<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class IngredientsController extends Controller
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
        $ingredients = [];
        $allBranches = false;
        $lang = $request->session()->get('ui_lang','en');
        if ($branchId) {
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients");
            $docs = $resp['documents'] ?? [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $ing = [
                    'id' => $id,
                    'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                              ?? ($f['name']['stringValue'] ?? ''),
                    'description' => ($lang==='fi' ? ($f['description_fi']['stringValue'] ?? null) : ($f['description_en']['stringValue'] ?? null))
                                     ?? ($f['description']['stringValue'] ?? ''),
                ];
                // Load sub-ingredients under this ingredient
                $sresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$id}/subingredients");
                $sdocs = $sresp['documents'] ?? [];
                $subs = [];
                foreach ($sdocs as $sd) {
                    $sid = Str::afterLast($sd['name'], '/');
                    $sf = $sd['fields'] ?? [];
                    $subs[] = [
                        'id' => $sid,
                        'name' => ($lang==='fi' ? ($sf['name_fi']['stringValue'] ?? null) : ($sf['name_en']['stringValue'] ?? null))
                                  ?? ($sf['name']['stringValue'] ?? ''),
                        'description' => ($lang==='fi' ? ($sf['description_fi']['stringValue'] ?? null) : ($sf['description_en']['stringValue'] ?? null))
                                         ?? ($sf['description']['stringValue'] ?? ''),
                        'price' => (float)($sf['price']['doubleValue'] ?? ($sf['price']['integerValue'] ?? 0)),
                    ];
                }
                $ing['subs'] = $subs;
                $ingredients[] = $ing;
            }
        } else {
            $allBranches = true;
            $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $bdocs = $bresp['documents'] ?? [];
            foreach ($bdocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branchName = $bf['name']['stringValue'] ?? $bid;
                $iresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bid}/ingredients");
                foreach (($iresp['documents'] ?? []) as $doc) {
                    $id = Str::afterLast($doc['name'], '/');
                    $f = $doc['fields'] ?? [];
                    $ing = [
                        'id' => $id,
                        'name' => ($lang==='fi' ? ($f['name_fi']['stringValue'] ?? null) : ($f['name_en']['stringValue'] ?? null))
                                  ?? ($f['name']['stringValue'] ?? ''),
                        'description' => ($lang==='fi' ? ($f['description_fi']['stringValue'] ?? null) : ($f['description_en']['stringValue'] ?? null))
                                         ?? ($f['description']['stringValue'] ?? ''),
                        'branchId' => $bid,
                        'branchName' => $branchName,
                    ];
                    // Load sub-ingredients for this ingredient in that branch
                    $sresp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bid}/ingredients/{$id}/subingredients");
                    $sdocs = $sresp['documents'] ?? [];
                    $subs = [];
                    foreach ($sdocs as $sd) {
                        $sid = Str::afterLast($sd['name'], '/');
                        $sf = $sd['fields'] ?? [];
                        $subs[] = [
                            'id' => $sid,
                            'name' => ($lang==='fi' ? ($sf['name_fi']['stringValue'] ?? null) : ($sf['name_en']['stringValue'] ?? null))
                                      ?? ($sf['name']['stringValue'] ?? ''),
                            'description' => ($lang==='fi' ? ($sf['description_fi']['stringValue'] ?? null) : ($sf['description_en']['stringValue'] ?? null))
                                             ?? ($sf['description']['stringValue'] ?? ''),
                            'price' => (float)($sf['price']['doubleValue'] ?? ($sf['price']['integerValue'] ?? 0)),
                        ];
                    }
                    $ing['subs'] = $subs;
                    $ingredients[] = $ing;
                }
            }
        }
        // Build branches list for selector in top bar
        $branches = [];
        $currentBranchId = $branchId;
        $bresp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        foreach (($bresp['documents'] ?? []) as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $bf = $bd['fields'] ?? [];
            $branches[] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
            ];
        }
        return view('admin.menu.ingredients.index', compact('ingredients','allBranches','branches','currentBranchId'));
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
            return view('admin.menu.ingredients.create', compact('restaurants','branches','selectedRestaurantId'));
        }
        if ($role === 'restaurant_admin') {
            if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
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
            return view('admin.menu.ingredients.create', compact('branches'));
        }
        // branch_admin: uses implicit session restaurant/branch
        if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
        return view('admin.menu.ingredients.create');
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'restaurantId' => $role === 'admin' ? 'required|string' : 'nullable|string',
            'branchId' => $role === 'admin' ? 'required|string' : ($role === 'restaurant_admin' ? 'required|string' : 'nullable|string'),
        ]);
        if ($role === 'admin') {
            $restaurantId = $data['restaurantId'];
            $branchId = $data['branchId'];
        } elseif ($role === 'restaurant_admin') {
            if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
            $branchId = $data['branchId'];
        } else {
            if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
            if (!$branchId) { return redirect()->route('menu.ingredients.index')->with('status', 'Select a branch to add ingredients.'); }
        }
        $coll = "restaurants/{$restaurantId}/branches/{$branchId}/ingredients";
        $id = 'ing_' . Str::random(6);
        $firebase->createDocument($coll, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
        ], $id);
        return redirect()->route('menu.ingredients.index')->with('status', 'Ingredient created');
    }

    public function edit(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        // allow branchId override via query when listing all branches
        $branchId = $request->query('branchId', $branchId);
        if ($role === 'branch_admin' && !$branchId) {
            return redirect()->route('menu.ingredients.index')->with('status', 'Select a branch to edit ingredients.');
        }
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $id);
        $f = $doc['fields'] ?? [];
        $ingredient = [
            'id' => $id,
            'name' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_fi' => $f['name_fi']['stringValue'] ?? '',
            'description' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_en' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_fi' => $f['description_fi']['stringValue'] ?? '',
        ];
        return view('admin.menu.ingredients.edit', compact('ingredient'));
    }

    public function update(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->input('branchId', $branchId);
        if ($role === 'branch_admin' && !$branchId) {
            return redirect()->route('menu.ingredients.index')->with('status', 'Select a branch to update ingredients.');
        }
        if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
        ]);
        $payload = [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
        ];
        $existing = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $id);
        $f = $existing['fields'] ?? [];
        $decode = function($v) use (&$decode) { if (!is_array($v)) return $v; if (isset($v['stringValue'])) return $v['stringValue']; if (isset($v['integerValue'])) return (int)$v['integerValue']; if (isset($v['doubleValue'])) return (float)$v['doubleValue']; if (isset($v['booleanValue'])) return (bool)$v['booleanValue']; if (isset($v['nullValue'])) return null; if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']); if (isset($v['mapValue']['fields'])) { $out=[]; foreach ($v['mapValue']['fields'] as $kk=>$vv){ $out[$kk]=$decode($vv);} return $out;} return $v; };
        $before = [];
        $after = [];
        foreach ($payload as $k=>$v) { if (array_key_exists($k,$f)) { $before[$k]=$decode($f[$k]); } $after[$k]=$v; }
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $id, $payload);
        return redirect()->route('menu.ingredients.index')->with('status', 'Ingredient updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $id)
    {
        [$restaurantId, $branchId, $role] = $this->ctx($request);
        $branchId = $request->input('branchId', $branchId);
        if ($role === 'branch_admin' && !$branchId) {
            return redirect()->route('menu.ingredients.index')->with('status', 'Select a branch to delete ingredients.');
        }
        if (!$restaurantId) { return redirect()->route('settings.context')->with('status', 'Select restaurant first.'); }
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients", $id);
        return redirect()->route('menu.ingredients.index')->with('status', 'Ingredient deleted');
    }

    public function createSub(Request $request, string $ingredientId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // allow branch override when coming from All Branches view
        $branchId = $request->query('branchId', $branchId);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        return view('admin.menu.ingredients.sub-create', compact('ingredientId'));
    }

    public function storeSub(Request $request, FirebaseService $firebase, string $ingredientId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // allow branch override from hidden input
        $branchId = $request->input('branchId', $branchId);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
        ]);
        $coll = "restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingredientId}/subingredients";
        $id = 'sub_' . Str::random(6);
        $firebase->createDocument($coll, [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'price' => (float)$data['price'],
        ], $id);
        return redirect()->route('menu.ingredients.index')->with('status', 'Sub-ingredient created');
    }

    public function editSub(Request $request, FirebaseService $firebase, string $ingredientId, string $subId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // allow branch override from query (all-branches view)
        $branchId = $request->query('branchId', $branchId);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingredientId}/subingredients", $subId);
        $f = $doc['fields'] ?? [];
        $sub = [
            'id' => $subId,
            'ingredientId' => $ingredientId,
            'name' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_en' => $f['name_en']['stringValue'] ?? ($f['name']['stringValue'] ?? ''),
            'name_fi' => $f['name_fi']['stringValue'] ?? '',
            'description' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_en' => $f['description_en']['stringValue'] ?? ($f['description']['stringValue'] ?? ''),
            'description_fi' => $f['description_fi']['stringValue'] ?? '',
            'price' => (float)($f['price']['doubleValue'] ?? ($f['price']['integerValue'] ?? 0)),
        ];
        return view('admin.menu.ingredients.sub-edit', compact('sub'));
    }

    public function updateSub(Request $request, FirebaseService $firebase, string $ingredientId, string $subId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // allow branch override via hidden input
        $branchId = $request->input('branchId', $branchId);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $data = $request->validate([
            'name_en' => 'required|string|max:120',
            'name_fi' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_fi' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
        ]);
        $payload = [
            'name' => $data['name_en'],
            'name_en' => $data['name_en'],
            'name_fi' => $data['name_fi'],
            'description' => $data['description_en'] ?? '',
            'description_en' => $data['description_en'] ?? '',
            'description_fi' => $data['description_fi'] ?? '',
            'price' => (float)$data['price'],
        ];
        $existing = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingredientId}/subingredients", $subId);
        $f = $existing['fields'] ?? [];
        $decode = function($v) use (&$decode) { if (!is_array($v)) return $v; if (isset($v['stringValue'])) return $v['stringValue']; if (isset($v['integerValue'])) return (int)$v['integerValue']; if (isset($v['doubleValue'])) return (float)$v['doubleValue']; if (isset($v['booleanValue'])) return (bool)$v['booleanValue']; if (isset($v['nullValue'])) return null; if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']); if (isset($v['mapValue']['fields'])) { $out=[]; foreach ($v['mapValue']['fields'] as $kk=>$vv){ $out[$kk]=$decode($vv);} return $out;} return $v; };
        $before = [];
        $after = [];
        foreach ($payload as $k=>$v) { if (array_key_exists($k,$f)) { $before[$k]=$decode($f[$k]); } $after[$k]=$v; }
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingredientId}/subingredients", $subId, $payload);
        return redirect()->route('menu.ingredients.index')->with('status', 'Sub-ingredient updated');
    }

    public function destroySub(Request $request, FirebaseService $firebase, string $ingredientId, string $subId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        // allow branch override via hidden input
        $branchId = $request->input('branchId', $branchId);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/ingredients/{$ingredientId}/subingredients", $subId);
        return redirect()->route('menu.ingredients.index')->with('status', 'Sub-ingredient deleted');
    }
}
