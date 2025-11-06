<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\FirebaseService;

class BranchAdminMiddleware
{
    protected FirebaseService $firebase;
    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $role = session('role');
        if ($role === 'branch_admin') {
            $uid = session('firebase_user.uid');
            if ($uid) {
                $userDoc = $this->firebase->getDocument('users', $uid);
                $f = $userDoc['fields'] ?? [];
                $rid = $f['restaurantId']['stringValue'] ?? null;
                $bid = $f['branchId']['stringValue'] ?? null;
                if ($rid && $bid) {
                    // Force context
                    session(['restaurantId' => $rid, 'branchId' => $bid]);
                }
            }
            // Block settings and context manipulation for branch admins
            $routeName = optional($request->route())->getName();
            $blocked = [
                'settings.index','settings.system','settings.system.save',
                'settings.gdpr.delete_user','settings.gdpr.consents.export',
                'settings.restaurants','settings.restaurants.create','settings.restaurants.store',
                'settings.restaurants.edit','settings.restaurants.update','settings.restaurants.destroy',
                'settings.branches','settings.branches.create','settings.branches.store',
                'settings.branches.edit','settings.branches.update','settings.branches.destroy',
                'settings.context.save','branch.select','branch.clear',
                'menu.categories.copy.form','menu.categories.copy',
            ];
            if ($routeName && in_array($routeName, $blocked, true)) {
                abort(403, 'Branch admin cannot access this area');
            }
            // Prevent posting changes that target other branches explicitly
            $targetBranchId = $request->input('branchId') ?? $request->route('branchId');
            if ($targetBranchId && $targetBranchId !== session('branchId')) {
                abort(403, 'Branch admin can only modify own branch');
            }
        }
        return $next($request);
    }
}
