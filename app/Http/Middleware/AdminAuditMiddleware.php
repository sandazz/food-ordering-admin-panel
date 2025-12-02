<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\FirebaseService;

class AdminAuditMiddleware
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            // Only log write actions
            if (in_array($request->method(), ['POST','PUT','PATCH','DELETE'], true)) {
                $role = (string) session('role');
                $uid = (string) session('firebase_user.uid');
                $userEmail = (string) session('firebase_user.email');
                $restaurantId = (string) session('restaurantId');
                $branchId = (string) session('branchId');
                $routeName = optional($request->route())->getName() ?? $request->path();
                $ip = $request->ip();

                // Filter out sensitive fields
                $input = $request->except(['password','password_confirmation','_token']);

                $data = [
                    'uid' => $uid,
                    'userEmail' => $userEmail,
                    'role' => $role,
                    'restaurantId' => $restaurantId,
                    'branchId' => $branchId,
                    'method' => $request->method(),
                    'route' => $routeName,
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'ip' => $ip,
                    'params' => $input,
                    'createdAt' => now()->toIso8601String(),
                ];

                // Attach optional before/after snapshots if controller provided them
                $before = $request->attributes->get('audit_before');
                $after = $request->attributes->get('audit_after');
                if (is_array($before) && !empty($before)) {
                    $data['before'] = $before;
                }
                if (is_array($after) && !empty($after)) {
                    $data['after'] = $after;
                }

                // Use top-level collection for centralized querying
                $this->firebase->createDocument('audit_logs', $data);
            }
        } catch (\Throwable $e) {
            // Swallow audit errors to not affect main request
        }

        return $response;
    }
}
