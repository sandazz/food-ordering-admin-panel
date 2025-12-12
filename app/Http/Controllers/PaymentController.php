<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Services\FirebaseService;
use App\Services\PaytrailClient;

class PaymentController extends Controller
{
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
        }
    }

    public function initiate(Request $request, FirebaseService $firebase, PaytrailClient $paytrail)
    {
        $v = $request->validate([
            'order_id' => 'required|string',
            'amount' => 'required|numeric',
            'customer_email' => 'required|email',
            'restaurant_id' => 'required|string',
            'branch_id' => 'required|string',
        ]);

        // Authorize caller for this branch
        $this->authorizeByRole($v['restaurant_id'], $v['branch_id']);

        // Load payment config
        $path = "restaurants/{$v['restaurant_id']}/branches/{$v['branch_id']}";
        $branch = $firebase->getDocument($path);
        $pc = $branch['paymentConfig'] ?? null;

        if (!$pc || empty($pc['merchantId']) || empty($pc['secretKeyEnc'])) {
            return response()->json(['message' => 'Payment gateway not configured for this branch'], 422);
        }

        try {
            $secret = Crypt::decryptString($pc['secretKeyEnc']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unable to decrypt payment secret'], 500);
        }

        // Build payload for Paytrail
        $payload = [
            'order_number' => $v['order_id'],
            'price' => intval(round($v['amount'] * 100)),
            'currency' => 'EUR',
            'language' => 'en',
            'customer' => [ 'email' => $v['customer_email'] ],
            'returnUrls' => [
                'success' => config('app.paytrail_return_success', env('PAYTRAIL_RETURN_URL_SUCCESS')),
                'cancel' => config('app.paytrail_return_cancel', env('PAYTRAIL_RETURN_URL_CANCEL')),
            ],
        ];

        // Use provided PaytrailClient to create the payment
        try {
            $paytrail = new PaytrailClient($pc['merchantId'], $secret);
            $result = $paytrail->createPayment($payload);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage()], 500);
        }

        if (empty($result['payment_url'])) {
            return response()->json(['message' => 'Failed to create payment'], 500);
        }

        // Optionally persist transaction id to Firestore (orders collection or branch)

        return response()->json([ 'payment_url' => $result['payment_url'], 'transaction_id' => $result['transaction_id'] ]);
    }

    public function webhook(Request $request, FirebaseService $firebase)
    {
        // Webhook should verify signature/header — implementation depends on Paytrail
        $payload = $request->all();
        $txnId = $payload['transaction_id'] ?? null;
        $orderNumber = $payload['order_number'] ?? $payload['orderId'] ?? null;
        $status = $payload['status'] ?? null;

        if ($orderNumber) {
            // Example: update order document under orders/{orderNumber}
            // You should map to your actual orders collection and fields
            try {
                $orders = $firebase->getCollection('orders');
            } catch (\Throwable $e) {
                // ignore if collection not present
                $orders = null;
            }
        }

        return response()->json(['ok' => true]);
    }
}
