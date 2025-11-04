<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class StaffController extends Controller
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

        // Load branches for selector
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
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/staff");
            $docs = $resp['documents'] ?? [];
            $staff = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $staff[] = [
                    'id' => $id,
                    'name' => $f['name']['stringValue'] ?? '',
                    'email' => $f['email']['stringValue'] ?? '',
                    'role' => $f['role']['stringValue'] ?? '',
                    'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
                    'permissions' => array_map(function($v){ return $v['stringValue'] ?? ''; }, $f['permissions']['arrayValue']['values'] ?? []),
                ];
            }
            return view('admin.staff.index', [
                'mode' => 'single',
                'staff' => $staff,
                'branches' => $branches,
                'currentBranchId' => $branchId,
            ]);
        }

        $branchStaff = [];
        foreach ($branches as $b) {
            $bId = $b['id'];
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bId}/staff");
            $docs = $resp['documents'] ?? [];
            $staff = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $staff[] = [
                    'id' => $id,
                    'name' => $f['name']['stringValue'] ?? '',
                    'email' => $f['email']['stringValue'] ?? '',
                    'role' => $f['role']['stringValue'] ?? '',
                    'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
                ];
            }
            $branchStaff[] = [
                'branch' => $b,
                'staff' => $staff,
            ];
        }
        return view('admin.staff.index', [
            'mode' => 'all',
            'branchStaff' => $branchStaff,
            'branches' => $branches,
            'currentBranchId' => null,
        ]);
    }

    public function create(Request $request)
    {
        $roles = ['branch_admin','cashier'];

        if (session('role') === 'admin') {
            // Super admin: show restaurant + branch selectors on the form
            // Load restaurants
            $firebase = app(\App\Services\FirebaseService::class);
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            $restaurants = [];
            foreach ($restaurantsDocs as $doc) {
                $id = \Illuminate\Support\Str::afterLast($doc['name'], '/');
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
                    $bid = \Illuminate\Support\Str::afterLast($bd['name'], '/');
                    $bf = $bd['fields'] ?? [];
                    $branches[] = [
                        'id' => $bid,
                        'name' => $bf['name']['stringValue'] ?? $bid,
                    ];
                }
            }
            // Include restaurant_admin role for super admin
            $roles = array_merge(['restaurant_admin'], $roles);
            return view('admin.staff.create', compact('roles','restaurants','branches','selectedRestaurantId'));
        }

        // Restaurant admin: allow selecting branch on the form (restaurant fixed)
        if (session('role') === 'restaurant_admin') {
            [$restaurantId, ] = $this->ctx($request);
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            $firebase = app(\App\Services\FirebaseService::class);
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            $branches = [];
            foreach ($branchesDocs as $bd) {
                $bid = \Illuminate\Support\Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branches[] = [
                    'id' => $bid,
                    'name' => $bf['name']['stringValue'] ?? $bid,
                ];
            }
            return view('admin.staff.create', compact('roles','branches'));
        }

        // Branch admin and others: require context preselected fully
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $baseRules = [
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role' => 'required|string',
            'isActive' => 'nullable|boolean',
            'password' => 'required|string|min:6',
        ];
        if (session('role') === 'admin') {
            // When creating a restaurant admin, require restaurantId and password only
            if ($request->input('role') === 'restaurant_admin') {
                $baseRules['restaurantId'] = 'required|string';
                $baseRules['password'] = 'required|string|min:6';
            } else {
                $baseRules['restaurantId'] = 'required|string';
                $baseRules['branchId'] = 'required|string';
            }
        } elseif (session('role') === 'restaurant_admin') {
            // Restaurant admin chooses branch on the form
            $baseRules['branchId'] = 'required|string';
        }
        $data = $request->validate($baseRules);

        if (session('role') === 'admin') {
            // Handle Restaurant Admin creation path
            if ($data['role'] === 'restaurant_admin') {
                $restaurantId = $data['restaurantId'];
                // Create Firebase Auth user
                $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
                if (! $apiKey) {
                    return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).'])->withInput();
                }
                $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}";
                $resp = Http::post($url, [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'returnSecureToken' => true,
                ]);
                if ($resp->failed()) {
                    $body = $resp->json();
                    $message = $body['error']['message'] ?? 'Failed to create Firebase user';
                    return back()->withErrors(['firebase' => $message])->withInput();
                }
                $uid = $resp->json('localId');

                // Create Firestore user profile
                $firebase->createDocument('users', [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 'restaurant_admin',
                    'restaurantId' => $restaurantId,
                    'createdAt' => now()->toIso8601String(),
                ], $uid);

                return redirect()->route('settings.restaurant_admins')->with('status', 'Restaurant admin created');
            }

            // Otherwise, normal staff under selected branch
            $restaurantId = $data['restaurantId'];
            $branchId = $data['branchId'];
        } elseif (session('role') === 'restaurant_admin') {
            [$restaurantId, ] = $this->ctx($request);
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            $branchId = $data['branchId'];
        } else {
            [$restaurantId, $branchId] = $this->ctx($request);
            if (!$restaurantId || !$branchId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
            }
        }

        // For branch/staff roles: create Firebase Auth user then save staff doc
        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (! $apiKey) {
            return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).'])->withInput();
        }
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}";
        $authResp = Http::post($url, [
            'email' => $data['email'],
            'password' => $data['password'],
            'returnSecureToken' => true,
        ]);
        if ($authResp->failed()) {
            $body = $authResp->json();
            $message = $body['error']['message'] ?? 'Failed to create Firebase user';
            return back()->withErrors(['firebase' => $message])->withInput();
        }
        $uid = $authResp->json('localId');

        $id = 'staff_' . Str::random(6);
        $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/staff", [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'uid' => $uid,
            'isActive' => (bool)($data['isActive'] ?? true),
        ], $id);

        // Mirror minimal user profile at top-level for unified login lookup
        $firebase->createDocument('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'restaurantId' => $restaurantId,
            'branchId' => $branchId,
            'createdAt' => now()->toIso8601String(),
        ], $uid);
        return redirect()->route('staff.index')->with('status', 'Staff created');
    }

    public function edit(Request $request, FirebaseService $firebase, string $staffId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/staff", $staffId);
        $f = $doc['fields'] ?? [];
        $staff = [
            'id' => $staffId,
            'name' => $f['name']['stringValue'] ?? '',
            'email' => $f['email']['stringValue'] ?? '',
            'role' => $f['role']['stringValue'] ?? '',
            'isActive' => (bool)($f['isActive']['booleanValue'] ?? true),
        ];
        $roles = ['branch_admin','cashier'];;
        return view('admin.staff.edit', compact('staff','roles'));
    }

    public function update(Request $request, FirebaseService $firebase, string $staffId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role' => 'required|string',
            'isActive' => 'nullable|boolean',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/staff", $staffId, [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'isActive' => (bool)($data['isActive'] ?? true),
        ]);
        return redirect()->route('staff.index')->with('status', 'Staff updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $staffId)
    {
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/staff", $staffId);
        return redirect()->route('staff.index')->with('status', 'Staff deleted');
    }
}
