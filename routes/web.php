
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;

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
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/orders', [AdminController::class, 'orders']);
    Route::get('/menu', [AdminController::class, 'menu']);
    Route::get('/staff', [AdminController::class, 'staff']);
    Route::get('/reports', [AdminController::class, 'reports']);
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::get('/settings', [AdminController::class, 'settings']);
});


Route::get('/test-admin', fn() => 'Admin Middleware Works!')->middleware('admin');

Route::get('/debug-middleware', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $aliases = $kernel->getMiddlewareAliases();
    return response()->json($aliases);
});