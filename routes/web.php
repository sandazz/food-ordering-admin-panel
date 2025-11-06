
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\RestaurantAdminController;
use App\Http\Controllers\SizesController;
use App\Http\Controllers\BasesController;

// Redirect root to selection if logged in without restaurant, otherwise admin or login
Route::get('/', function() {
    if (session('firebase_user')) {
        return session('restaurantId') ? redirect('/admin') : redirect()->route('settings.context');
    }
    return redirect('/login');
});

Route::get('/firebase-test', [FirebaseController::class, 'test']);

// Auth (Firebase email/password)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin panel routes
Route::prefix('admin')->middleware([\App\Http\Middleware\BranchAdminMiddleware::class, \App\Http\Middleware\RestaurantAdminMiddleware::class])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    // Menu Categories & Items
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    // Menu Sizes
    Route::get('/menu/sizes', [SizesController::class, 'index'])->name('menu.sizes.index');
    Route::get('/menu/sizes/create', [SizesController::class, 'create'])->name('menu.sizes.create');
    Route::post('/menu/sizes', [SizesController::class, 'store'])->name('menu.sizes.store');
    Route::get('/menu/sizes/{id}/edit', [SizesController::class, 'edit'])->name('menu.sizes.edit');
    Route::put('/menu/sizes/{id}', [SizesController::class, 'update'])->name('menu.sizes.update');
    Route::delete('/menu/sizes/{id}', [SizesController::class, 'destroy'])->name('menu.sizes.destroy');
    // Menu Bases
    Route::get('/menu/bases', [BasesController::class, 'index'])->name('menu.bases.index');
    Route::get('/menu/bases/create', [BasesController::class, 'create'])->name('menu.bases.create');
    Route::post('/menu/bases', [BasesController::class, 'store'])->name('menu.bases.store');
    Route::get('/menu/bases/{id}/edit', [BasesController::class, 'edit'])->name('menu.bases.edit');
    Route::put('/menu/bases/{id}', [BasesController::class, 'update'])->name('menu.bases.update');
    Route::delete('/menu/bases/{id}', [BasesController::class, 'destroy'])->name('menu.bases.destroy');
    // Categories
    Route::get('/menu/categories/create', [MenuController::class, 'createCategory'])->name('menu.categories.create');
    Route::post('/menu/categories', [MenuController::class, 'storeCategory'])->name('menu.categories.store');
    Route::get('/menu/categories/{categoryId}/edit', [MenuController::class, 'editCategory'])->name('menu.categories.edit');
    Route::put('/menu/categories/{categoryId}', [MenuController::class, 'updateCategory'])->name('menu.categories.update');
    Route::delete('/menu/categories/{categoryId}', [MenuController::class, 'destroyCategory'])->name('menu.categories.destroy');
    // Items
    Route::get('/menu/categories/{categoryId}/items/create', [MenuController::class, 'createItem'])->name('menu.items.create');
    Route::post('/menu/categories/{categoryId}/items', [MenuController::class, 'storeItem'])->name('menu.items.store');
    Route::get('/menu/categories/{categoryId}/items/{itemId}/edit', [MenuController::class, 'editItem'])->name('menu.items.edit');
    Route::put('/menu/categories/{categoryId}/items/{itemId}', [MenuController::class, 'updateItem'])->name('menu.items.update');
    Route::delete('/menu/categories/{categoryId}/items/{itemId}', [MenuController::class, 'destroyItem'])->name('menu.items.destroy');

    // Category copy between branches
    Route::get('/menu/categories/{categoryId}/copy', [MenuController::class, 'copyCategoryForm'])->name('menu.categories.copy.form');
    Route::post('/menu/categories/{categoryId}/copy', [MenuController::class, 'copyCategory'])->name('menu.categories.copy');

    // Staff CRUD
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staffId}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staffId}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staffId}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportsController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/top-items', [ReportsController::class, 'topItems'])->name('reports.top_items');
    Route::get('/reports/busy-slots', [ReportsController::class, 'busySlots'])->name('reports.busy_slots');
    Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [NotificationsController::class, 'send'])->name('notifications.send');

    // Settings and Context
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/context', [SettingsController::class, 'context'])->name('settings.context');
    Route::post('/settings/context', [SettingsController::class, 'saveContext'])->name('settings.context.save');
    Route::post('/branch/select', [SettingsController::class, 'setBranch'])->name('branch.select');
    Route::post('/branch/clear', [SettingsController::class, 'clearBranch'])->name('branch.clear');

    // UI language toggle (en|fi)
    Route::post('/lang', function (\Illuminate\Http\Request $request) {
        $lang = $request->input('lang');
        if (!in_array($lang, ['en','fi'])) { $lang = 'en'; }
        $request->session()->put('ui_lang', $lang);
        return back();
    })->name('ui.lang.set');

    Route::get('/settings/system', [SettingsController::class, 'system'])->name('settings.system');
    Route::post('/settings/system', [SettingsController::class, 'saveSystem'])->name('settings.system.save');
    Route::post('/settings/gdpr/delete-user', [SettingsController::class, 'gdprDeleteUser'])->name('settings.gdpr.delete_user');
    Route::get('/settings/gdpr/consents/export', [SettingsController::class, 'exportConsents'])->name('settings.gdpr.consents.export');

    // Restaurants CRUD
    Route::get('/settings/restaurants', [SettingsController::class, 'restaurants'])->name('settings.restaurants');
    Route::get('/settings/restaurants/create', [SettingsController::class, 'createRestaurant'])->name('settings.restaurants.create');
    Route::post('/settings/restaurants', [SettingsController::class, 'storeRestaurant'])->name('settings.restaurants.store');
    Route::get('/settings/restaurants/{restaurantId}/edit', [SettingsController::class, 'editRestaurant'])->name('settings.restaurants.edit');
    Route::put('/settings/restaurants/{restaurantId}', [SettingsController::class, 'updateRestaurant'])->name('settings.restaurants.update');
    Route::delete('/settings/restaurants/{restaurantId}', [SettingsController::class, 'destroyRestaurant'])->name('settings.restaurants.destroy');

    // Branches CRUD under restaurant
    Route::get('/settings/restaurants/{restaurantId}/branches', [SettingsController::class, 'branches'])->name('settings.branches');
    Route::get('/settings/restaurants/{restaurantId}/branches/create', [SettingsController::class, 'createBranch'])->name('settings.branches.create');
    Route::post('/settings/restaurants/{restaurantId}/branches', [SettingsController::class, 'storeBranch'])->name('settings.branches.store');
    Route::get('/settings/restaurants/{restaurantId}/branches/{branchId}/edit', [SettingsController::class, 'editBranch'])->name('settings.branches.edit');
    Route::put('/settings/restaurants/{restaurantId}/branches/{branchId}', [SettingsController::class, 'updateBranch'])->name('settings.branches.update');
    Route::delete('/settings/restaurants/{restaurantId}/branches/{branchId}', [SettingsController::class, 'destroyBranch'])->name('settings.branches.destroy');

    // Restaurant Admins management (super admin creates restaurant-scoped admins)
    Route::get('/settings/restaurant-admins', [RestaurantAdminController::class, 'index'])->name('settings.restaurant_admins');
    Route::get('/settings/restaurant-admins/create', [RestaurantAdminController::class, 'create'])->name('settings.restaurant_admins.create');
    Route::post('/settings/restaurant-admins', [RestaurantAdminController::class, 'store'])->name('settings.restaurant_admins.store');
    Route::get('/settings/restaurant-admins/{userId}/edit', [RestaurantAdminController::class, 'edit'])->name('settings.restaurant_admins.edit');
    Route::put('/settings/restaurant-admins/{userId}', [RestaurantAdminController::class, 'update'])->name('settings.restaurant_admins.update');
    Route::delete('/settings/restaurant-admins/{userId}', [RestaurantAdminController::class, 'destroy'])->name('settings.restaurant_admins.destroy');
});


Route::get('/test-admin', fn() => 'Admin Middleware Works!')->middleware('admin');

Route::get('/debug-middleware', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $aliases = $kernel->getMiddlewareAliases();
    return response()->json($aliases);
});