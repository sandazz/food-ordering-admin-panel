<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::prefix('payments')->group(function () {
    Route::post('initiate', [PaymentController::class, 'initiate']);
    Route::get('callback', [PaymentController::class, 'callback']);
    Route::get('status', [PaymentController::class, 'status']);
});

Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('history/{restaurant_id}/{branch_id}', [PaymentController::class, 'adminHistory']);
});
