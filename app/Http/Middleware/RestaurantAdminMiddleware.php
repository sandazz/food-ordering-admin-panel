<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\FirebaseService;

class RestaurantAdminMiddleware
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $role = session('role');
        if ($role === 'restaurant_admin') {
            $uid = session('firebase_user.uid');
            if ($uid) {
                $userDoc = $this->firebase->getDocument('users', $uid);
                $f = $userDoc['fields'] ?? [];
                $rid = $f['restaurantId']['stringValue'] ?? null;
                if ($rid) {
                    // Force restaurant context for restaurant admins
                    session(['restaurantId' => $rid]);
                }
            }

            // Block actions that change restaurant context or global system
            $routeName = optional($request->route())->getName();
            $blocked = [
                'settings.context.save',
                'settings.restaurants',
                'settings.restaurants.create',
                'settings.restaurants.store',
                'settings.restaurants.destroy',
                'settings.system',
                'settings.system.save',
                'settings.gdpr.delete_user',
                'settings.gdpr.consents.export',
            ];
            if ($routeName && in_array($routeName, $blocked, true)) {
                abort(403, 'Restaurant admin cannot access this area');
            }

            // Enforce that any restaurantId in route/input matches assigned one
            $assignedRid = session('restaurantId');
            $targetRid = $request->route('restaurantId') ?? $request->input('restaurantId');
            if ($targetRid && $assignedRid && $targetRid !== $assignedRid) {
                abort(403, 'Restaurant admin can only manage their assigned restaurant');
            }
        }
        return $next($request);
    }
}
