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
use Illuminate\Support\Facades\Crypt;

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
            'restaurant_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'order_id' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
        ]);

        $branchId = $validated['branch_id'];
        $restaurantId = $validated['restaurant_id'];
        $orderId = $validated['order_id'];
        $amountCents = (int) round(((float) $validated['amount']) * 100);

        try {
            // Branch documents are stored under restaurants/{restaurantId}/branches/{branchId}
            $branchDoc = $this->firestore->getDocument("restaurants/{$restaurantId}/branches", $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

            $creds = $this->resolvePaytrailConfig($branchData);
            if (!$creds) {
                Log::warning('Missing Paytrail config for branch', ['restaurant_id' => $restaurantId, 'branch_id' => $branchId]);
                return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Paytrail requires callback/redirect URLs to use https and not be raw IPs.
            // Allow overriding the base URL via the PAYTRAIL_CALLBACK_BASE_URL env var
            // (use an ngrok/secure tunnel URL during local development).
            $baseUrl = rtrim(env('PAYTRAIL_CALLBACK_BASE_URL', config('app.url', $request->getSchemeAndHttpHost())), '/');

            // Build SDK payment request
            $sdkClient = new PaytrailSdkClient((int) $creds['merchant_id'], (string) $creds['secret_key'], (string) config('app.name', 'Laravel'));
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
                        ->setSuccess($baseUrl . '/api/payments/callback?restaurant_id=' . urlencode($restaurantId) . '&branch_id=' . urlencode($branchId))
                        ->setCancel($baseUrl . '/api/payments/callback?restaurant_id=' . urlencode($restaurantId) . '&branch_id=' . urlencode($branchId))
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
                'restaurant_id' => $restaurantId,
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
            return $this->formatExceptionResponse($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function callback(Request $request)
    {
        $restaurantId = (string) $request->query('restaurant_id');
        $branchId = (string) $request->query('branch_id');
        if (!$restaurantId || !$branchId) {
            return response('restaurant_id and branch_id required', Response::HTTP_BAD_REQUEST);
        }

        try {
            $branchDoc = $this->firestore->getDocument("restaurants/{$restaurantId}/branches", $branchId);
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
            return $this->formatExceptionResponse($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Optional: Payment Status endpoint using SDK
    public function status(Request $request)
    {
        $restaurantId = (string) $request->query('restaurant_id');
        $branchId = (string) $request->query('branch_id');
        $transactionId = (string) $request->query('transaction_id');
        if (!$restaurantId || !$branchId || !$transactionId) {
            return response()->json(['message' => 'restaurant_id, branch_id and transaction_id are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $branchDoc = $this->firestore->getDocument("restaurants/{$restaurantId}/branches", $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

                $creds = $this->resolvePaytrailConfig($branchData);
                if (!$creds) {
                    return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

            $sdkClient = new PaytrailSdkClient((int) $creds['merchant_id'], (string) $creds['secret_key'], (string) config('app.name', 'Laravel'));
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
            return $this->formatExceptionResponse($e, Response::HTTP_BAD_GATEWAY);
        }
    }

    public function adminHistory(Request $request, string $restaurantId, string $branchId)
    {
        try {
            $branchDoc = $this->firestore->getDocument("restaurants/{$restaurantId}/branches", $branchId);
            $branchData = $this->decodeFirestoreDocument($branchDoc);
            if (!$branchData) {
                return response()->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
            }

            $creds = $this->resolvePaytrailConfig($branchData);
            if (!$creds) {
                return response()->json(['message' => 'Branch payment configuration missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $client = new PaytrailClient((string) $creds['merchant_id'], (string) $creds['secret_key']);
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
            return $this->formatExceptionResponse($e, Response::HTTP_BAD_GATEWAY);
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

    /**
     * Resolve Paytrail credentials from branch document. Supports two shapes:
     * - paytrail_config: { merchant_id, secret_key }
     * - paymentConfig: { merchantId, secretKeyEnc }
     * Returns ['merchant_id'=>string, 'secret_key'=>string] or null if missing/invalid.
     */
    protected function resolvePaytrailConfig(array $branchData): ?array
    {
        // Preferred modern shape
        if (isset($branchData['paytrail_config']) && is_array($branchData['paytrail_config'])) {
            $pc = $branchData['paytrail_config'];
            if (!empty($pc['merchant_id']) && !empty($pc['secret_key'])) {
                return [
                    'merchant_id' => (string) $pc['merchant_id'],
                    'secret_key' => (string) $pc['secret_key'],
                ];
            }
        }

        // Legacy / alternative shape used by admin UI
        if (!empty($branchData['paymentConfig']) && is_array($branchData['paymentConfig'])) {
            $alt = $branchData['paymentConfig'];
            if (!empty($alt['merchantId']) && !empty($alt['secretKeyEnc'])) {
                try {
                    $secret = Crypt::decryptString($alt['secretKeyEnc']);
                } catch (\Throwable $e) {
                    Log::error('Unable to decrypt branch payment secret', ['error' => $e->getMessage()]);
                    return null;
                }
                return [
                    'merchant_id' => (string) $alt['merchantId'],
                    'secret_key' => (string) $secret,
                ];
            }
        }

        return null;
    }

    /**
     * Build a standardized error response for exceptions.
     * Includes message always and stack trace only when app.debug is true.
     */
    protected function formatExceptionResponse(\Throwable $e, int $status = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        $body = [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ];

        if (config('app.debug')) {
            $body['trace'] = $e->getTraceAsString();
        }

        return response()->json($body, $status);
    }
}
