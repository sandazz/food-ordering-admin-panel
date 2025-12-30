<?php
namespace App\Services;

use GuzzleHttp\Client;

class PaytrailClient
{
    protected $merchantId;
    protected $secret;
    protected $client;

    public function __construct(string $merchantId, string $secret)
    {
        $this->merchantId = $merchantId;
        $this->secret = $secret;
        $this->client = new Client(['base_uri' => 'https://payment.paytrail.com/']);
    }

    public function createPayment(array $payload): array
    {
        $resp = $this->client->post('/payments', [
            'json' => $payload,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->merchantId . ':' . $this->secret),
                'Accept' => 'application/json',
            ],
        ]);

        $body = json_decode($resp->getBody()->getContents(), true);
        return [
            'payment_url' => $body['payment_url'] ?? ($body['links'][0]['href'] ?? null),
            'transaction_id' => $body['transaction_id'] ?? $body['payment_id'] ?? null,
            'raw' => $body,
        ];
    }

    /**
     * Get payment details from Paytrail by transaction ID
     * This is the ONLY method Paytrail provides for querying individual payments
     * 
     * @param string $transactionId The Paytrail transaction ID
     * @return array Payment details including status, amount, provider
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPayment(string $transactionId): array
    {
        $resp = $this->client->get("/payments/{$transactionId}", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->merchantId . ':' . $this->secret),
                'Accept' => 'application/json',
            ],
        ]);

        $body = json_decode($resp->getBody()->getContents(), true);
        return $body ?? [];
    }

    /**
     * @deprecated Paytrail does NOT support payment listing/reporting via API
     * This method is kept for backward compatibility but will always fail
     * Use Firestore 'payments' collection instead for payment history
     */
    public function getPaymentReport(array $params = []): array
    {
        // NOTE: Paytrail API does NOT support listing payments by date range
        // This method is deprecated and should not be used
        // All payment history must be stored in Firestore during creation/callback
        throw new \Exception('Paytrail does not support payment listing API. Query Firestore payments collection instead.');
    }
}
