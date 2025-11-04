<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MenuController extends Controller
{
    protected function ctx(Request $request): array
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        return [$restaurantId, $branchId];
    }

    private function storePublicUpload($file, string $subdir): string
    {
        $dest = public_path('uploads/' . trim($subdir, '/'));
        if (!File::exists($dest)) { File::makeDirectory($dest, 0755, true); }
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug(substr($base, 0, 50));
        $filename = $slug . '-' . date('YmdHis') . '-' . Str::random(4) . '.' . $ext;
        $file->move($dest, $filename);
        return asset('uploads/' . trim($subdir, '/') . '/' . $filename);
    }

    public function index(Request $request, FirebaseService $firebase)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        // Provide branches for selector
        $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $branchesDocs = $branchesResp['documents'] ?? [];
        $branches = [];
        foreach ($branchesDocs as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $bf = $bd['fields'] ?? [];
            $branches[] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
            ];
        }

        if ($branchId) {
            // Single-branch mode
            $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus";
            $resp = $firebase->getCollection($basePath);
            $docs = $resp['documents'] ?? [];
            $categories = [];
            $lang = $request->session()->get('ui_lang', 'en');
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $fields = $doc['fields'] ?? [];
                $categories[$id] = [
                    'id' => $id,
                    'name' => ($lang==='fi' ? ($fields['name_fi']['stringValue'] ?? null) : ($fields['name_en']['stringValue'] ?? null))
                              ?? ($fields['name']['stringValue'] ?? ''),
                    'description' => ($lang==='fi' ? ($fields['description_fi']['stringValue'] ?? null) : ($fields['description_en']['stringValue'] ?? null))
                                     ?? ($fields['description']['stringValue'] ?? ''),
                    'displayOrder' => (int) ($fields['displayOrder']['integerValue'] ?? 0),
                ];
                $itemsResp = $firebase->getCollection($basePath . "/{$id}/items");
                $itemDocs = $itemsResp['documents'] ?? [];
                $items = [];
                foreach ($itemDocs as $i) {
                    $iid = Str::afterLast($i['name'], '/');
                    $f = $i['fields'] ?? [];
                    $items[] = [
                        'id' => $iid,
                        'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                                  ?? ($f['name']['stringValue'] ?? ''),
                        'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
                        'available' => (bool)($f['available']['booleanValue'] ?? true),
                    ];
                }
                $categories[$id]['items'] = $items;
            }
            return view('admin.menu.index', [
                'mode' => 'single',
                'categories' => $categories,
                'branches' => $branches,
                'currentBranchId' => $branchId,
            ]);
        }

        // All-branches mode: aggregate per branch
        $branchMenus = [];
        $lang = $request->session()->get('ui_lang', 'en');
        foreach ($branches as $b) {
            $bId = $b['id'];
            $basePath = "restaurants/{$restaurantId}/branches/{$bId}/menus";
            $resp = $firebase->getCollection($basePath);
            $docs = $resp['documents'] ?? [];
            $categories = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $fields = $doc['fields'] ?? [];
                $categories[$id] = [
                    'id' => $id,
                    'name' => ($lang==='fi' ? ($fields['name_fi']['stringValue'] ?? null) : ($fields['name_en']['stringValue'] ?? null))
                              ?? ($fields['name']['stringValue'] ?? ''),
                    'description' => ($lang==='fi' ? ($fields['description_fi']['stringValue'] ?? null) : ($fields['description_en']['stringValue'] ?? null))
                                     ?? ($fields['description']['stringValue'] ?? ''),
                    'displayOrder' => (int) ($fields['displayOrder']['integerValue'] ?? 0),
                ];
                $itemsResp = $firebase->getCollection($basePath . "/{$id}/items");
                $itemDocs = $itemsResp['documents'] ?? [];
                $items = [];
                foreach ($itemDocs as $i) {
                    $iid = Str::afterLast($i['name'], '/');
                    $f = $i['fields'] ?? [];
                    $items[] = [
                        'id' => $iid,
                        'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                                  ?? ($f['name']['stringValue'] ?? ''),
                        'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
                        'available' => (bool)($f['available']['booleanValue'] ?? true),
                    ];
                }
                $categories[$id]['items'] = $items;
            }
            $branchMenus[] = [
                'branch' => $b,
                'categories' => $categories,
            ];
        }
        return view('admin.menu.index', [
            'mode' => 'all',
            'branchMenus' => $branchMenus,
            'branches' => $branches,
            'currentBranchId' => null,
        ]);
    }

    // Category CRUD
    public function createCategory(Request $request)
    {
        // Branch must be selected before adding
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
        if (!$branchId) { return redirect()->route('menu.index')->with('status', 'Select a branch from the top-right before adding.'); }
        return view('admin.menu.category-create');
    }

    public function storeCategory(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'displayOrder' => 'nullable|integer|min:0',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus";
        $documentId = 'cat_' . Str::random(6);
        $imageUrl = $data['imageUrl'] ?? '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'categories');
        }
        $firebase->createDocument($basePath, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'displayOrder' => (int) ($data['displayOrder'] ?? 0),
            'imageUrl' => $imageUrl,
        ], $documentId);

        return redirect()->route('menu.index')->with('status', 'Category created');
    }

    public function editCategory(Request $request, FirebaseService $firebase, string $categoryId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $f = $doc['fields'] ?? [];
        $category = [
            'id' => $categoryId,
            'name' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_fi' => $f['name_fi']['stringValue'] ?? '',
            'description' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_en' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_fi' => $f['description_fi']['stringValue'] ?? '',
            'displayOrder' => (int) ($f['displayOrder']['integerValue'] ?? 0),
            'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
        ];
        return view('admin.menu.category-edit', compact('category'));
    }

    public function updateCategory(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'displayOrder' => 'nullable|integer|min:0',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $imageUrl = $data['imageUrl'] ?? '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'categories');
        }
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'displayOrder' => (int) ($data['displayOrder'] ?? 0),
            'imageUrl' => $imageUrl,
        ]);
        return redirect()->route('menu.index')->with('status', 'Category updated');
    }

    public function destroyCategory(Request $request, FirebaseService $firebase, string $categoryId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        return redirect()->route('menu.index')->with('status', 'Category deleted');
    }

    // Copy a category (and its items) to other branches
    public function copyCategoryForm(Request $request, FirebaseService $firebase, string $categoryId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        if (empty($catDoc['fields'])) {
            return redirect()->route('menu.index')->with('status', 'Category not found');
        }
        $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $branchesDocs = $branchesResp['documents'] ?? [];
        $branches = [];
        foreach ($branchesDocs as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            if ($bid === $branchId) continue; // exclude current branch
            $bf = $bd['fields'] ?? [];
            $branches[] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
            ];
        }
        $categoryName = $catDoc['fields']['name']['stringValue'] ?? $categoryId;
        return view('admin.menu.category-copy', compact('categoryId', 'categoryName', 'branches'));
    }

    public function copyCategory(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'targets' => 'required|array|min:1',
            'targets.*' => 'string',
        ]);
        [$restaurantId, $sourceBranchId] = $this->ctx($request);
        if (!$restaurantId || !$sourceBranchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }

        $srcBase = "restaurants/{$restaurantId}/branches/{$sourceBranchId}/menus/{$categoryId}";
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$sourceBranchId}/menus", $categoryId);
        $cf = $catDoc['fields'] ?? [];
        if (empty($cf)) { return redirect()->route('menu.index')->with('status', 'Source category not found'); }
        $catPayload = [
            'name' => $cf['name']['stringValue'] ?? '',
            'description' => $cf['description']['stringValue'] ?? '',
            'displayOrder' => (int) ($cf['displayOrder']['integerValue'] ?? 0),
        ];
        // Load items from source
        $itemsResp = $firebase->getCollection($srcBase . '/items');
        $itemDocs = $itemsResp['documents'] ?? [];
        $items = [];
        foreach ($itemDocs as $doc) {
            $iid = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $items[] = [
                'id' => $iid,
                'payload' => [
                    'name' => $f['name']['stringValue'] ?? '',
                    'description' => $f['description']['stringValue'] ?? '',
                    'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
                    'available' => (bool)($f['available']['booleanValue'] ?? true),
                    'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
                ],
            ];
        }

        // Copy to each target branch (upsert with same IDs)
        foreach ($data['targets'] as $targetBranchId) {
            if ($targetBranchId === $sourceBranchId) continue;
            $firebase->createDocument("restaurants/{$restaurantId}/branches/{$targetBranchId}/menus", $catPayload, $categoryId);
            foreach ($items as $it) {
                $firebase->createDocument("restaurants/{$restaurantId}/branches/{$targetBranchId}/menus/{$categoryId}/items", $it['payload'], $it['id']);
            }
        }
        return redirect()->route('menu.index')->with('status', 'Category copied to selected branches');
    }

    // Item CRUD
    public function createItem(Request $request, string $categoryId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        // Load sizes and bases (prefer branch; fallback to restaurant defaults if branch empty)
        $sizes = [];
        $bases = [];
        $firebase = app(\App\Services\FirebaseService::class);
        $sresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/sizes");
        $sdocs = $sresp['documents'] ?? [];
        if (empty($sdocs)) {
            $sresp = $firebase->getCollection("restaurants/{$restaurantId}/sizes");
            $sdocs = $sresp['documents'] ?? [];
        }
        foreach ($sdocs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $sizes[] = [
                'id' => $id,
                'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                          ?? ($f['name']['stringValue'] ?? $id),
                'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
            ];
        }
        $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/bases");
        $bdocs = $bresp['documents'] ?? [];
        if (empty($bdocs)) {
            $bresp = $firebase->getCollection("restaurants/{$restaurantId}/bases");
            $bdocs = $bresp['documents'] ?? [];
        }
        foreach ($bdocs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $bases[] = [
                'id' => $id,
                'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                          ?? ($f['name']['stringValue'] ?? $id),
                'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
            ];
        }
        return view('admin.menu.item-create', compact('categoryId','sizes','bases'));
    }

    public function storeItem(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'available' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'sizes' => 'nullable|array',
            'sizes_price' => 'nullable|array',
            'bases' => 'nullable|array',
            'bases_price' => 'nullable|array',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items";
        // Resolve selected sizes and bases (multiple)
        $selectedSizes = array_keys($request->input('sizes', []));
        $sizesPriceMap = $request->input('sizes_price', []);
        $selectedBases = array_keys($request->input('bases', []));
        $basesPriceMap = $request->input('bases_price', []);
        $sizesOptions = [];
        $basesOptions = [];
        $sum = 0.0;
        foreach ($selectedSizes as $sid) {
            $sd = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/sizes", $sid);
            if (empty($sd['fields'])) { $sd = $firebase->getDocument("restaurants/{$restaurantId}/sizes", $sid); }
            $sf = $sd['fields'] ?? [];
            $name = $sf['name']['stringValue'] ?? $sid;
            $defaultPrice = isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0);
            $p = isset($sizesPriceMap[$sid]) && $sizesPriceMap[$sid] !== '' ? (float)$sizesPriceMap[$sid] : $defaultPrice;
            $sizesOptions[] = ['id'=>$sid,'name'=>$name,'price'=>$p];
            $sum += $p;
        }
        foreach ($selectedBases as $bid) {
            $bd = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/bases", $bid);
            if (empty($bd['fields'])) { $bd = $firebase->getDocument("restaurants/{$restaurantId}/bases", $bid); }
            $bf = $bd['fields'] ?? [];
            $name = $bf['name']['stringValue'] ?? $bid;
            $defaultPrice = isset($bf['price']['doubleValue']) ? (float)$bf['price']['doubleValue'] : (float)($bf['price']['integerValue'] ?? 0);
            $p = isset($basesPriceMap[$bid]) && $basesPriceMap[$bid] !== '' ? (float)$basesPriceMap[$bid] : $defaultPrice;
            $basesOptions[] = ['id'=>$bid,'name'=>$name,'price'=>$p];
            $sum += $p;
        }
        $finalPrice = isset($data['price']) && $data['price']!==null && $data['price']!=='' ? (float)$data['price'] : 0.0;
        $itemId = 'item_' . Str::random(6);
        $imageUrl = $data['imageUrl'] ?? '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'items');
        }
        $payload = [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'price' => $finalPrice,
            'available' => (bool) ($data['available'] ?? true),
            'imageUrl' => $imageUrl,
        ];
        // Create item document
        $firebase->createDocument($basePath, $payload, $itemId);
        // Create subcollections for sizes and bases
        foreach ($sizesOptions as $opt) {
            $firebase->createDocument($basePath . "/{$itemId}/sizes", [
                'name' => $opt['name'],
                'price' => (float)$opt['price'],
            ], $opt['id']);
        }
        foreach ($basesOptions as $opt) {
            $firebase->createDocument($basePath . "/{$itemId}/bases", [
                'name' => $opt['name'],
                'price' => (float)$opt['price'],
            ], $opt['id']);
        }
        return redirect()->route('menu.index')->with('status', 'Item created');
    }

    public function editItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        $f = $doc['fields'] ?? [];
        $item = [
            'id' => $itemId,
            'categoryId' => $categoryId,
            'name' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_fi' => $f['name_fi']['stringValue'] ?? '',
            'description' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_en' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_fi' => $f['description_fi']['stringValue'] ?? '',
            'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
            'available' => (bool) ($f['available']['booleanValue'] ?? true),
            'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
        ];
        // Load sizes/bases subcollections for editing
        $item['sizesOptions'] = [];
        $scol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/sizes");
        foreach (($scol['documents'] ?? []) as $sd) {
            $sid = Str::afterLast($sd['name'], '/');
            $sf = $sd['fields'] ?? [];
            $item['sizesOptions'][] = [
                'id' => $sid,
                'name' => $sf['name']['stringValue'] ?? $sid,
                'price' => isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0),
            ];
        }
        $item['basesOptions'] = [];
        $bcol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/bases");
        foreach (($bcol['documents'] ?? []) as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $bf = $bd['fields'] ?? [];
            $item['basesOptions'][] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
                'price' => isset($bf['price']['doubleValue']) ? (float)$bf['price']['doubleValue'] : (float)($bf['price']['integerValue'] ?? 0),
            ];
        }
        // Load sizes/bases for editing
        $sizes = [];
        $bases = [];
        $sresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/sizes");
        $sdocs = $sresp['documents'] ?? [];
        if (empty($sdocs)) { $sresp = $firebase->getCollection("restaurants/{$restaurantId}/sizes"); $sdocs = $sresp['documents'] ?? []; }
        foreach ($sdocs as $doc2) {
            $id = Str::afterLast($doc2['name'], '/');
            $ff = $doc2['fields'] ?? [];
            $sizes[] = [
                'id' => $id,
                'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($ff['name_fi']['stringValue'] ?? null) : ($ff['name_en']['stringValue'] ?? null))
                          ?? ($ff['name']['stringValue'] ?? $id),
                'price' => isset($ff['price']['doubleValue']) ? (float)$ff['price']['doubleValue'] : (float)($ff['price']['integerValue'] ?? 0),
            ];
        }
        $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/bases");
        $bdocs = $bresp['documents'] ?? [];
        if (empty($bdocs)) { $bresp = $firebase->getCollection("restaurants/{$restaurantId}/bases"); $bdocs = $bresp['documents'] ?? []; }
        foreach ($bdocs as $doc3) {
            $id = Str::afterLast($doc3['name'], '/');
            $ff = $doc3['fields'] ?? [];
            $bases[] = [
                'id' => $id,
                'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($ff['name_fi']['stringValue'] ?? null) : ($ff['name_en']['stringValue'] ?? null))
                          ?? ($ff['name']['stringValue'] ?? $id),
                'price' => isset($ff['price']['doubleValue']) ? (float)$ff['price']['doubleValue'] : (float)($ff['price']['integerValue'] ?? 0),
            ];
        }
        return view('admin.menu.item-edit', compact('item','sizes','bases'));
    }

    public function updateItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'available' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'sizeId' => 'nullable|string',
            'sizePrice' => 'nullable|numeric|min:0',
            'baseId' => 'nullable|string',
            'basePrice' => 'nullable|numeric|min:0',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        // Resolve multiple selections and compute price
        $selectedSizes = array_keys($request->input('sizes', []));
        $sizesPriceMap = $request->input('sizes_price', []);
        $selectedBases = array_keys($request->input('bases', []));
        $basesPriceMap = $request->input('bases_price', []);
        $sizesOptions = [];
        $basesOptions = [];
        $sum = 0.0;
        foreach ($selectedSizes as $sid) {
            $sd = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/sizes", $sid);
            if (empty($sd['fields'])) { $sd = $firebase->getDocument("restaurants/{$restaurantId}/sizes", $sid); }
            $sf = $sd['fields'] ?? [];
            $name = $sf['name']['stringValue'] ?? $sid;
            $defaultPrice = isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0);
            $p = isset($sizesPriceMap[$sid]) && $sizesPriceMap[$sid] !== '' ? (float)$sizesPriceMap[$sid] : $defaultPrice;
            $sizesOptions[] = ['id'=>$sid,'name'=>$name,'price'=>$p];
            $sum += $p;
        }
        foreach ($selectedBases as $bid) {
            $bd = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/bases", $bid);
            if (empty($bd['fields'])) { $bd = $firebase->getDocument("restaurants/{$restaurantId}/bases", $bid); }
            $bf = $bd['fields'] ?? [];
            $name = $bf['name']['stringValue'] ?? $bid;
            $defaultPrice = isset($bf['price']['doubleValue']) ? (float)$bf['price']['doubleValue'] : (float)($bf['price']['integerValue'] ?? 0);
            $p = isset($basesPriceMap[$bid]) && $basesPriceMap[$bid] !== '' ? (float)$basesPriceMap[$bid] : $defaultPrice;
            $basesOptions[] = ['id'=>$bid,'name'=>$name,'price'=>$p];
            $sum += $p;
        }
        $finalPrice = isset($data['price']) && $data['price']!==null && $data['price']!=='' ? (float)$data['price'] : 0.0;
        $imageUrl = $data['imageUrl'] ?? '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'items');
        }
        $payload = [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'price' => $finalPrice,
            'available' => (bool) ($data['available'] ?? true),
            'imageUrl' => $imageUrl,
        ];
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId, $payload);
        // Replace subcollections: delete existing then create new
        $existingSizes = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/sizes");
        foreach (($existingSizes['documents'] ?? []) as $sd) {
            $sid = Str::afterLast($sd['name'], '/');
            $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/sizes", $sid);
        }
        foreach ($sizesOptions as $opt) {
            $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/sizes", [
                'name' => $opt['name'],
                'price' => (float)$opt['price'],
            ], $opt['id']);
        }
        $existingBases = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/bases");
        foreach (($existingBases['documents'] ?? []) as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/bases", $bid);
        }
        foreach ($basesOptions as $opt) {
            $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/bases", [
                'name' => $opt['name'],
                'price' => (float)$opt['price'],
            ], $opt['id']);
        }
        return redirect()->route('menu.index')->with('status', 'Item updated');
    }

    public function destroyItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        return redirect()->route('menu.index')->with('status', 'Item deleted');
    }
}

