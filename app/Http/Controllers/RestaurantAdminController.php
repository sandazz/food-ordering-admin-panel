<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class RestaurantAdminController extends Controller
{
    protected function ensureSuper(Request $request)
    {
        if (session('role') !== 'admin') {
            abort(403, 'Only super admin can manage restaurant admins');
        }
    }

    public function index(Request $request, FirebaseService $firebase)
    {
        $this->ensureSuper($request);
        $resp = $firebase->getCollection('users');
        $docs = $resp['documents'] ?? [];
        $admins = [];
        foreach ($docs as $doc) {
            $f = $doc['fields'] ?? [];
            if (($f['role']['stringValue'] ?? '') === 'restaurant_admin') {
                $admins[] = [
                    'uid' => Str::afterLast($doc['name'], '/'),
                    'name' => $f['name']['stringValue'] ?? '',
                    'email' => $f['email']['stringValue'] ?? '',
                    'restaurantId' => $f['restaurantId']['stringValue'] ?? '',
                ];
            }
        }
        // Restaurants for display mapping
        $restaurants = $this->listRestaurants($firebase);
        return view('admin.settings.restaurant_admins.index', compact('admins', 'restaurants'));
    }

    public function create(Request $request, FirebaseService $firebase)
    {
        $this->ensureSuper($request);
        $restaurants = $this->listRestaurants($firebase);
        return view('admin.settings.restaurant_admins.create', compact('restaurants'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $this->ensureSuper($request);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'restaurantId' => 'required|string',
        ]);

        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (! $apiKey) {
            return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).'])->withInput();
        }

        // Create Firebase Auth user
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

        // Store user profile in Firestore
        $firebase->createDocument('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'restaurant_admin',
            'restaurantId' => $data['restaurantId'],
            'createdAt' => now()->toIso8601String(),
        ], $uid);

        return redirect()->route('settings.restaurant_admins')->with('status', 'Restaurant admin created');
    }

    public function edit(Request $request, FirebaseService $firebase, string $userId)
    {
        $this->ensureSuper($request);
        $doc = $firebase->getDocument('users', $userId);
        $f = $doc['fields'] ?? [];
        $admin = [
            'uid' => $userId,
            'name' => $f['name']['stringValue'] ?? '',
            'email' => $f['email']['stringValue'] ?? '',
            'restaurantId' => $f['restaurantId']['stringValue'] ?? '',
        ];
        $restaurants = $this->listRestaurants($firebase);
        return view('admin.settings.restaurant_admins.edit', compact('admin', 'restaurants'));
    }

    public function update(Request $request, FirebaseService $firebase, string $userId)
    {
        $this->ensureSuper($request);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email',
            'restaurantId' => 'required|string',
            'password' => 'nullable|string|min:6',
        ]);

        // Update Firestore doc
        $firebase->updateDocument('users', $userId, [
            'name' => $data['name'],
            'email' => $data['email'],
            'restaurantId' => $data['restaurantId'],
        ]);

        // Optionally update password via Firebase Auth if provided
        if (!empty($data['password'])) {
            $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
            if ($apiKey) {
                // Need idToken to change password; without admin SDK, this is non-trivial.
                // Skipping password change here; recommend reset via email in UI.
            }
        }

        return redirect()->route('settings.restaurant_admins')->with('status', 'Restaurant admin updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $userId)
    {
        $this->ensureSuper($request);
        $firebase->deleteDocument('users', $userId);
        // Note: Deleting Firebase Auth user requires Admin SDK; not handled here.
        return redirect()->route('settings.restaurant_admins')->with('status', 'Restaurant admin removed (Auth account not deleted)');
    }

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
                'name' => $f['name']['stringValue'] ?? $id,
            ];
        }
        return $restaurants;
    }
}
