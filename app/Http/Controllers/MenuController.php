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
                        'availability' => (bool)($f['availability']['booleanValue'] ?? false),
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
                        'availability' => (bool)($f['availability']['booleanValue'] ?? false),
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
            'isSpecial' => 'nullable|boolean',
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
            'isSpecial' => (bool) ($data['isSpecial'] ?? false),
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
            'isSpecial' => (bool) ($f['isSpecial']['booleanValue'] ?? false),
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
            'isSpecial' => 'nullable|boolean',
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
        $payload = [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'displayOrder' => (int) ($data['displayOrder'] ?? 0),
            'isSpecial' => (bool) ($data['isSpecial'] ?? false),
            'imageUrl' => $imageUrl,
        ];
        $existing = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $f = $existing['fields'] ?? [];
        $decode = function($v) use (&$decode) {
            if (!is_array($v)) return $v;
            if (isset($v['stringValue'])) return $v['stringValue'];
            if (isset($v['integerValue'])) return (int)$v['integerValue'];
            if (isset($v['doubleValue'])) return (float)$v['doubleValue'];
            if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
            if (isset($v['nullValue'])) return null;
            if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']);
            if (isset($v['mapValue']['fields'])) { $out=[]; foreach ($v['mapValue']['fields'] as $kk=>$vv){ $out[$kk]=$decode($vv);} return $out; }
            return $v;
        };
        $before = [];
        $after = [];
        foreach ($payload as $k=>$v) { if (array_key_exists($k,$f)) { $before[$k]=$decode($f[$k]); } $after[$k]=$v; }
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId, $payload);
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
            'name' => $cf['name']['stringValue'] ?? ($cf['name_en']['stringValue'] ?? ''),
            'name_en' => $cf['name_en']['stringValue'] ?? ($cf['name']['stringValue'] ?? ''),
            'name_fi' => $cf['name_fi']['stringValue'] ?? '',
            'description' => $cf['description']['stringValue'] ?? ($cf['description_en']['stringValue'] ?? ''),
            'description_en' => $cf['description_en']['stringValue'] ?? ($cf['description']['stringValue'] ?? ''),
            'description_fi' => $cf['description_fi']['stringValue'] ?? '',
            'displayOrder' => (int) ($cf['displayOrder']['integerValue'] ?? 0),
            'imageUrl' => $cf['imageUrl']['stringValue'] ?? '',
        ];
        // Load items from source
        $itemsResp = $firebase->getCollection($srcBase . '/items');
        $itemDocs = $itemsResp['documents'] ?? [];
        $items = [];
        foreach ($itemDocs as $doc) {
            $iid = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            // Load sizes and bases subcollections for this item
            $sizesCol = $firebase->getCollection($srcBase . "/items/{$iid}/sizes");
            $basesCol = $firebase->getCollection($srcBase . "/items/{$iid}/bases");
            $sizes = [];
            foreach (($sizesCol['documents'] ?? []) as $sd) {
                $sid = Str::afterLast($sd['name'], '/');
                $sf = $sd['fields'] ?? [];
                $sizes[] = [
                    'id' => $sid,
                    'name' => $sf['name']['stringValue'] ?? $sid,
                    'price' => isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0),
                ];
            }
            $bases = [];
            foreach (($basesCol['documents'] ?? []) as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $bases[] = [
                    'id' => $bid,
                    'name' => $bf['name']['stringValue'] ?? $bid,
                    'price' => isset($bf['price']['doubleValue']) ? (float)$bf['price']['doubleValue'] : (float)($bf['price']['integerValue'] ?? 0),
                ];
            }
            $items[] = [
                'id' => $iid,
                'payload' => [
                    'name' => $f['name']['stringValue'] ?? ($f['name_en']['stringValue'] ?? ''),
                    'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
                    'name_fi' => $f['name_fi']['stringValue'] ?? '',
                    'description' => $f['description']['stringValue'] ?? ($f['description_en']['stringValue'] ?? ''),
                    'description_en' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
                    'description_fi' => $f['description_fi']['stringValue'] ?? '',
                    'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
                    'availability' => (bool)($f['availability']['booleanValue'] ?? false),
                    'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
                ],
                'sizes' => $sizes,
                'bases' => $bases,
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
        // Load category to know if it's special
        $firebase = app(\App\Services\FirebaseService::class);
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $catFields = $catDoc['fields'] ?? [];
        $isSpecialCategory = (bool)($catFields['isSpecial']['booleanValue'] ?? false);
        // Load sizes and bases (prefer branch; fallback to restaurant defaults if branch empty)
        $sizes = [];
        $bases = [];
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
        // Load ingredients if special category
        $ingredients = [];
        if ($isSpecialCategory) {
            $iresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients");
            $idocs = $iresp['documents'] ?? [];
            if (empty($idocs)) {
                $iresp = $firebase->getCollection("restaurants/{$restaurantId}/ingredients");
                $idocs = $iresp['documents'] ?? [];
            }
            foreach ($idocs as $idoc) {
                $iid = Str::afterLast($idoc['name'], '/');
                $iff = $idoc['fields'] ?? [];
                $ingredients[] = [
                    'id' => $iid,
                    'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($iff['name_fi']['stringValue'] ?? null) : ($iff['name_en']['stringValue'] ?? null))
                              ?? ($iff['name']['stringValue'] ?? $iid),
                ];
            }
        }
        return view('admin.menu.item-create', compact('categoryId','sizes','bases','isSpecialCategory','ingredients'));
    }

    public function storeItem(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'offerPrice' => 'nullable|numeric|min:0',
            'offerPriceAvailable' => 'nullable|boolean',
            'availability' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'sizes' => 'nullable|array',
            'sizes_price' => 'nullable|array',
            'bases' => 'nullable|array',
            'bases_price' => 'nullable|array',
            'ingredients' => 'nullable|array',
            'ingredients_max' => 'nullable|array',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items";
        // Read category to check special
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $catFields = $catDoc['fields'] ?? [];
        $isSpecialCategory = (bool)($catFields['isSpecial']['booleanValue'] ?? false);
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
        $finalOfferPrice = isset($data['offerPrice']) && $data['offerPrice']!==null && $data['offerPrice']!=='' ? (float)$data['offerPrice'] : 0.0;
        $offerPriceAvailableFlag = (bool) ($data['offerPriceAvailable'] ?? false);
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
            'availability' => (bool) ($data['availability'] ?? true),
            'offerPriceAvailable' => $offerPriceAvailableFlag,
            'imageUrl' => $imageUrl,
        ];
        // Only persist offerPrice when offerPriceAvailable is checked and a value provided
        if ($offerPriceAvailableFlag && isset($data['offerPrice']) && $data['offerPrice'] !== null && $data['offerPrice'] !== '') {
            $payload['offerPrice'] = (float)$data['offerPrice'];
        }
        // Attach audit info (after) to ensure item id and name are present
        $request->attributes->set('audit_before', []);
        $request->attributes->set('audit_after', [
            'itemId' => $itemId,
            'itemName' => $payload['name'] ?? ($payload['name_en'] ?? ''),
        ]);

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
        // If special category, persist selected ingredients with per-size max and copy sub-ingredients
        if ($isSpecialCategory) {
            $selectedIngredients = array_keys($request->input('ingredients', []));
            $maxMap = $request->input('ingredients_max', []);
            foreach ($selectedIngredients as $ingId) {
                // load ingredient for name
                $ingDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $ingId);
                if (empty($ingDoc['fields'])) { $ingDoc = $firebase->getDocument("restaurants/{$restaurantId}/ingredients", $ingId); }
                $ingFields = $ingDoc['fields'] ?? [];
                $ingName = $ingFields['name']['stringValue'] ?? ($ingFields['name_en']['stringValue'] ?? $ingId);
                $firebase->createDocument($basePath . "/{$itemId}/ingredients", [
                    'name' => $ingName,
                    // legacy overall max kept as 0 when using per-size limits
                    'maxSelections' => 0,
                ], $ingId);
                // write per-size limits if provided
                $perSize = $maxMap[$ingId] ?? [];
                if (is_array($perSize)) {
                    foreach ($perSize as $sizeId => $mx) {
                        if ($mx === '' || $mx === null) { continue; }
                        $firebase->createDocument($basePath . "/{$itemId}/ingredients/{$ingId}/sizeLimits", [
                            'maxSelections' => (int)$mx,
                        ], (string)$sizeId);
                    }
                }
                // copy sub-ingredients
                $subCol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingId}/subingredients");
                $subDocs = $subCol['documents'] ?? [];
                foreach ($subDocs as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $sf = $sd['fields'] ?? [];
                    $firebase->createDocument($basePath . "/{$itemId}/ingredients/{$ingId}/subingredients", [
                        'name' => $sf['name']['stringValue'] ?? ($sf['name_en']['stringValue'] ?? $sid),
                        'name_en' => $sf['name_en']['stringValue'] ?? ($sf['name']['stringValue'] ?? ''),
                        'name_fi' => $sf['name_fi']['stringValue'] ?? '',
                        'description' => $sf['description']['stringValue'] ?? ($sf['description_en']['stringValue'] ?? ''),
                        'description_en' => $sf['description_en']['stringValue'] ?? ($sf['description']['stringValue'] ?? ''),
                        'description_fi' => $sf['description_fi']['stringValue'] ?? '',
                        'price' => isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0),
                    ], $sid);
                }
            }
        }
        return redirect()->route('menu.index')->with('status', 'Item created');
    }

    public function editItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // Load category special flag
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $catFields = $catDoc['fields'] ?? [];
        $isSpecialCategory = (bool)($catFields['isSpecial']['booleanValue'] ?? false);
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
            'offerPrice' => isset($f['offerPrice']['doubleValue']) ? (float)$f['offerPrice']['doubleValue'] : (float)($f['offerPrice']['integerValue'] ?? 0),
            'availability' => (bool) ($f['availability']['booleanValue'] ?? false),
            'offerPriceAvailable' => (bool) ($f['offerPriceAvailable']['booleanValue'] ?? false),
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
        // Ingredients for special category
        $ingredients = [];
        $item['ingredientsOptions'] = [];
        if ($isSpecialCategory) {
            $iresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients");
            $idocs = $iresp['documents'] ?? [];
            if (empty($idocs)) { $iresp = $firebase->getCollection("restaurants/{$restaurantId}/ingredients"); $idocs = $iresp['documents'] ?? []; }
            foreach ($idocs as $idoc) {
                $iid = Str::afterLast($idoc['name'], '/');
                $iff = $idoc['fields'] ?? [];
                $ingredients[] = [
                    'id' => $iid,
                    'name' => ($request->session()->get('ui_lang','en')==='fi' ? ($iff['name_fi']['stringValue'] ?? null) : ($iff['name_en']['stringValue'] ?? null))
                              ?? ($iff['name']['stringValue'] ?? $iid),
                ];
            }
            // load selected ingredients under item
            $icol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients");
            foreach (($icol['documents'] ?? []) as $idoc2) {
                $iid = Str::afterLast($idoc2['name'], '/');
                $if2 = $idoc2['fields'] ?? [];
                $opt = [
                    'id' => $iid,
                    'name' => $if2['name']['stringValue'] ?? $iid,
                    'maxSelections' => isset($if2['maxSelections']['integerValue']) ? (int)$if2['maxSelections']['integerValue'] : 0,
                    'sizeMax' => [],
                ];
                // load per-size limits if present
                $slcol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$iid}/sizeLimits");
                foreach (($slcol['documents'] ?? []) as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $sf = $sd['fields'] ?? [];
                    $opt['sizeMax'][$sid] = isset($sf['maxSelections']['integerValue']) ? (int)$sf['maxSelections']['integerValue'] : 0;
                }
                $item['ingredientsOptions'][] = $opt;
            }
        }
        return view('admin.menu.item-edit', compact('item','sizes','bases','isSpecialCategory','ingredients'));
    }

    public function updateItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'offerPrice' => 'nullable|numeric|min:0',
            'offerPriceAvailable' => 'nullable|boolean',
            'availability' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'sizeId' => 'nullable|string',
            'sizePrice' => 'nullable|numeric|min:0',
            'baseId' => 'nullable|string',
            'basePrice' => 'nullable|numeric|min:0',
            'ingredients' => 'nullable|array',
            'ingredients_max' => 'nullable|array',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        // Read category to check special
        $catDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId);
        $catFields = $catDoc['fields'] ?? [];
        $isSpecialCategory = (bool)($catFields['isSpecial']['booleanValue'] ?? false);
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
            'availability' => (bool) ($data['availability'] ?? true),
            'offerPriceAvailable' => (bool) ($data['offerPriceAvailable'] ?? false),
            'imageUrl' => $imageUrl,
        ];
        // Save offerPrice if provided (do not overwrite when left blank)
        // If offerAvailable checked, update offerPrice (allow clearing when unchecked)
        if (!empty($data['offerPriceAvailable'])) {
            if (array_key_exists('offerPrice', $data) && $data['offerPrice'] !== null && $data['offerPrice'] !== '') {
                $payload['offerPrice'] = (float)$data['offerPrice'];
            } else {
                // Explicitly clear offerPrice when offerPriceAvailable is set but no value provided
                $payload['offerPrice'] = null;
            }
        } else {
            // If offer not available, ensure offerPrice is cleared
            $payload['offerPrice'] = null;
        }
        // Attach before/after for item
        $existing = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        $f = $existing['fields'] ?? [];
        $decode = function($v) use (&$decode) { if (!is_array($v)) return $v; if (isset($v['stringValue'])) return $v['stringValue']; if (isset($v['integerValue'])) return (int)$v['integerValue']; if (isset($v['doubleValue'])) return (float)$v['doubleValue']; if (isset($v['booleanValue'])) return (bool)$v['booleanValue']; if (isset($v['nullValue'])) return null; if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']); if (isset($v['mapValue']['fields'])) { $out=[]; foreach ($v['mapValue']['fields'] as $kk=>$vv){ $out[$kk]=$decode($vv);} return $out;} return $v; };
        $before = [];
        $after = [];
        foreach ($payload as $k=>$v) { if (array_key_exists($k,$f)) { $before[$k]=$decode($f[$k]); } $after[$k]=$v; }
        // Ensure audit includes item id and a friendly item name
        $existingName = $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? '');
        $before['itemId'] = $itemId;
        $before['itemName'] = $existingName;
        $after['itemId'] = $itemId;
        $after['itemName'] = $payload['name'] ?? ($payload['name_en'] ?? $existingName);
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);
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
        // Replace ingredients subcollection if special category
        if ($isSpecialCategory) {
            $existingIngs = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients");
            foreach (($existingIngs['documents'] ?? []) as $idoc) {
                $iid = Str::afterLast($idoc['name'], '/');
                // delete nested subingredients first
                $existSubs = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$iid}/subingredients");
                foreach (($existSubs['documents'] ?? []) as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$iid}/subingredients", $sid);
                }
                // delete sizeLimits if any
                $existSL = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$iid}/sizeLimits");
                foreach (($existSL['documents'] ?? []) as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$iid}/sizeLimits", $sid);
                }
                $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients", $iid);
            }
            $selectedIngredients = array_keys($request->input('ingredients', []));
            $maxMap = $request->input('ingredients_max', []);
            foreach ($selectedIngredients as $ingId) {
                $ingDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $ingId);
                if (empty($ingDoc['fields'])) { $ingDoc = $firebase->getDocument("restaurants/{$restaurantId}/ingredients", $ingId); }
                $ingFields = $ingDoc['fields'] ?? [];
                $ingName = $ingFields['name']['stringValue'] ?? ($ingFields['name_en']['stringValue'] ?? $ingId);
                $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients", [
                    'name' => $ingName,
                    'maxSelections' => 0,
                ], $ingId);
                // write per-size limits if provided
                $perSize = $maxMap[$ingId] ?? [];
                if (is_array($perSize)) {
                    foreach ($perSize as $sizeId => $mx) {
                        if ($mx === '' || $mx === null) { continue; }
                        $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$ingId}/sizeLimits", [
                            'maxSelections' => (int)$mx,
                        ], (string)$sizeId);
                    }
                }
                $subCol = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingId}/subingredients");
                foreach (($subCol['documents'] ?? []) as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $sf = $sd['fields'] ?? [];
                    $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items/{$itemId}/ingredients/{$ingId}/subingredients", [
                        'name' => $sf['name']['stringValue'] ?? ($sf['name_en']['stringValue'] ?? $sid),
                        'name_en' => $sf['name_en']['stringValue'] ?? ($sf['name']['stringValue'] ?? ''),
                        'name_fi' => $sf['name_fi']['stringValue'] ?? '',
                        'description' => $sf['description']['stringValue'] ?? ($sf['description_en']['stringValue'] ?? ''),
                        'description_en' => $sf['description_en']['stringValue'] ?? ($sf['description']['stringValue'] ?? ''),
                        'description_fi' => $sf['description_fi']['stringValue'] ?? '',
                        'price' => isset($sf['price']['doubleValue']) ? (float)$sf['price']['doubleValue'] : (float)($sf['price']['integerValue'] ?? 0),
                    ], $sid);
                }
            }
        }
        return redirect()->route('menu.index')->with('status', 'Item updated');
    }

    public function destroyItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        return redirect()->route('menu.index')->with('status', 'Item deleted');
    }

    public function toggleItemAvailability(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        $f = $doc['fields'] ?? [];
        // If field doesn't exist, treat as unavailable (false) and add it
        $availability = (bool) ($f['availability']['booleanValue'] ?? false);
        $itemName = $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? $itemId);
        $before = ['availability' => $availability, 'itemId' => $itemId, 'itemName' => $itemName];
        $after = ['availability' => !$availability, 'itemId' => $itemId, 'itemName' => $itemName];
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId, [
            'availability' => !$availability,
        ]);
        return back()->with('status', !$availability ? 'Item marked available' : 'Item marked unavailable');
    }
}

