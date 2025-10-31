<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    protected function ctx(Request $request): array
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        return [$restaurantId, $branchId];
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
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $fields = $doc['fields'] ?? [];
                $categories[$id] = [
                    'id' => $id,
                    'name' => $fields['name']['stringValue'] ?? '',
                    'description' => $fields['description']['stringValue'] ?? '',
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
                        'name' => $f['name']['stringValue'] ?? '',
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
                    'name' => $fields['name']['stringValue'] ?? '',
                    'description' => $fields['description']['stringValue'] ?? '',
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
                        'name' => $f['name']['stringValue'] ?? '',
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
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'displayOrder' => 'nullable|integer|min:0',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus";
        $documentId = 'cat_' . Str::random(6);
        $firebase->createDocument($basePath, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'displayOrder' => (int) ($data['displayOrder'] ?? 0),
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
            'name' => $f['name']['stringValue'] ?? '',
            'description' => $f['description']['stringValue'] ?? '',
            'displayOrder' => (int) ($f['displayOrder']['integerValue'] ?? 0),
        ];
        return view('admin.menu.category-edit', compact('category'));
    }

    public function updateCategory(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'displayOrder' => 'nullable|integer|min:0',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus", $categoryId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'displayOrder' => (int) ($data['displayOrder'] ?? 0),
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
        return view('admin.menu.item-create', compact('categoryId'));
    }

    public function storeItem(Request $request, FirebaseService $firebase, string $categoryId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'available' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        $basePath = "restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items";
        $itemId = 'item_' . Str::random(6);
        $firebase->createDocument($basePath, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => (float) $data['price'],
            'available' => (bool) ($data['available'] ?? true),
            'imageUrl' => $data['imageUrl'] ?? '',
        ], $itemId);
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
            'name' => $f['name']['stringValue'] ?? '',
            'description' => $f['description']['stringValue'] ?? '',
            'price' => isset($f['price']['doubleValue']) ? (float)$f['price']['doubleValue'] : (float)($f['price']['integerValue'] ?? 0),
            'available' => (bool) ($f['available']['booleanValue'] ?? true),
            'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
        ];
        return view('admin.menu.item-edit', compact('item'));
    }

    public function updateItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'available' => 'nullable|boolean',
            'imageUrl' => 'nullable|url',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => (float) $data['price'],
            'available' => (bool) ($data['available'] ?? true),
            'imageUrl' => $data['imageUrl'] ?? '',
        ]);
        return redirect()->route('menu.index')->with('status', 'Item updated');
    }

    public function destroyItem(Request $request, FirebaseService $firebase, string $categoryId, string $itemId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/menus/{$categoryId}/items", $itemId);
        return redirect()->route('menu.index')->with('status', 'Item deleted');
    }
}

