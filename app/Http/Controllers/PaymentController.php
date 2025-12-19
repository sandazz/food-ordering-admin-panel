<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use App\Services\PaytrailClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Paytrail\SDK\Util\Signature as PaytrailSignature;
use Paytrail\SDK\Exception\HmacException;
use Paytrail\SDK\Client as PaytrailSdkClient;
use Paytrail\SDK\Request\PaymentRequest as PaytrailPaymentRequest;
use Paytrail\SDK\Request\PaymentStatusRequest as PaytrailPaymentStatusRequest;
use Paytrail\SDK\Model\Customer as PaytrailCustomer;
use Paytrail\SDK\Model\CallbackUrl as PaytrailCallbackUrl;

class PaymentController extends Controller
{
    protected FirebaseService $firestore;

    public function __construct(FirebaseService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'order_id' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
        ]);

        $branchId = $validated['branch_id'];
        $orderId = $validated['order_id'];
        $amountCents = (int) round(((float) $validated['amount']) * 100);

        try {
            $branchDoc = $this->firestore->getDocument('branches', $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

            $cfg = $branchData['paytrail_config'] ?? null;
            if (!$cfg || empty($cfg['merchant_id']) || empty($cfg['secret_key'])) {
                Log::warning('Missing Paytrail config for branch', ['branch_id' => $branchId]);
                return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $baseUrl = rtrim(config('app.url', $request->getSchemeAndHttpHost()), '/');

            // Build SDK payment request
            $sdkClient = new PaytrailSdkClient((int) $cfg['merchant_id'], (string) $cfg['secret_key'], (string) config('app.name', 'Laravel'));
            $paymentReq = (new PaytrailPaymentRequest())
                ->setAmount($amountCents)
                ->setReference($orderId)
                ->setStamp(Str::uuid()->toString())
                ->setCurrency('EUR')
                ->setLanguage('EN')
                ->setCustomer((new PaytrailCustomer())->setEmail($validated['customer_email']))
                ->setRedirectUrls((new PaytrailCallbackUrl())
                    ->setSuccess($baseUrl . '/payment/success')
                    ->setCancel($baseUrl . '/payment/cancel')
                )
                ->setCallbackUrls((new PaytrailCallbackUrl())
                    ->setSuccess($baseUrl . '/api/payments/callback?branch_id=' . urlencode($branchId))
                    ->setCancel($baseUrl . '/api/payments/callback?branch_id=' . urlencode($branchId))
                );

            $sdkResponse = $sdkClient->createPayment($paymentReq);
            $paymentUrl = $sdkResponse->getHref();
            if (!$paymentUrl) {
                Log::error('Paytrail SDK createPayment returned no href', ['branch_id' => $branchId, 'order_id' => $orderId]);
                return response()->json(['message' => 'Unable to create payment'], Response::HTTP_BAD_GATEWAY);
            }

            $this->firestore->createDocument('orders', [
                'order_id' => $orderId,
                'branch_id' => $branchId,
                'amount' => (float) $validated['amount'],
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
            ], $orderId);

            return response()->json(['payment_url' => $paymentUrl], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed', [
                'branch_id' => $branchId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Payment initiation error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function callback(Request $request)
    {
        $branchId = (string) $request->query('branch_id');
        if (!$branchId) {
            return response('branch_id required', Response::HTTP_BAD_REQUEST);
        }

        try {
            $branchDoc = $this->firestore->getDocument('branches', $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData || empty($branchData['paytrail_config']['secret_key'])) {
                Log::warning('Callback for unknown branch', ['branch_id' => $branchId]);
                return response('Invalid branch', Response::HTTP_FORBIDDEN);
            }

            $secret = (string) $branchData['paytrail_config']['secret_key'];

            $isValid = $this->verifySignature($request, $secret);
            if (!$isValid) {
                Log::warning('Invalid Paytrail signature', ['branch_id' => $branchId]);
                return response('Forbidden', Response::HTTP_FORBIDDEN);
            }

            $orderId = (string) ($request->query('order_id') ?? $request->query('checkout-reference') ?? $request->query('reference'));
            $transactionId = (string) ($request->query('transaction_id') ?? $request->query('checkout-transaction-id'));
            $status = (string) ($request->query('status') ?? $request->query('checkout-status'));

            if ($orderId && Str::lower($status) === 'ok') {
                $this->firestore->updateDocument('orders', $orderId, [
                    'status' => 'paid',
                    'paytrail_transaction_id' => $transactionId,
                    'paid_at' => now()->toIso8601String(),
                ]);
            }

            return response('OK', Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Payment callback failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);
            return response('OK', Response::HTTP_OK);
        }
    }

    // Optional: Payment Status endpoint using SDK
    public function status(Request $request)
    {
        $branchId = (string) $request->query('branch_id');
        $transactionId = (string) $request->query('transaction_id');
        if (!$branchId || !$transactionId) {
            return response()->json(['message' => 'branch_id and transaction_id are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $branchDoc = $this->firestore->getDocument('branches', $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

            $cfg = $branchData['paytrail_config'] ?? null;
            if (!$cfg || empty($cfg['merchant_id']) || empty($cfg['secret_key'])) {
                return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $sdkClient = new PaytrailSdkClient((int) $cfg['merchant_id'], (string) $cfg['secret_key'], (string) config('app.name', 'Laravel'));
            $statusReq = (new PaytrailPaymentStatusRequest())->setTransactionId($transactionId);
            $statusRes = $sdkClient->getPaymentStatus($statusReq);

            return response()->json([
                'transaction_id' => $statusRes->getTransactionId(),
                'status' => $statusRes->getStatus(),
                'amount' => $statusRes->getAmount(),
                'currency' => $statusRes->getCurrency(),
                'reference' => $statusRes->getReference(),
                'paid_at' => $statusRes->getPaidAt(),
                'provider' => $statusRes->getProvider(),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Payment status fetch failed', ['branch_id' => $branchId, 'transaction_id' => $transactionId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to fetch status'], Response::HTTP_BAD_GATEWAY);
        }
    }

    public function adminHistory(Request $request, string $branchId)
    {
        try {
            $branchDoc = $this->firestore->getDocument('branches', $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

            $cfg = $branchData['paytrail_config'] ?? null;
            if (!$cfg || empty($cfg['merchant_id']) || empty($cfg['secret_key'])) {
                return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $client = new PaytrailClient((string) $cfg['merchant_id'], (string) $cfg['secret_key']);
            $from = (string) $request->query('from', now()->subDays(7)->toDateString());
            $to = (string) $request->query('to', now()->toDateString());
            $report = $client->getPaymentReport([
                'from' => $from,
                'to' => $to,
            ]);

            return response()->json(['transactions' => $report], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Admin history retrieval failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Unable to fetch history'], Response::HTTP_BAD_GATEWAY);
        }
    }

    protected function decodeFirestoreDocument(?array $doc): ?array
    {
        if (!$doc || !isset($doc['fields'])) {
            return null;
        }
        return $this->decodeFields($doc['fields']);
    }

    protected function decodeFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $k => $v) {
            $out[$k] = $this->decodeValue($v);
        }
        return $out;
    }

    protected function decodeValue($value)
    {
        if (isset($value['stringValue'])) return $value['stringValue'];
        if (isset($value['integerValue'])) return (int) $value['integerValue'];
        if (isset($value['doubleValue'])) return (float) $value['doubleValue'];
        if (isset($value['booleanValue'])) return (bool) $value['booleanValue'];
        if (isset($value['nullValue'])) return null;
        if (isset($value['mapValue'])) return $this->decodeFields($value['mapValue']['fields'] ?? []);
        if (isset($value['arrayValue'])) {
            $vals = $value['arrayValue']['values'] ?? [];
            return array_map(fn($v) => $this->decodeValue($v), $vals);
        }
        return $value;
    }

    protected function verifySignature(Request $request, string $secret): bool
    {
        // Prefer header-based signature validation
        $headerSig = (string) ($request->header('signature') ?? $request->header('checkout-hmac') ?? '');
        if ($headerSig !== '') {
            $headers = array_change_key_case($request->headers->all(), CASE_LOWER);
            try {
                PaytrailSignature::validateHmac($headers, '', $headerSig, $secret);
                return true;
            } catch (HmacException $e) {
                // fall through to query param validation
            }
        }

        // Fallback to query-param signature validation (redirect/callback)
        $querySig = (string) $request->query('signature', '');
        if ($querySig !== '') {
            $params = $request->query();
            try {
                PaytrailSignature::validateHmac($params, '', $querySig, $secret);
                return true;
            } catch (HmacException $e) {
                return false;
            }
        }

        return false;
    }
}
