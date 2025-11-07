<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    // Landing for settings
    public function index()
    {
        return redirect()->route('settings.context');
    }

    // Context selection: choose restaurant and branch
    public function context(Request $request, FirebaseService $firebase)
    {
        $restaurants = $this->listRestaurants($firebase);
        $selectedRestaurantId = $request->query('restaurantId') ?? session('restaurantId');
        $branches = [];
        if ($selectedRestaurantId) {
            $branches = $this->listBranches($firebase, $selectedRestaurantId);
        }
        return view('admin.settings.context', compact('restaurants', 'branches', 'selectedRestaurantId'));
    }

    public function saveContext(Request $request)
    {
        $data = $request->validate([
            'restaurantId' => 'required|string',
        ]);
        $request->session()->put('restaurantId', $data['restaurantId']);
        // Clearing branch selection when restaurant changes
        $request->session()->forget('branchId');
        return redirect()->route('settings.context')->with('status', 'Restaurant saved');
    }

    public function setBranch(Request $request)
    {
        $data = $request->validate([
            'branchId' => 'required|string',
        ]);
        // Ensure restaurant is selected first
        if (!session('restaurantId')) {
            return redirect()->route('settings.context')->with('status', 'Select a restaurant first.');
        }
        $request->session()->put('branchId', $data['branchId']);
        return back()->with('status', 'Branch selected');
    }

    public function clearBranch(Request $request)
    {
        $request->session()->forget('branchId');
        return back()->with('status', 'Branch cleared (showing all branches)');
    }

    // System Settings: payments, features, pricing, localization, GDPR
    public function system(Request $request, FirebaseService $firebase)
    {
        $doc = $firebase->getDocument('system_settings', 'global');
        $f = $doc['fields'] ?? [];
        $settings = [
            'payments' => [
                'gateway' => $f['payments']['mapValue']['fields']['gateway']['stringValue'] ?? 'manual',
                'enabled' => isset($f['payments']['mapValue']['fields']['enabled']['booleanValue']) ? (bool)$f['payments']['mapValue']['fields']['enabled']['booleanValue'] : true,
            ],
            'features' => [
                'delivery' => isset($f['features']['mapValue']['fields']['delivery']['booleanValue']) ? (bool)$f['features']['mapValue']['fields']['delivery']['booleanValue'] : true,
                'pickup' => isset($f['features']['mapValue']['fields']['pickup']['booleanValue']) ? (bool)$f['features']['mapValue']['fields']['pickup']['booleanValue'] : true,
            ],
            'pricing' => [
                'taxRate' => isset($f['pricing']['mapValue']['fields']['taxRate']['doubleValue']) ? (float)$f['pricing']['mapValue']['fields']['taxRate']['doubleValue'] : (float)($f['pricing']['mapValue']['fields']['taxRate']['integerValue'] ?? 0),
                'serviceCharge' => isset($f['pricing']['mapValue']['fields']['serviceCharge']['doubleValue']) ? (float)$f['pricing']['mapValue']['fields']['serviceCharge']['doubleValue'] : (float)($f['pricing']['mapValue']['fields']['serviceCharge']['integerValue'] ?? 0),
                'deliveryFee' => isset($f['pricing']['mapValue']['fields']['deliveryFee']['doubleValue']) ? (float)$f['pricing']['mapValue']['fields']['deliveryFee']['doubleValue'] : (float)($f['pricing']['mapValue']['fields']['deliveryFee']['integerValue'] ?? 0),
            ],
            'localization' => [
                'default_locale' => $f['localization']['mapValue']['fields']['default_locale']['stringValue'] ?? 'en',
                'locales' => array_map(fn($v)=>$v['stringValue'] ?? 'en', $f['localization']['mapValue']['fields']['locales']['arrayValue']['values'] ?? [['stringValue'=>'en']]),
            ],
            'gdpr' => [
                'consent_required' => isset($f['gdpr']['mapValue']['fields']['consent_required']['booleanValue']) ? (bool)$f['gdpr']['mapValue']['fields']['consent_required']['booleanValue'] : false,
                'retention_days' => (int)($f['gdpr']['mapValue']['fields']['retention_days']['integerValue'] ?? 0),
            ],
        ];
        return view('admin.settings.system', compact('settings'));
    }

    public function saveSystem(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'payments.gateway' => 'required|string',
            'payments.enabled' => 'required|boolean',
            'features.delivery' => 'required|boolean',
            'features.pickup' => 'required|boolean',
            'pricing.taxRate' => 'required|numeric|min:0',
            'pricing.serviceCharge' => 'required|numeric|min:0',
            'pricing.deliveryFee' => 'required|numeric|min:0',
            'localization.default_locale' => 'required|string',
            'localization.locales' => 'nullable|array',
            'localization.locales.*' => 'string',
            'gdpr.consent_required' => 'required|boolean',
            'gdpr.retention_days' => 'nullable|integer|min:0',
        ]);

        $payload = [
            'payments' => [
                'gateway' => data_get($data, 'payments.gateway'),
                'enabled' => (bool)data_get($data, 'payments.enabled'),
            ],
            'features' => [
                'delivery' => (bool)data_get($data, 'features.delivery'),
                'pickup' => (bool)data_get($data, 'features.pickup'),
            ],
            'pricing' => [
                'taxRate' => (float)data_get($data, 'pricing.taxRate'),
                'serviceCharge' => (float)data_get($data, 'pricing.serviceCharge'),
                'deliveryFee' => (float)data_get($data, 'pricing.deliveryFee'),
            ],
            'localization' => [
                'default_locale' => data_get($data, 'localization.default_locale'),
                'locales' => array_values((array) data_get($data, 'localization.locales', [])),
            ],
            'gdpr' => [
                'consent_required' => (bool)data_get($data, 'gdpr.consent_required'),
                'retention_days' => (int) data_get($data, 'gdpr.retention_days', 0),
            ],
        ];

        // Upsert global settings
        $existing = $firebase->getDocument('system_settings', 'global');
        if (!empty($existing['name'])) {
            $firebase->updateDocument('system_settings', 'global', $payload);
        } else {
            $firebase->createDocument('system_settings', $payload, 'global');
        }
        return back()->with('status', 'System settings saved');
    }

    public function gdprDeleteUser(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'userId' => 'required_without:email|string',
            'email' => 'nullable|email',
        ]);
        $deleted = false;
        if (!empty($data['userId'])) {
            // Try deleting from users collection if exists
            $deleted = $firebase->deleteDocument('users', $data['userId']);
        }
        // Log request
        $firebase->createDocument('gdpr_requests', [
            'type' => 'delete_user',
            'userId' => $data['userId'] ?? '',
            'email' => $data['email'] ?? '',
            'status' => $deleted ? 'deleted' : 'requested',
            'createdAt' => now()->toIso8601String(),
        ]);
        return back()->with('status', $deleted ? 'User data deleted' : 'Deletion requested');
    }

    public function exportConsents(Request $request, FirebaseService $firebase)
    {
        $resp = $firebase->getCollection('consent_logs');
        $docs = $resp['documents'] ?? [];
        $rows = [];
        foreach ($docs as $doc) {
            $f = $doc['fields'] ?? [];
            $rows[] = [
                $f['userId']['stringValue'] ?? '',
                $f['action']['stringValue'] ?? '',
                $f['ip']['stringValue'] ?? '',
                $f['userAgent']['stringValue'] ?? '',
                $f['createdAt']['timestampValue'] ?? ($f['createdAt']['stringValue'] ?? ''),
            ];
        }
        $headers = ['userId','action','ip','userAgent','createdAt'];
        $filename = 'consents_' . date('Ymd_His') . '.csv';
        $response = new StreamedResponse(function() use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $r) { fputcsv($handle, $r); }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        return $response;
    }

    // Restaurants CRUD
    public function restaurants(FirebaseService $firebase)
    {
        $restaurants = $this->listRestaurants($firebase);
        return view('admin.settings.restaurants.index', compact('restaurants'));
    }

    public function createRestaurant()
    {
        return view('admin.settings.restaurants.create');
    }

    public function storeRestaurant(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'logoUrl' => 'nullable|url',
            'logo' => 'nullable|image|max:4096',
            'taxRate' => 'nullable|numeric|min:0',
            'serviceCharge' => 'nullable|numeric|min:0',
            'status' => 'required|string',
        ]);
        $id = 'resto_' . Str::random(6);
        $logoUrl = $data['logoUrl'] ?? '';
        if ($request->hasFile('logo')) {
            $logoUrl = $this->storePublicUpload($request->file('logo'), 'restaurants');
        }
        $firebase->createDocument('restaurants', [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'logoUrl' => $logoUrl,
            'taxRate' => (float)($data['taxRate'] ?? 0),
            'serviceCharge' => (float)($data['serviceCharge'] ?? 0),
            'status' => $data['status'],
        ], $id);
        return redirect()->route('settings.restaurants')->with('status', 'Restaurant created');
    }

    public function editRestaurant(FirebaseService $firebase, string $restaurantId)
    {
        $doc = $firebase->getDocument('restaurants', $restaurantId);
        $f = $doc['fields'] ?? [];
        $restaurant = [
            'id' => $restaurantId,
            'name' => $f['name']['stringValue'] ?? '',
            'description' => $f['description']['stringValue'] ?? '',
            'logoUrl' => $f['logoUrl']['stringValue'] ?? '',
            'taxRate' => isset($f['taxRate']['doubleValue']) ? (float)$f['taxRate']['doubleValue'] : (float)($f['taxRate']['integerValue'] ?? 0),
            'serviceCharge' => isset($f['serviceCharge']['doubleValue']) ? (float)$f['serviceCharge']['doubleValue'] : (float)($f['serviceCharge']['integerValue'] ?? 0),
            'status' => $f['status']['stringValue'] ?? 'active',
        ];
        return view('admin.settings.restaurants.edit', compact('restaurant'));
    }

    public function updateRestaurant(Request $request, FirebaseService $firebase, string $restaurantId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'logoUrl' => 'nullable|url',
            'logo' => 'nullable|image|max:4096',
            'taxRate' => 'nullable|numeric|min:0',
            'serviceCharge' => 'nullable|numeric|min:0',
            'status' => 'required|string',
        ]);
        $logoUrl = $data['logoUrl'] ?? '';
        if ($request->hasFile('logo')) {
            $logoUrl = $this->storePublicUpload($request->file('logo'), 'restaurants');
        }
        $firebase->updateDocument('restaurants', $restaurantId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'logoUrl' => $logoUrl,
            'taxRate' => (float)($data['taxRate'] ?? 0), 
            'serviceCharge' => (float)($data['serviceCharge'] ?? 0),
            'status' => $data['status'],
        ]);
        return redirect()->route('settings.context')->with('status', 'Restaurant updated');
    }

    public function destroyRestaurant(FirebaseService $firebase, string $restaurantId)
    {
        $firebase->deleteDocument('restaurants', $restaurantId);
        return redirect()->route('settings.restaurants')->with('status', 'Restaurant deleted');
    }

    // Branches CRUD
    public function branches(FirebaseService $firebase, string $restaurantId)
    {
        $branches = $this->listBranches($firebase, $restaurantId);
        return view('admin.settings.branches.index', compact('branches', 'restaurantId'));
    }

    public function createBranch(string $restaurantId)
    {
        return view('admin.settings.branches.create', compact('restaurantId'));
    }

    public function storeBranch(Request $request, FirebaseService $firebase, string $restaurantId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'contact' => 'nullable|string|max:120',
            'status' => 'required|string',
            'street' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'zipCode' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:120',
            'image' => 'nullable|image|max:4096',
        ]);
        $branchId = 'branch_' . Str::random(6);
        $imageUrl = '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'branches');
        }
        $firebase->createDocument("restaurants/{$restaurantId}/branches", [
            'name' => $data['name'],
            'contact' => $data['contact'] ?? '',
            'status' => $data['status'],
            'imageUrl' => $imageUrl,
            'address' => [
                'street' => $data['street'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'zipCode' => $data['zipCode'] ?? '',
                'country' => $data['country'] ?? '',
            ],
        ], $branchId);
        return redirect()->route('settings.branches', $restaurantId)->with('status', 'Branch created');
    }

    public function editBranch(FirebaseService $firebase, string $restaurantId, string $branchId)
    {
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches", $branchId);
        $f = $doc['fields'] ?? [];
        $branch = [
            'id' => $branchId,
            'name' => $f['name']['stringValue'] ?? '',
            'contact' => $f['contact']['stringValue'] ?? '',
            'status' => $f['status']['stringValue'] ?? 'open',
            'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
            'address' => [
                'street' => $f['address']['mapValue']['fields']['street']['stringValue'] ?? '',
                'city' => $f['address']['mapValue']['fields']['city']['stringValue'] ?? '',
                'state' => $f['address']['mapValue']['fields']['state']['stringValue'] ?? '',
                'zipCode' => $f['address']['mapValue']['fields']['zipCode']['stringValue'] ?? '',
                'country' => $f['address']['mapValue']['fields']['country']['stringValue'] ?? '',
            ],
        ];
        return view('admin.settings.branches.edit', compact('branch','restaurantId'));
    }

    public function updateBranch(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'contact' => 'nullable|string|max:120',
            'status' => 'required|string',
            'street' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'zipCode' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:120',
            'image' => 'nullable|image|max:4096',
        ]);
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'branches');
        }
        $firebase->updateDocument("restaurants/{$restaurantId}/branches", $branchId, [
            'name' => $data['name'],
            'contact' => $data['contact'] ?? '',
            'status' => $data['status'],
            ...(isset($imageUrl) && $imageUrl !== null ? ['imageUrl' => $imageUrl] : []),
            'address' => [
                'street' => $data['street'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'zipCode' => $data['zipCode'] ?? '',
                'country' => $data['country'] ?? '',
            ],
        ]);
        return redirect()->route('settings.branches', $restaurantId)->with('status', 'Branch updated');
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

    public function destroyBranch(FirebaseService $firebase, string $restaurantId, string $branchId)
    {
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches", $branchId);
        return redirect()->route('settings.branches', $restaurantId)->with('status', 'Branch deleted');
    }

    // Helpers
    private function listRestaurants(FirebaseService $firebase): array
    {
        $resp = $firebase->getCollection('restaurants');
        $docs = $resp['documents'] ?? [];
        $restaurants = [];
        foreach ($docs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $restaurants[] = [
                'id' => $id,
                'name' => $f['name']['stringValue'] ?? '',
                'status' => $f['status']['stringValue'] ?? 'active',
            ];
        }
        return $restaurants;
    }

    private function listBranches(FirebaseService $firebase, string $restaurantId): array
    {
        $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $docs = $resp['documents'] ?? [];
        $branches = [];
        foreach ($docs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $branches[] = [
                'id' => $id,
                'name' => $f['name']['stringValue'] ?? '',
                'status' => $f['status']['stringValue'] ?? 'open',
            ];
        }
        return $branches;
    }
}
