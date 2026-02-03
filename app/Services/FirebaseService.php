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

    /**
     * Run a structured query against Firestore to fetch documents that match the provided filters.
     * Returns an array with a 'documents' key consistent with getCollection responses.
     *
     * @param string $collectionId Top-level collection id (e.g. 'orders')
     * @param array $fieldFilters Array of ['fieldPath' => 'restaurantId', 'op' => 'EQUAL', 'value' => 'resto_xxx']
     * @return array
     */
    public function runStructuredQuery(string $collectionId, array $fieldFilters = [])
    {
        $token = $this->getAccessToken();

        // Build where clause
        $where = null;
        $filters = [];
        foreach ($fieldFilters as $ff) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $ff['fieldPath']],
                    'op' => $ff['op'] ?? 'EQUAL',
                    'value' => is_string($ff['value']) ? ['stringValue' => $ff['value']] : (is_int($ff['value']) ? ['integerValue' => $ff['value']] : ['stringValue' => (string)$ff['value']]),
                ],
            ];
        }

        if (count($filters) === 1) {
            $where = $filters[0];
        } elseif (count($filters) > 1) {
            $where = [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => $filters,
                ],
            ];
        }

        $structuredQuery = [
            'from' => [[ 'collectionId' => $collectionId ]],
        ];

        if ($where) {
            $structuredQuery['where'] = $where;
        }

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";

        $response = $this->performRequest('POST', $url, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => ['structuredQuery' => $structuredQuery],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        // The runQuery response is an array of results, each may contain a 'document' entry
        $documents = [];
        if (is_array($body)) {
            foreach ($body as $entry) {
                if (isset($entry['document'])) {
                    $documents[] = $entry['document'];
                }
            }
        }

        return ['documents' => $documents];
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
        // Build update mask so Firestore performs a partial update and preserves unspecified fields
        $fieldPaths = array_keys($data);
        $query = '';
        if (!empty($fieldPaths)) {
            // Append a query param for each field path
            $maskParts = array_map(function ($f) {
                return 'updateMask.fieldPaths=' . urlencode($f);
            }, $fieldPaths);
            $query = '?' . implode('&', $maskParts);
        }

        $response = $this->performRequest('PATCH', "{$collectionName}/{$documentId}{$query}", [
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
     * Update a Firebase Auth user's password using privileged service account access.
     *
     * @param string $localId The Firebase Auth UID (localId)
     * @param string $newPassword New password to set
     * @return bool True on success
     */
    public function updateUserPassword(string $localId, string $newPassword): bool
    {
        $token = $this->getAccessToken();
        $uri = "https://identitytoolkit.googleapis.com/v1/projects/{$this->projectId}/accounts:update";
        $client = new Client($this->guzzleOptions);
        $res = $client->post($uri, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'localId' => $localId,
                'password' => $newPassword,
            ],
        ]);
        return in_array($res->getStatusCode(), [200], true);
    }

    /**
     * Get Firebase Auth user by email address.
     *
     * @param string $email User's email address
     * @return array|null User data or null if not found
     */
    public function getUserByEmail(string $email): ?array
    {
        $token = $this->getAccessToken();
        $uri = "https://identitytoolkit.googleapis.com/v1/projects/{$this->projectId}/accounts:lookup";
        $client = new Client($this->guzzleOptions);
        
        try {
            $res = $client->post($uri, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => [$email],
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                $body = json_decode($res->getBody()->getContents(), true);
                return $body['users'][0] ?? null;
            }
        } catch (\Exception $e) {
            \Log::error("Error looking up user by email: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Delete a Firebase Auth user account.
     *
     * @param string $localId The Firebase Auth UID (localId)
     * @return bool True on success
     */
    public function deleteUser(string $localId): bool
    {
        $token = $this->getAccessToken();
        $uri = "https://identitytoolkit.googleapis.com/v1/projects/{$this->projectId}/accounts:delete";
        $client = new Client($this->guzzleOptions);
        
        try {
            $res = $client->post($uri, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'localId' => $localId,
                ],
            ]);

            return in_array($res->getStatusCode(), [200, 204], true);
        } catch (\Exception $e) {
            \Log::error("Error deleting user: " . $e->getMessage());
            return false;
        }
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

    /**
     * Send push notification to multiple devices using FCM multicast
     *
     * @param array $tokens Array of FCM device tokens
     * @param array $notification ['title' => 'Title', 'body' => 'Body']
     * @param array $data Optional additional data payload
     * @return array ['success' => int, 'failure' => int, 'invalidTokens' => array]
     */
    public function sendPushNotification(array $tokens, array $notification, array $data = []): array
    {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'invalidTokens' => []];
        }

        try {
            $message = [
                'notification' => [
                    'title' => $notification['title'] ?? '',
                    'body' => $notification['body'] ?? '',
                ],
            ];

            if (!empty($data)) {
                $message['data'] = $data;
            }

            // Use the messaging SDK to send multicast
            $messaging = $this->messaging;
            $sendReport = $messaging->sendMulticast($message, $tokens);

            $successCount = $sendReport->successes()->count();
            $failureCount = $sendReport->failures()->count();
            $invalidTokens = [];

            // Collect invalid tokens for cleanup
            foreach ($sendReport->failures()->getItems() as $failure) {
                $error = $failure->error();
                $errorCode = $error->getMessagingErrorCode();
                
                // Track tokens that should be removed
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    $invalidTokens[] = $failure->target()->value();
                }
            }

            return [
                'success' => $successCount,
                'failure' => $failureCount,
                'invalidTokens' => $invalidTokens,
            ];
        } catch (\Exception $e) {
            \Log::error('FCM sendPushNotification failed: ' . $e->getMessage());
            return [
                'success' => 0,
                'failure' => count($tokens),
                'invalidTokens' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}
