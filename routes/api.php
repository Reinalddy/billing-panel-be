<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'user'], function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('services')->group(function(){
        Route::get('/products', [ServiceController::class, 'product']);
        Route::get('/products/{product_id}', [ServiceController::class, 'product_detail']);
        Route::get('/products/{product_id}/durations', [ServiceController::class, 'product_durations']);

        Route::post('/checkout-preview', [ServiceController::class, 'checkout_preview']);
        Route::post('/checkout', [ServiceController::class, 'checkout']);
    });
});