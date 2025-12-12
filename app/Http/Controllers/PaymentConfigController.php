<?php
namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PaymentConfigController extends Controller
{
    // Branch documents are stored under: restaurants/{restaurantId}/branches/{branchId}
    private string $branchCollectionBase = 'restaurants';

    public function index(Request $request, FirebaseService $firebase)
    {
        $role = (string) session('role');
        if (in_array($role, ['restaurant_admin','branch_admin'], true)) {
            abort(403, 'Only super admin can list all payment configs');
        }
        // Iterate restaurants and their branches to collect paymentConfig maps
        $restaurantsResp = $firebase->getCollection('restaurants');
        $restaurants = $restaurantsResp['documents'] ?? [];
        $items = [];
        foreach ($restaurants as $restDoc) {
            $restId = Str::afterLast($restDoc['name'], '/');
            $branchesResp = $firebase->getCollection("restaurants/{$restId}/branches");
            $branchDocs = $branchesResp['documents'] ?? [];
            foreach ($branchDocs as $bdoc) {
                $branchId = Str::afterLast($bdoc['name'], '/');
                $fields = $bdoc['fields'] ?? [];
                if (!empty($fields['paymentConfig'])) {
                    $pcFields = $fields['paymentConfig']['mapValue']['fields'] ?? [];
                    $items[] = $this->mapBranchPaymentToApi($restId, $branchId, $pcFields, false);
                }
            }
        }
        return response()->json([
            'data' => $items,
            'count' => count($items),
        ]);
    }

    public function show(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId)
    {
        // restaurantId is provided in the route now (restaurants/{restaurantId}/branches/{branchId})
        $doc = $firebase->getDocument("restaurants/{$restaurantId}/branches", $branchId);
        $f = $doc['fields'] ?? [];
        $pcFields = $f['paymentConfig']['mapValue']['fields'] ?? [];

        if (!empty($pcFields)) {
            $this->authorizeByRole($restaurantId, $branchId);
            $item = $this->mapBranchPaymentToApi($restaurantId, $branchId, $pcFields, true);
            return response()->json($item);
        }

        $this->authorizeByRole($restaurantId, $branchId);
        return response()->json(null, 204);
    }

    public function upsert(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId)
    {
        // Allow partial updates; only require secret/merchant on create
        $validated = $request->validate([
            'gateway_name' => ['sometimes','string','max:100'],
            'merchant_id' => ['sometimes','string','max:255'],
            'secret_key' => ['sometimes','string'],
            'is_active' => ['sometimes','boolean'],
        ]);

        // Authorize based on role and context; restaurantId comes from route
        $this->authorizeByRole($restaurantId, $branchId);

        // Read existing branch doc for merging and audit before snapshot
        $existingBranch = $firebase->getDocument("restaurants/{$restaurantId}/branches", $branchId) ?: [];
        $existingFields = $existingBranch['fields'] ?? [];
        $before = [];
        if (!empty($existingFields['paymentConfig'])) {
            $before = $this->safeAuditSnapshot($existingFields['paymentConfig']['mapValue']['fields']);
        }

        // Existing paymentConfig values (decoded from firestore structure if present)
        $existingPc = [];
        if (!empty($existingFields['paymentConfig'])) {
            $existingPc = $this->mapFirestoreToApi($branchId, $existingFields['paymentConfig']['mapValue']['fields'], false);
        }

        $now = now()->toIso8601String();

        // Build merged payload: prefer incoming values, then existing, then defaults
        $pcPayload = [
            'gatewayName' => $validated['gateway_name'] ?? $existingPc['gateway_name'] ?? 'Paytrail',
            'merchantId' => $validated['merchant_id'] ?? $existingPc['merchant_id'] ?? null,
            'isActive' => array_key_exists('is_active', $validated) ? (bool)$validated['is_active'] : ($existingPc['is_active'] ?? true),
            'updatedAt' => $now,
        ];

        if (empty($existingPc['created_at'])) {
            $pcPayload['createdAt'] = $now;
        }

        // Only overwrite encrypted secret when provided
        if (!empty($validated['secret_key'])) {
            $pcPayload['secretKeyEnc'] = Crypt::encryptString($validated['secret_key']);
        } elseif (!empty($existingPc['secret_key'])) {
            // keep existing encrypted value — fetch from raw fields if present
            $existingEnc = $existingFields['paymentConfig']['mapValue']['fields']['secretKeyEnc']['stringValue'] ?? null;
            if ($existingEnc) $pcPayload['secretKeyEnc'] = $existingEnc;
        }

        // Update branch document's paymentConfig map (merge with other branch fields)
        $firebase->updateDocument("restaurants/{$restaurantId}/branches", $branchId, ['paymentConfig' => $pcPayload]);

        $after = $this->safeAuditSnapshot($this->wrapApiFields($pcPayload));
        $request->attributes->set('audit_before', $before);
        $request->attributes->set('audit_after', $after);

        $savedBranch = $firebase->getDocument("restaurants/{$restaurantId}/branches", $branchId);
        $sf = $savedBranch['fields']['paymentConfig']['mapValue']['fields'] ?? [];
        $item = $this->mapBranchPaymentToApi($restaurantId, $branchId, $sf, true);
        return response()->json($item, 200);
    }

    private function authorizeByRole(?string $resourceRestaurantId, string $targetBranchId): void
    {
        $role = (string) session('role');
        if ($role === 'restaurant_admin') {
            $assignedRid = (string) session('restaurantId');
            if (!$assignedRid || !$resourceRestaurantId || $assignedRid !== $resourceRestaurantId) {
                abort(403, 'Restaurant admin can only access their restaurant branches');
            }
        } elseif ($role === 'branch_admin') {
            $assignedBid = (string) session('branchId');
            if (!$assignedBid || $assignedBid !== $targetBranchId) {
                abort(403, 'Branch admin can only access their own branch');
            }
        } else {
            // super admin: allow
        }
    }

    private function mapFirestoreToApi(string $docId, array $f, bool $decryptSecret): array
    {
        $decode = function($v) use (&$decode) {
            if (!is_array($v)) return $v;
            if (isset($v['stringValue'])) return $v['stringValue'];
            if (isset($v['integerValue'])) return (int)$v['integerValue'];
            if (isset($v['doubleValue'])) return (float)$v['doubleValue'];
            if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
            if (isset($v['nullValue'])) return null;
            if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']);
            if (isset($v['mapValue']['fields'])) {
                $out = [];
                foreach ($v['mapValue']['fields'] as $kk => $vv) { $out[$kk] = $decode($vv); }
                return $out;
            }
            return $v;
        };

        $secretEnc = $f['secretKeyEnc']['stringValue'] ?? null;
        $secret = null;
        if ($decryptSecret && $secretEnc) {
            try { $secret = Crypt::decryptString($secretEnc); } catch (\Throwable $e) { $secret = null; }
        }

        return [
            'branch_id' => $f['branchId']['stringValue'] ?? $docId,
            'restaurant_id' => $f['restaurantId']['stringValue'] ?? '',
            'gateway_name' => $f['gatewayName']['stringValue'] ?? 'Paytrail',
            'merchant_id' => $f['merchantId']['stringValue'] ?? '',
            'secret_key' => $secret,
            'is_active' => isset($f['isActive']['booleanValue']) ? (bool)$f['isActive']['booleanValue'] : (bool)($f['isActive']['integerValue'] ?? true),
            'created_at' => $f['createdAt']['stringValue'] ?? null,
            'updated_at' => $f['updatedAt']['stringValue'] ?? null,
        ];
    }

    private function mapBranchPaymentToApi(string $restaurantId, string $branchId, array $f, bool $decryptSecret): array
    {
        // $f is the 'fields' inside paymentConfig map
        $secretEnc = $f['secretKeyEnc']['stringValue'] ?? null;
        $secret = null;
        if ($decryptSecret && $secretEnc) {
            try { $secret = Crypt::decryptString($secretEnc); } catch (\Throwable $e) { $secret = null; }
        }
        return [
            'branch_id' => $branchId,
            'restaurant_id' => $restaurantId,
            'gateway_name' => $f['gatewayName']['stringValue'] ?? 'Paytrail',
            'merchant_id' => $f['merchantId']['stringValue'] ?? '',
            'secret_key' => $secret,
            'is_active' => isset($f['isActive']['booleanValue']) ? (bool)$f['isActive']['booleanValue'] : true,
            'created_at' => $f['createdAt']['stringValue'] ?? null,
            'updated_at' => $f['updatedAt']['stringValue'] ?? null,
        ];
    }

    private function wrapApiFields(array $payload): array
    {
        // Helper to mimic Firestore 'fields' for audit masking logic
        $fields = [];
        foreach ($payload as $k => $v) {
            if (is_bool($v)) $fields[$k] = ['booleanValue' => $v];
            elseif (is_int($v)) $fields[$k] = ['integerValue' => $v];
            else $fields[$k] = ['stringValue' => (string) $v];
        }
        return $fields;
    }

    private function safeAuditSnapshot(array $fields): array
    {
        $decode = function($v) use (&$decode) {
            if (!is_array($v)) return $v;
            if (isset($v['stringValue'])) return $v['stringValue'];
            if (isset($v['integerValue'])) return (int)$v['integerValue'];
            if (isset($v['doubleValue'])) return (float)$v['doubleValue'];
            if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
            if (isset($v['nullValue'])) return null;
            if (isset($v['arrayValue']['values'])) return array_map($decode, $v['arrayValue']['values']);
            if (isset($v['mapValue']['fields'])) {
                $out = [];
                foreach ($v['mapValue']['fields'] as $kk => $vv) { $out[$kk] = $decode($vv); }
                return $out;
            }
            return $v;
        };
        $out = [];
        foreach ($fields as $k => $v) {
            if (stripos($k, 'secret') !== false) {
                $out[$k] = '[MASKED]';
            } else {
                $out[$k] = $decode($v);
            }
        }
        return $out;
    }
}
