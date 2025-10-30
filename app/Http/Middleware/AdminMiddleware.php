<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('firebase_user');
        if (!$user || !isset($user['email'])) {
            return redirect('/login')->withErrors(['You must be logged in as admin.']);
        }
        return $next($request);
    }
}
