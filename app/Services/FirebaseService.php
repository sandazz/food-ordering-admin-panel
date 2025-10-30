<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use GuzzleHttp\Client;

class FirebaseService
{
    protected Client $httpClient;
    protected Messaging $messaging;
    protected string $projectId;
    protected ?string $accessToken = null;
    protected ?int $accessTokenExpiry = null;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $this->messaging = $factory->createMessaging();
        
        // Get project ID from credentials file
        $credentials = json_decode(file_get_contents(storage_path('app/firebase/firebase_credentials.json')), true);
        $this->projectId = $credentials['project_id'];
        
        // Initialize HTTP client for Firestore REST API (we'll add Authorization header per-request)
        $this->httpClient = new Client([
            'base_uri' => "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/",
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    protected function getAccessToken()
    {
        // Return cached token if not expired (with 60s buffer)
        if ($this->accessToken && $this->accessTokenExpiry && time() < ($this->accessTokenExpiry - 60)) {
            return $this->accessToken;
        }

        $credentialsPath = storage_path('app/firebase/firebase_credentials.json');
        $credentials = json_decode(file_get_contents($credentialsPath), true);

        if (empty($credentials['private_key']) || empty($credentials['client_email']) || empty($credentials['token_uri'])) {
            throw new \RuntimeException('Invalid service account credentials for Firestore access token.');
        }

        $now = time();
        $tokenUri = $credentials['token_uri'];
        $scope = "https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/cloud-platform";

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claim = [
            'iss' => $credentials['client_email'],
            'scope' => $scope,
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $claimPayload = $this->base64UrlEncode(json_encode($claim));
        $unsignedJwt = $header . '.' . $claimPayload;

        $privateKey = $credentials['private_key'];
        $signature = null;
        $pkey = openssl_pkey_get_private($privateKey);
        if ($pkey === false) {
            throw new \RuntimeException('Unable to parse private key for service account.');
        }
        $ok = openssl_sign($unsignedJwt, $signature, $pkey, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkey);
        if (! $ok) {
            throw new \RuntimeException('Failed to sign JWT for OAuth token exchange.');
        }

        $signed = $unsignedJwt . '.' . $this->base64UrlEncode($signature);

        // Exchange JWT for access token
        $response = (new Client())->post($tokenUri, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $signed,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        if (empty($body['access_token']) || empty($body['expires_in'])) {
            throw new \RuntimeException('Failed to obtain access token from Google: ' . $response->getBody());
        }

        $this->accessToken = $body['access_token'];
        $this->accessTokenExpiry = time() + (int) $body['expires_in'];

        return $this->accessToken;
    }

    public function getCollection(string $collectionName)
    {
        $token = $this->getAccessToken();
        $response = $this->httpClient->get($collectionName, [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getDocument(string $collectionName, string $documentId)
    {
        $token = $this->getAccessToken();
        $response = $this->httpClient->get("{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function createDocument(string $collectionName, array $data, ?string $documentId = null)
    {
        $endpoint = $documentId ? "{$collectionName}?documentId={$documentId}" : $collectionName;
        $token = $this->getAccessToken();
        $response = $this->httpClient->post($endpoint, [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json' => ['fields' => $this->encodeFirestoreData($data)]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function updateDocument(string $collectionName, string $documentId, array $data)
    {
        $token = $this->getAccessToken();
        $response = $this->httpClient->patch("{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json' => ['fields' => $this->encodeFirestoreData($data)]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteDocument(string $collectionName, string $documentId)
    {
        $token = $this->getAccessToken();
        $response = $this->httpClient->delete("{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return in_array($response->getStatusCode(), [200, 202, 204], true);
    }

    protected function base64UrlEncode(string $input)
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    protected function encodeFirestoreData(array $data)
    {
        $encoded = [];
        foreach ($data as $key => $value) {
            $encoded[$key] = $this->encodeFirestoreValue($value);
        }
        return $encoded;
    }

    protected function encodeFirestoreValue($value)
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_null($value)) {
            return ['nullValue' => null];
        } elseif (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                // Sequential array (list)
                return ['arrayValue' => ['values' => array_map([$this, 'encodeFirestoreValue'], $value)]];
            } else {
                // Associative array (map)
                return ['mapValue' => ['fields' => $this->encodeFirestoreData($value)]];
            }
        }
        throw new \InvalidArgumentException("Unsupported value type: " . gettype($value));
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }
}
