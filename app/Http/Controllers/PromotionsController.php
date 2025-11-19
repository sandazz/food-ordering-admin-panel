<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class PromotionsController extends Controller
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
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/promotions");
            $docs = $resp['documents'] ?? [];
            $promotions = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $promotions[] = $this->mapPromotion($id, $f);
            }
            return view('admin.promotions.index', [
                'mode' => 'single',
                'promotions' => $promotions,
                'branches' => $branches,
                'currentBranchId' => $branchId,
                'restaurantId' => $restaurantId,
            ]);
        }

        $branchPromotions = [];
        foreach ($branches as $b) {
            $bId = $b['id'];
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bId}/promotions");
            $docs = $resp['documents'] ?? [];
            $promos = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $promos[] = $this->mapPromotion($id, $f);
            }
            $branchPromotions[] = [
                'branch' => $b,
                'promotions' => $promos,
            ];
        }
        return view('admin.promotions.index', [
            'mode' => 'all',
            'branchPromotions' => $branchPromotions,
            'branches' => $branches,
            'currentBranchId' => null,
            'restaurantId' => $restaurantId,
        ]);
    }

    public function create(Request $request, FirebaseService $firebase)
    {
        if (session('role') === 'admin') {
            // Super admin: show restaurant + branch selectors
            $restaurants = $this->listRestaurants($firebase);
            $selectedRestaurantId = $request->query('restaurantId') ?? ($restaurants[0]['id'] ?? null);
            $branches = [];
            if ($selectedRestaurantId) {
                $branches = $this->listBranches($firebase, $selectedRestaurantId);
            }
            return view('admin.promotions.create', compact('restaurants','branches','selectedRestaurantId'));
        }

        if (session('role') === 'restaurant_admin') {
            [$restaurantId, ] = $this->ctx($request);
            if (!$restaurantId) {
                return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
            }
            $branches = $this->listBranches($firebase, $restaurantId);
            return view('admin.promotions.create', compact('branches'));
        }

        // Branch admin and others: require full context
        [$restaurantId, $branchId] = $this->ctx($request);
        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }
        return view('admin.promotions.create');
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $baseRules = [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:5120',
            'status' => 'required|string',
            'discountType' => 'required|string|in:percent,fixed',
            'discountValue' => 'required|numeric|min:0',
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
        ];
        if (session('role') === 'admin') {
            $baseRules['restaurantId'] = 'required|string';
            $baseRules['branchId'] = 'required|string';
        } elseif (session('role') === 'restaurant_admin') {
            $baseRules['branchId'] = 'required|string';
        }
        $data = $request->validate($baseRules);

        if (session('role') === 'admin') {
            $restaurantId = $data['restaurantId'];
            $branchId = $data['branchId'];
        } elseif (session('role') === 'restaurant_admin') {
            [$restaurantId, ] = $this->ctx($request);
            $branchId = $data['branchId'];
        } else {
            [$restaurantId, $branchId] = $this->ctx($request);
        }

        if (!$restaurantId || !$branchId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant and branch first.');
        }

        $imageUrl = '';
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'promotions');
        }

        $id = 'promo_' . Str::random(6);
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'imageUrl' => $imageUrl,
            'status' => $data['status'],
            'discount' => [
                'type' => $data['discountType'],
                'value' => (float)$data['discountValue'],
            ],
            'startsAt' => !empty($data['startsAt']) ? (string)date('c', strtotime($data['startsAt'])) : '',
            'endsAt' => !empty($data['endsAt']) ? (string)date('c', strtotime($data['endsAt'])) : '',
            'createdAt' => now()->toIso8601String(),
        ];
        $firebase->createDocument("restaurants/{$restaurantId}/branches/{$branchId}/promotions", $payload, $id);
        return redirect()->route('promotions.index')->with('status', 'Promotion created');
    }

    public function edit(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $promoId)
    {
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}/promotions", $promoId);
        $f = $doc['fields'] ?? [];
        $promotion = $this->mapPromotion($promoId, $f);
        return view('admin.promotions.edit', [
            'promotion' => $promotion,
            'restaurantId' => $restaurantId,
            'branchId' => $branchId,
        ]);
    }

    public function update(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $promoId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:5120',
            'status' => 'required|string',
            'discountType' => 'required|string|in:percent,fixed',
            'discountValue' => 'required|numeric|min:0',
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
        ]);
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $this->storePublicUpload($request->file('image'), 'promotions');
        }
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'],
            'discount' => [
                'type' => $data['discountType'],
                'value' => (float)$data['discountValue'],
            ],
            'startsAt' => !empty($data['startsAt']) ? (string)date('c', strtotime($data['startsAt'])) : '',
            'endsAt' => !empty($data['endsAt']) ? (string)date('c', strtotime($data['endsAt'])) : '',
        ];
        if (!is_null($imageUrl)) {
            $payload['imageUrl'] = $imageUrl;
        }
        $firebase->updateDocument("restaurants/{$restaurantId}/branches/{$branchId}/promotions", $promoId, $payload);
        return redirect()->route('promotions.index')->with('status', 'Promotion updated');
    }

    public function destroy(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId, string $promoId)
    {
        $firebase->deleteDocument("restaurants/{$restaurantId}/branches/{$branchId}/promotions", $promoId);
        return redirect()->route('promotions.index')->with('status', 'Promotion deleted');
    }

    private function mapPromotion(string $id, array $f): array
    {
        $startsAt = $f['startsAt']['timestampValue'] ?? ($f['startsAt']['stringValue'] ?? '');
        $endsAt = $f['endsAt']['timestampValue'] ?? ($f['endsAt']['stringValue'] ?? '');
        return [
            'id' => $id,
            'name' => $f['name']['stringValue'] ?? '',
            'description' => $f['description']['stringValue'] ?? '',
            'imageUrl' => $f['imageUrl']['stringValue'] ?? '',
            'status' => $f['status']['stringValue'] ?? 'inactive',
            'discount' => [
                'type' => $f['discount']['mapValue']['fields']['type']['stringValue'] ?? 'percent',
                'value' => isset($f['discount']['mapValue']['fields']['value']['doubleValue'])
                    ? (float)$f['discount']['mapValue']['fields']['value']['doubleValue']
                    : (float)($f['discount']['mapValue']['fields']['value']['integerValue'] ?? 0),
            ],
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
        ];
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
                'name' => $f['name']['stringValue'] ?? $id,
            ];
        }
        return $branches;
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
}
