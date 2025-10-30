
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

// Redirect root to admin dashboard if logged in, otherwise to login
Route::get('/', function() {
    return session('firebase_user') ? redirect('/admin') : redirect('/login');
});

Route::get('/firebase-test', [FirebaseController::class, 'test']);

// Auth (Firebase email/password)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin panel routes (all require admin login)
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    // Menu Categories & Items
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
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

    // Staff CRUD
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staffId}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staffId}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staffId}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [NotificationsController::class, 'send'])->name('notifications.send');

    // Settings and Context
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/context', [SettingsController::class, 'context'])->name('settings.context');
    Route::post('/settings/context', [SettingsController::class, 'saveContext'])->name('settings.context.save');
    Route::post('/branch/select', [SettingsController::class, 'setBranch'])->name('branch.select');
    Route::post('/branch/clear', [SettingsController::class, 'clearBranch'])->name('branch.clear');

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
});


Route::get('/test-admin', fn() => 'Admin Middleware Works!')->middleware('admin');

Route::get('/debug-middleware', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $aliases = $kernel->getMiddlewareAliases();
    return response()->json($aliases);
});