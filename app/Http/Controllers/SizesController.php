<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class SizesController extends Controller
{
    protected function ctx(Request $request): array
    {
        return [
            $request->session()->get('restaurantId'),
            $request->session()->get('branchId'),
            $request->session()->get('role'),
        ];
    }

    public function index(Request $request, FirebaseService $firebase)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        $sizes = [];
        $allBranches = false;
        if ($branchId) {
            // Use branch sizes when branch is selected
            $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/sizes");
            $docs = $bresp['documents'] ?? [];
            $lang = $request->session()->get('ui_lang','en');
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $sizes[] = [
                    'id' => $id,
                    'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                              ?? ($f['name']['stringValue'] ?? ''),
                    'price' => (float)($f['price']['doubleValue'] ?? ($f['price']['integerValue'] ?? 0)),
                    'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
                ];
            }
        } else {
            // No branch selected: list sizes from all branches
            $allBranches = true;
            $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $bdocs = $bresp['documents'] ?? [];
            $lang = $request->session()->get('ui_lang','en');
            foreach ($bdocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branchName = $bf['name']['stringValue'] ?? $bid;
                $sresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bid}/sizes");
                $sdocs = $sresp['documents'] ?? [];
                foreach ($sdocs as $doc) {
                    $id = Str::afterLast($doc['name'], '/');
                    $f = $doc['fields'] ?? [];
                    $sizes[] = [
                        'id' => $id,
                        'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                                  ?? ($f['name']['stringValue'] ?? ''),
                        'price' => (float)($f['price']['doubleValue'] ?? ($f['price']['integerValue'] ?? 0)),
                        'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
                        'branchId' => $bid,
                        'branchName' => $branchName,
                    ];
                }
            }
        }
        // Build branches list for top-right selector
        $branches = [];
        $currentBranchId = $branchId;
        $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $bdocs = $bresp['documents'] ?? [];
        foreach ($bdocs as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $bf = $bd['fields'] ?? [];
            $branches[] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
            ];
        }
        return view('admin.menu.sizes.index', compact('sizes','allBranches','branches','currentBranchId'));
    }

    public function create(Request $request)
    {
        [$restaurantId, , $role] = $this->ctx($request);
        if ($role === 'admin') {
            $firebase = app(\App\Services\FirebaseService::class);
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            $restaurants = [];
            foreach ($restaurantsDocs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $restaurants[] = [
                    'id' => $id,
                    'name' => $f['name']['stringValue'] ?? $id,
                ];
            }
            $selectedRestaurantId = $request->query('restaurantId') ?? ($restaurants[0]['id'] ?? null);
            $branches = [];
            if ($selectedRestaurantId) {
                $branchesResp = $firebase->getCollection("restaurants/{$selectedRestaurantId}/branches");
                $branchesDocs = $branchesResp['documents'] ?? [];
                foreach ($branchesDocs as $bd) {
                    $bid = Str::afterLast($bd['name'], '/');
                    $bf = $bd['fields'] ?? [];
                    $branches[] = [
                        'id' => $bid,
                        'name' => $bf['name']['stringValue'] ?? $bid,
                    ];
                }
            }
            return view('admin.menu.sizes.create', compact('restaurants','branches','selectedRestaurantId'));
        }
        if ($role === 'restaurant_admin') {
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            $firebase = app(\App\Services\FirebaseService::class);
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
            return view('admin.menu.sizes.create', compact('branches'));
        }
        // branch_admin: no selectors
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }
        return view('admin.menu.sizes.create');
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->query('branchId', $branchId);
        $data = $request->validate([
            'name_en' => 'required|string|max:80',
            'name_fi' => 'required|string|max:80',
            'price' => 'required|numeric|min:0',
            'isActive' => 'nullable|boolean',
            'restaurantId' => $role === 'admin' ? 'required|string' : 'nullable|string',
            'branchId' => $role === 'admin' ? 'nullable|string' : ($role === 'restaurant_admin' ? 'required|string' : 'nullable|string'),
        ]);
        if ($role === 'admin') {
            $restaurantId = $data['restaurantId'];
            $branchId = $data['branchId'] ?? null;
        } elseif ($role === 'restaurant_admin') {
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            $branchId = $data['branchId'];
        } else {
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            // branch_admin uses session branch if set
        }
        $coll = $branchId
            ? "restaurants/{$restaurantId}/branches/{$branchId}/sizes"
            : "restaurants/{$restaurantId}/sizes";
        $id = 'size_' . Str::random(6);
        $firebase->createDocument($coll, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'price' => (float)$data['price'],
            'isActive' => (bool)($data['isActive'] ?? true),
        ], $id);
        return redirect()->route('menu.sizes.index')->with('status', 'Size created');
    }

    public function edit(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->query('branchId', $branchId);
        if ($role === 'branch_admin' && !$branchId) {
            return redirect()->route('menu.sizes.index')->with('status', 'Select a branch to edit sizes.');
        }
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }
        $coll = $branchId
            ? "restaurants/{$restaurantId}/branches/{$branchId}/sizes"
            : "restaurants/{$restaurantId}/sizes";
        $doc = $firebase->getDocument($coll, $id);
        $f = $doc['fields'] ?? [];
        $size = [
            'id' => $id,
            'name' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_fi' => $f['name_fi']['stringValue'] ?? '',
            'price' => (float)($f['price']['doubleValue'] ?? ($f['price']['integerValue'] ?? 0)),
            'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
        ];
        return view('admin.menu.sizes.edit', compact('size'));
    }

    public function update(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->input('branchId', $branchId);
        $data = $request->validate([
            'name_en' => 'required|string|max:80',
            'name_fi' => 'required|string|max:80',
            'price' => 'required|numeric|min:0',
            'isActive' => 'nullable|boolean',
        ]);
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }
        $coll = $branchId
            ? "restaurants/{$restaurantId}/branches/{$branchId}/sizes"
            : "restaurants/{$restaurantId}/sizes";
        $firebase->updateDocument($coll, $id, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'price' => (float)$data['price'],
            'isActive' => (bool)($data['isActive'] ?? true),
        ]);
        return redirect()->route('menu.sizes.index')->with('status', 'Size updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->input('branchId', $branchId);
        if ($role === 'branch_admin' && !$branchId) {
            return redirect()->route('menu.sizes.index')->with('status', 'Select a branch to delete sizes.');
        }
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }
        $coll = $branchId
            ? "restaurants/{$restaurantId}/branches/{$branchId}/sizes"
            : "restaurants/{$restaurantId}/sizes";
        $firebase->deleteDocument($coll, $id);
        return redirect()->route('menu.sizes.index')->with('status', 'Size deleted');
    }
}
