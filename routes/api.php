<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::prefix('payments')->group(function () {
    Route::post('initiate', [PaymentController::class, 'initiate']);
    Route::get('callback', [PaymentController::class, 'callback']);
});

Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('history/{branch_id}', [PaymentController::class, 'adminHistory']);
});
