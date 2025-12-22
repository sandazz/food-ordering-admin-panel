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

    public function getPaymentReport(array $params = []): array
    {
        $query = [];
        if (!empty($params['from'])) $query['from'] = $params['from'];
        if (!empty($params['to'])) $query['to'] = $params['to'];

        $resp = $this->client->get('/payments/report', [
            'query' => $query,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->merchantId . ':' . $this->secret),
                'Accept' => 'application/json',
            ],
        ]);

        $body = json_decode($resp->getBody()->getContents(), true);
        if (is_array($body)) {
            return $body;
        }
        return [];
    }
}
