<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

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
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $roles = ['branch_admin','chef','cashier','server'];
        $permissions = ['manage_orders','manage_menu','view_reports','update_order_status','create_orders','process_payments'];
        return view('admin.staff.create', compact('roles','permissions'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role' => 'required|string',
            'isActive' => 'nullable|boolean',
            'permissions' => 'array',
        ]);
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        $id = 'staff_' . Str::random(6);
        $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/staff", [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'isActive' => (bool)($data['isActive'] ?? true),
            'permissions' => array_values($data['permissions'] ?? []),
        ], $id);
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
            'permissions' => array_map(function($v){ return $v['stringValue'] ?? ''; }, $f['permissions']['arrayValue']['values'] ?? []),
        ];
        $roles = ['branch_admin','chef','cashier','server'];
        $permissions = ['manage_orders','manage_menu','view_reports','update_order_status','create_orders','process_payments'];
        return view('admin.staff.edit', compact('staff','roles','permissions'));
    }

    public function update(Request $request, FirebaseService $firebase, string $staffId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role' => 'required|string',
            'isActive' => 'nullable|boolean',
            'permissions' => 'array',
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
            'permissions' => array_values($data['permissions'] ?? []),
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
