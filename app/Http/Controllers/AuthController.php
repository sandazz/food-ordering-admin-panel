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
            if ($role === 'branch_admin') {
                $ridField = $fields['restaurantId'] ?? [];
                $bidField = $fields['branchId'] ?? [];
                $rid = $ridField['stringValue'] ?? null;
                $bid = $bidField['stringValue'] ?? null;
                if ($rid && $bid) {
                    session(['role' => 'branch_admin', 'restaurantId' => $rid, 'branchId' => $bid]);
                    return redirect()->intended('/admin')->with('status', 'Logged in as Branch Admin');
                }
            }
            session(['role' => $role]);
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
}
