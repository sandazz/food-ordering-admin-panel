<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

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
            'taxRate' => 'nullable|numeric|min:0',
            'serviceCharge' => 'nullable|numeric|min:0',
            'status' => 'required|string',
        ]);
        $id = 'resto_' . Str::random(6);
        $firebase->createDocument('restaurants', [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'logoUrl' => $data['logoUrl'] ?? '',
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
            'taxRate' => 'nullable|numeric|min:0',
            'serviceCharge' => 'nullable|numeric|min:0',
            'status' => 'required|string',
        ]);
        $firebase->updateDocument('restaurants', $restaurantId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'logoUrl' => $data['logoUrl'] ?? '',
            'taxRate' => (float)($data['taxRate'] ?? 0), 
            'serviceCharge' => (float)($data['serviceCharge'] ?? 0),
            'status' => $data['status'],
        ]);
        return redirect()->route('settings.restaurants')->with('status', 'Restaurant updated');
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
        ]);
        $branchId = 'branch_' . Str::random(6);
        $firebase->createDocument("restaurants/{$restaurantId}/branches", [
            'name' => $data['name'],
            'contact' => $data['contact'] ?? '',
            'status' => $data['status'],
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
        ]);
        $firebase->updateDocument("restaurants/{$restaurantId}/branches", $branchId, [
            'name' => $data['name'],
            'contact' => $data['contact'] ?? '',
            'status' => $data['status'],
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
