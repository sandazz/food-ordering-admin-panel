<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (! $apiKey) {
            return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).']);
        }

        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}";

        $response = Http::post($url, [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'returnSecureToken' => true,
        ]);

        if ($response->failed()) {
            $body = $response->json();
            $message = $body['error']['message'] ?? 'Authentication failed';
            return back()->withErrors(['firebase' => $message])->withInput();
        }

        $data = $response->json();

        session([
            'firebase_id_token' => $data['idToken'] ?? null,
            'firebase_refresh_token' => $data['refreshToken'] ?? null,
            'firebase_user' => [
                'uid' => $data['localId'] ?? null,
                'email' => $data['email'] ?? null,
            ],
        ]);

        $uid = $data['localId'] ?? null;
        if ($uid) {
            $userDoc = $firebase->getDocument('users', $uid);
            $fields = $userDoc['fields'] ?? [];
            $role = $fields['role']['stringValue'] ?? 'admin';

            // Branch Admin: lock to restaurant + branch
            if ($role === 'branch_admin') {
                $rid = $fields['restaurantId']['stringValue'] ?? null;
                $bid = $fields['branchId']['stringValue'] ?? null;
                if ($rid && $bid) {
                    session(['role' => 'branch_admin', 'restaurantId' => $rid, 'branchId' => $bid]);
                    return redirect()->intended('/admin')->with('status', 'Logged in as Branch Admin');
                }
            }

            // Restaurant Admin: lock to restaurant
            if ($role === 'restaurant_admin') {
                $rid = $fields['restaurantId']['stringValue'] ?? null;
                if ($rid) {
                    session(['role' => 'restaurant_admin', 'restaurantId' => $rid, 'branchId' => null]);
                    return redirect()->intended('/admin')->with('status', 'Logged in as Restaurant Admin');
                }
            }

            // Super Admin: full access
            if ($role === 'admin') {
                session(['role' => 'admin', 'restaurantId' => null, 'branchId' => null]);
                return redirect()->intended('/admin')->with('status', 'Logged in as Super Admin');
            }

            // Any other role: block access
            return redirect()->route('login')->withErrors(['auth' => 'Your account role is not permitted to access the admin panel.']);
        }

        if (! session('restaurantId')) {
            return redirect()->route('settings.context')->with('status', 'Please select a restaurant to continue.');
        }
        return redirect()->intended('/admin')->with('status', 'Logged in with Firebase');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['firebase_id_token', 'firebase_refresh_token', 'firebase_user', 'restaurantId', 'branchId', 'role']);
        return redirect('/login')->with('status', 'Logged out successfully');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (!$apiKey) {
            return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).']);
        }

        $url = "https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key={$apiKey}";

        // $continueUrl = app()->environment('local')
        //                 ? 'https://food-ordering-63faa.firebaseapp.com/__/auth/action?mode=resetPassword'
        //                 : url('/reset-password');

        $continueUrl = url('/reset-password');

        $payload = [
            'requestType' => 'PASSWORD_RESET',
            'email' => $request->input('email'),
            'continueUrl' => $continueUrl,
            'handleCodeInApp' => true,
        ];

        $response = Http::post($url, $payload);

        if ($response->failed()) {
            $body = $response->json();
            $message = $body['error']['message'] ?? 'Failed to send password reset email';
            return back()->withErrors(['firebase' => $message])->withInput();
        }

        return back()->with('status', 'Password reset email sent. Please check your inbox.');
    }

    public function showResetPassword(Request $request)
    {
        // Firebase app will redirect with oobCode and mode in query params
        $oobCode = $request->query('oobCode');
        $mode = $request->query('mode');
        return view('auth.reset-password', compact('oobCode', 'mode'));
    }

    public function handleResetPassword(Request $request)
    {
        $request->validate([
            'oobCode' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (!$apiKey) {
            return back()->withErrors(['firebase' => 'Firebase API key not configured (FIREBASE_API_KEY).']);
        }

        // Optionally verify code first to get email (not strictly required to reset)
        $verifyUrl = "https://identitytoolkit.googleapis.com/v1/accounts:resetPassword?key={$apiKey}";
        $verifyRes = Http::post($verifyUrl, [ 'oobCode' => $request->input('oobCode') ]);
        if ($verifyRes->failed()) {
            $msg = $verifyRes->json()['error']['message'] ?? 'Invalid or expired code';
            return back()->withErrors(['firebase' => $msg])->withInput();
        }

        // Confirm password reset with new password
        $confirmRes = Http::post($verifyUrl, [
            'oobCode' => $request->input('oobCode'),
            'newPassword' => $request->input('password'),
        ]);
        if ($confirmRes->failed()) {
            $msg = $confirmRes->json()['error']['message'] ?? 'Failed to reset password';
            return back()->withErrors(['firebase' => $msg])->withInput();
        }

        return redirect()->route('login')->with('status', 'Password has been reset. You can now login.');
    }
}
