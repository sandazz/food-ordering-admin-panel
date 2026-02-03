<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class AccountDeletionController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Show the account deletion request form
     */
    public function showDeletionForm()
    {
        return view('legal.delete-account');
    }

    /**
     * Process the account deletion request and delete immediately
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        Log::info("=== Account Deletion Request Started ===");
        Log::info("Email provided: {$email}");

        try {
            // Check if user exists in Firebase Auth
            Log::info("Checking if user exists in Firebase...");
            $user = $this->firebaseService->getUserByEmail($email);

            if (!$user) {
                Log::warning("No user found with email: {$email}");
                return back()->with('error', 'No account found with this email address.');
            }

            $uid = $user['localId'];
            Log::info("User found - UID: {$uid}");

            // Delete user from Firebase Authentication
            Log::info("Deleting user from Firebase Auth...");
            $deleted = $this->firebaseService->deleteUser($uid);

            if (!$deleted) {
                throw new \Exception('Failed to delete user from Firebase Auth');
            }

            // Delete all user-related data from Firestore
            Log::info("Deleting user data from Firestore...");
            $this->deleteUserData($uid);

            Log::info("✅ Account deleted successfully for user: {$email} (UID: {$uid})");
            Log::info("=== Account Deletion Request Completed ===");

            return view('legal.delete-account-success', ['email' => $email]);

        } catch (\Exception $e) {
            Log::error('❌ Account deletion request failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'An error occurred while deleting your account. Please try again later.');
        }
    }

    /**
     * Delete all user-related data from Firestore
     */
    protected function deleteUserData($uid)
    {
        try {
            // Delete user document from 'users' collection
            $this->firebaseService->deleteDocument('users', $uid);

            // Delete user's orders
            $orders = $this->firebaseService->runStructuredQuery('orders', [
                ['fieldPath' => 'userId', 'op' => 'EQUAL', 'value' => $uid]
            ]);

            if (isset($orders['documents'])) {
                foreach ($orders['documents'] as $order) {
                    $orderId = basename($order['name']);
                    $this->firebaseService->deleteDocument('orders', $orderId);
                }
            }

            // Delete user's device tokens
            $tokens = $this->firebaseService->runStructuredQuery('deviceTokens', [
                ['fieldPath' => 'userId', 'op' => 'EQUAL', 'value' => $uid]
            ]);

            if (isset($tokens['documents'])) {
                foreach ($tokens['documents'] as $token) {
                    $tokenId = basename($token['name']);
                    $this->firebaseService->deleteDocument('deviceTokens', $tokenId);
                }
            }

            // Add more collections as needed (addresses, favorites, etc.)
            
            Log::info("User data deleted from Firestore for UID: {$uid}");

        } catch (\Exception $e) {
            Log::error("Error deleting user data from Firestore: " . $e->getMessage());
            // Continue even if Firestore deletion fails, as Auth deletion is more critical
        }
    }
}
