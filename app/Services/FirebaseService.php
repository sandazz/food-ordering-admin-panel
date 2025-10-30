<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class FirebaseService
{
    protected Client $httpClient;
    protected Messaging $messaging;
    protected string $projectId;
    protected ?string $accessToken = null;
    protected ?int $accessTokenExpiry = null;
    protected array $guzzleOptions = [];

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $this->messaging = $factory->createMessaging();
        
        // Get project ID from credentials file
        $credentials = json_decode(file_get_contents(storage_path('app/firebase/firebase_credentials.json')), true);
        $this->projectId = $credentials['project_id'];
        
        // Default Guzzle options — tune timeouts and retries to be resilient to transient network issues
        $this->guzzleOptions = [
            'base_uri' => "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/",
            'headers' => [
                'Content-Type' => 'application/json',
                'Connection' => 'keep-alive',
            ],
            // Don't throw exceptions for 4xx/5xx so we can handle them uniformly
            'http_errors' => false,
            // reasonable timeouts
            'timeout' => 15,
            'connect_timeout' => 5,
            // Enable debug output only when APP_DEBUG is true and running in CLI to avoid
            // passing invalid output streams to cURL in web/FPM SAPI environments.
            'debug' => (bool) (config('app.debug', false) && php_sapi_name() === 'cli'),
        ];

        $this->httpClient = new Client($this->guzzleOptions);
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
        $response = $this->performRequest('GET', $collectionName, [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getDocument(string $collectionName, string $documentId)
    {
        $token = $this->getAccessToken();
        $response = $this->performRequest('GET', "{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function createDocument(string $collectionName, array $data, ?string $documentId = null)
    {
        $endpoint = $documentId ? "{$collectionName}?documentId={$documentId}" : $collectionName;
        $token = $this->getAccessToken();
        $response = $this->performRequest('POST', $endpoint, [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json' => ['fields' => $this->encodeFirestoreData($data)]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function updateDocument(string $collectionName, string $documentId, array $data)
    {
        $token = $this->getAccessToken();
        $response = $this->performRequest('PATCH', "{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json' => ['fields' => $this->encodeFirestoreData($data)]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteDocument(string $collectionName, string $documentId)
    {
        $token = $this->getAccessToken();
        $response = $this->performRequest('DELETE', "{$collectionName}/{$documentId}", [
            'headers' => ['Authorization' => "Bearer {$token}"]
        ]);
        return in_array($response->getStatusCode(), [200, 202, 204], true);
    }

    /**
     * Perform an HTTP request with simple retry/backoff for transient network errors.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param int $retries
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function performRequest(string $method, string $uri, array $options = [], int $retries = 3)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $retries) {
            try {
                return $this->httpClient->request($method, $uri, $options);
            } catch (ConnectException $e) {
                $lastException = $e;
                // connection-level errors: retry
            } catch (RequestException $e) {
                $lastException = $e;
                // For server errors or network resets, retry
            }

            $attempt++;
            // exponential backoff
            sleep((int) pow(2, $attempt));
        }

        // If we reach here, rethrow the last exception for visibility
        if ($lastException) {
            throw $lastException;
        }

        throw new \RuntimeException('Unexpected error performing HTTP request to Firestore.');
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
