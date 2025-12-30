<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class NotificationsController extends Controller
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Display the notifications page with restaurant selector
     */
    public function index()
    {
        $role = session('role', 'admin');
        $restaurantId = session('restaurantId');
        $restaurants = [];
        
        // Fetch restaurants based on user role
        if ($role === 'admin') {
            // Super Admin: Can see all restaurants
            try {
                $result = $this->firebase->getCollection('restaurants');
                if (isset($result['documents']) && is_array($result['documents'])) {
                    foreach ($result['documents'] as $doc) {
                        $id = basename($doc['name']);
                        $fields = $doc['fields'] ?? [];
                        $restaurants[] = [
                            'id' => $id,
                            'name' => $fields['name']['stringValue'] ?? $id,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch restaurants: ' . $e->getMessage());
            }
        } elseif (in_array($role, ['restaurant_admin', 'branch_admin']) && $restaurantId) {
            // Restaurant/Branch Admin: Scoped to their restaurant
            try {
                $doc = $this->firebase->getDocument('restaurants', $restaurantId);
                if (isset($doc['fields'])) {
                    $fields = $doc['fields'];
                    $restaurants[] = [
                        'id' => $restaurantId,
                        'name' => $fields['name']['stringValue'] ?? $restaurantId,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch restaurant: ' . $e->getMessage());
            }
        }

        return view('admin.notifications.index', compact('restaurants', 'role'));
    }

    /**
     * Send push notifications to customers
     */
    public function store(Request $request)
    {
        $role = session('role', 'admin');
        $userRestaurantId = session('restaurantId');

        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:500',
            'target_restaurant' => 'required|string',
        ]);

        $targetRestaurant = $validated['target_restaurant'];

        // Role-based restrictions
        if (in_array($role, ['restaurant_admin', 'branch_admin'])) {
            // Force target to user's restaurant
            $targetRestaurant = $userRestaurantId;
            if (!$targetRestaurant) {
                return back()->withErrors(['target_restaurant' => 'No restaurant context found.']);
            }
        }

        try {
            // Fetch customers from Firestore
            $customers = [];
            if ($targetRestaurant === 'all') {
                // Fetch all customers
                $result = $this->firebase->getCollection('customers');
                if (isset($result['documents']) && is_array($result['documents'])) {
                    $customers = $result['documents'];
                }
            } else {
                // Fetch customers filtered by restaurantId
                $result = $this->firebase->runStructuredQuery('customers', [
                    ['fieldPath' => 'restaurantId', 'op' => 'EQUAL', 'value' => $targetRestaurant]
                ]);
                if (isset($result['documents']) && is_array($result['documents'])) {
                    $customers = $result['documents'];
                }
            }

            // Extract FCM tokens and prepare notification data
            $tokens = [];
            $customerLanguages = [];
            
            foreach ($customers as $doc) {
                $fields = $doc['fields'] ?? [];
                $userId = basename($doc['name']);
                
                // Extract fcmToken
                if (isset($fields['fcmToken']['stringValue'])) {
                    $token = $fields['fcmToken']['stringValue'];
                    if (!empty($token) && strlen($token) > 10) {
                        $tokens[] = $token;
                        
                        // Store customer language preference
                        $lang = $fields['preferredLanguage']['stringValue'] ?? 'en';
                        $customerLanguages[$token] = $lang;
                    }
                }
            }

            if (empty($tokens)) {
                return back()->with('warning', 'No customers with valid FCM tokens found for the selected target.');
            }

            // Send notifications using FirebaseService
            $successCount = 0;
            $failureCount = 0;
            $batches = array_chunk($tokens, 500); // FCM limit is 500 tokens per batch

            foreach ($batches as $batchTokens) {
                $notification = [
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                ];

                $data = [
                    'restaurantId' => $targetRestaurant,
                    'type' => 'admin_notification',
                    'timestamp' => now()->toIso8601String(),
                ];

                try {
                    $result = $this->firebase->sendPushNotification($batchTokens, $notification, $data);
                    $successCount += $result['success'] ?? 0;
                    $failureCount += $result['failure'] ?? 0;
                } catch (\Exception $e) {
                    Log::error('Failed to send notification batch: ' . $e->getMessage());
                    $failureCount += count($batchTokens);
                }
            }

            // Set audit after data for AdminAuditMiddleware to log to Firebase
            $request->attributes->set('audit_after', [
                'title' => $validated['title'],
                'body' => $validated['body'],
            ]);

            $message = "Notification sent successfully! Success: {$successCount}, Failures: {$failureCount}";
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to send notifications: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to send notifications: ' . $e->getMessage()]);
        }
    }

    /**
     * Send a test notification to a specific FCM token
     */
    public function test(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string|min:20',
            ]);

            $token = $validated['token'];
            
            $notification = [
                'title' => '🔔 Test Notification',
                'body' => 'This is a test notification from your food ordering admin panel. Sent at ' . now()->format('H:i:s'),
            ];

            $data = [
                'type' => 'test_notification',
                'timestamp' => now()->toIso8601String(),
                'sender' => session('firebase_user')['email'] ?? 'admin',
            ];

            $result = $this->firebase->sendPushNotification([$token], $notification, $data);

            if ($result['success'] > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test notification sent successfully!',
                    'result' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test notification. The token may be invalid or expired.',
                    'result' => $result,
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Test notification failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Legacy send method for backward compatibility
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:500',
            'topic' => 'nullable|string',
            'token' => 'nullable|string',
            'restaurantId' => 'nullable|string',
            'region' => 'nullable|string',
            'group' => 'nullable|string',
        ]);

        $messaging = $this->firebase->getMessaging();

        if (!empty($data['token'])) {
            $message = CloudMessage::withTarget('token', $data['token'])
                ->withNotification(FcmNotification::create($data['title'], $data['body']));
            $messaging->send($message);
        } else {
            $topics = [];
            if (!empty($data['topic'])) { $topics[] = $data['topic']; }
            if (!empty($data['restaurantId'])) { $topics[] = 'restaurant_' . $data['restaurantId']; }
            if (!empty($data['region'])) { $topics[] = 'region_' . $data['region']; }
            if (!empty($data['group'])) { $topics[] = 'group_' . $data['group']; }
            $topics = array_values(array_unique(array_filter($topics)));
            foreach ($topics as $t) {
                $message = CloudMessage::withTarget('topic', $t)
                    ->withNotification(FcmNotification::create($data['title'], $data['body']));
                $messaging->send($message);
            }
        }

        return back()->with('status', 'Notification sent');
    }
}
