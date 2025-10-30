<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
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

        // Save useful info in session
        session([
            'firebase_id_token' => $data['idToken'] ?? null,
            'firebase_refresh_token' => $data['refreshToken'] ?? null,
            'firebase_user' => [
                'uid' => $data['localId'] ?? null,
                'email' => $data['email'] ?? null,
            ],
        ]);

        return redirect()->intended('/admin')->with('status', 'Logged in with Firebase');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['firebase_id_token', 'firebase_refresh_token', 'firebase_user']);
        return redirect('/login')->with('status', 'Logged out successfully');
    }
}
