<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class LounasHourController extends Controller
{
    protected function ctx(Request $request): array
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        return [$restaurantId, $branchId];
    }

    protected function authorizeManage()
    {
        $role = session('role');
        if (!in_array($role, ['admin','restaurant_admin','branch_admin'], true)) {
            abort(403, 'Not authorized');
        }
    }

    public function index(Request $request, FirebaseService $firebase)
    {
        $this->authorizeManage();
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

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
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/lounas_hours");
            $docs = $resp['documents'] ?? [];
            $items = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $items[] = [
                    'id' => $id,
                    'dayOfWeek' => (int)($f['dayOfWeek']['integerValue'] ?? 0),
                    'startTime' => $f['startTime']['stringValue'] ?? '11:00',
                    'endTime' => $f['endTime']['stringValue'] ?? '15:00',
                    'active' => (bool)($f['active']['booleanValue'] ?? true),
                ];
            }
            usort($items, fn($a,$b)=>$a['dayOfWeek']<=>$b['dayOfWeek']);
            return view('admin.lounas.index', [
                'mode' => 'single',
                'items' => $items,
                'branches' => $branches,
                'currentBranchId' => $branchId,
                'restaurantId' => $restaurantId,
            ]);
        }

        $branchItems = [];
        foreach ($branches as $b) {
            $bId = $b['id'];
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bId}/lounas_hours");
            $docs = $resp['documents'] ?? [];
            $items = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $items[] = [
                    'id' => $id,
                    'dayOfWeek' => (int)($f['dayOfWeek']['integerValue'] ?? 0),
                    'startTime' => $f['startTime']['stringValue'] ?? '11:00',
                    'endTime' => $f['endTime']['stringValue'] ?? '15:00',
                    'active' => (bool)($f['active']['booleanValue'] ?? true),
                ];
            }
            usort($items, fn($a,$b)=>$a['dayOfWeek']<=>$b['dayOfWeek']);
            $branchItems[] = [
                'branch' => $b,
                'items' => $items,
            ];
        }
        return view('admin.lounas.index', [
            'mode' => 'all',
            'branchItems' => $branchItems,
            'branches' => $branches,
            'currentBranchId' => null,
            'restaurantId' => $restaurantId,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage();
        $role = session('role');
        $restaurants = [];
        $branches = [];
        $selectedRestaurantId = null;
        if ($role === 'admin') {
            // Load restaurants and branches for selected restaurant
            $firebase = app(\App\Services\FirebaseService::class);
            $restResp = $firebase->getCollection('restaurants');
            foreach (($restResp['documents'] ?? []) as $doc) {
                $rid = \Illuminate\Support\Str::afterLast($doc['name'], '/');
                $rf = $doc['fields'] ?? [];
                $restaurants[] = ['id' => $rid, 'name' => $rf['name']['stringValue'] ?? $rid];
            }
            $selectedRestaurantId = $request->query('restaurantId') ?? (session('restaurantId') ?: ($restaurants[0]['id'] ?? null));
            if ($selectedRestaurantId) {
                $bResp = $firebase->getCollection("restaurants/{$selectedRestaurantId}/branches");
                foreach (($bResp['documents'] ?? []) as $bd) {
                    $bid = \Illuminate\Support\Str::afterLast($bd['name'], '/');
                    $bf = $bd['fields'] ?? [];
                    $branches[] = ['id' => $bid, 'name' => $bf['name']['stringValue'] ?? $bid];
                }
            }
            return view('admin.lounas.create', compact('restaurants','branches','selectedRestaurantId','role'));
        }
        if ($role === 'restaurant_admin') {
            $firebase = app(\App\Services\FirebaseService::class);
            [$restaurantId, ] = $this->ctx($request);
            if (!$restaurantId) {
                // Try to derive restaurantId from user profile
                $uid = session('firebase_user.uid');
                if ($uid) {
                    $userDoc = $firebase->getDocument('users', $uid);
                    $uf = $userDoc['fields'] ?? [];
                    $restaurantId = $uf['restaurantId']['stringValue'] ?? null;
                }
            }
            $selectedRestaurantId = $restaurantId;
            $bResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            foreach (($bResp['documents'] ?? []) as $bd) {
                $bid = \Illuminate\Support\Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branches[] = ['id' => $bid, 'name' => $bf['name']['stringValue'] ?? $bid];
            }
            return view('admin.lounas.create', compact('branches','selectedRestaurantId','role'));
        }
        // branch_admin
        return view('admin.lounas.create', ['role' => $role]);
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $this->authorizeManage();
        $baseRules = [
            'dayOfWeek' => 'required|integer|min:0|max:6',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
            'active' => 'nullable|boolean',
        ];
        $role = session('role');
        if ($role === 'admin') {
            $baseRules['restaurantId'] = 'required|string';
            $baseRules['branchId'] = 'required|string';
        } elseif ($role === 'restaurant_admin') {
            $baseRules['branchId'] = 'required|string';
        }
        $data = $request->validate($baseRules);
        [$sessionRestaurantId, $sessionBranchId] = $this->ctx($request);
        $restaurantId = $sessionRestaurantId;
        $branchId = $sessionBranchId;
        if ($role === 'admin') {
            $restaurantId = $data['restaurantId'];
            $branchId = $data['branchId'];
        } elseif ($role === 'restaurant_admin') {
            // derive restaurantId if missing in session
            if (!$restaurantId) {
                $uid = session('firebase_user.uid');
                if ($uid) {
                    $userDoc = $firebase->getDocument('users', $uid);
                    $uf = $userDoc['fields'] ?? [];
                    $restaurantId = $uf['restaurantId']['stringValue'] ?? null;
                }
            }
            $branchId = $data['branchId'];
        }
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $id = 'lh_' . Str::random(6);
        $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/lounas_hours", [
            'dayOfWeek' => (int)$data['dayOfWeek'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime'],
            'active' => (bool)($data['active'] ?? true),
        ], $id);
        return redirect()->route('lounas.index')->with('status', 'Lounas hour added');
    }

    public function edit(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $id)
    {
        $this->authorizeManage();
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/lounas_hours", $id);
        $f = $doc['fields'] ?? [];
        if (empty($f)) { return redirect()->route('lounas.index')->with('status', 'Not found'); }
        $item = [
            'id' => $id,
            'dayOfWeek' => (int)($f['dayOfWeek']['integerValue'] ?? 0),
            'startTime' => $f['startTime']['stringValue'] ?? '11:00',
            'endTime' => $f['endTime']['stringValue'] ?? '15:00',
            'active' => (bool)($f['active']['booleanValue'] ?? true),
        ];
        return view('admin.lounas.edit', [
            'item' => $item,
            'restaurantId' => $restaurantId,
            'branchId' => $branchId,
        ]);
    }

    public function update(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $id)
    {
        $this->authorizeManage();
        $data = $request->validate([
            'dayOfWeek' => 'required|integer|min:0|max:6',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
            'active' => 'nullable|boolean',
        ]);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/lounas_hours", $id, [
            'dayOfWeek' => (int)$data['dayOfWeek'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime'],
            'active' => (bool)($data['active'] ?? true),
        ]);
        return redirect()->route('lounas.index')->with('status', 'Lounas hour updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $id)
    {
        $this->authorizeManage();
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/lounas_hours", $id);
        return redirect()->route('lounas.index')->with('status', 'Lounas hour deleted');
    }
}
