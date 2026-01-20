<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ServiceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'user'], function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('services')->group(function(){
        Route::get('/', [ServiceController::class, 'services']);

        Route::get('/products', [ServiceController::class, 'product']);
        Route::get('/products/{product_id}', [ServiceController::class, 'product_detail']);
        Route::get('/products/{product_id}/durations', [ServiceController::class, 'product_durations']);

        Route::post('/checkout-preview', [ServiceController::class, 'checkout_preview']);
        Route::post('/checkout', [ServiceController::class, 'checkout']);

        Route::get('/{service_id}/renew-options', [ServiceController::class, 'renew_options']);
        Route::post('/{service_id}/renew-preview', [ServiceController::class, 'renew_preview']);
        Route::post('/{service_id}/renew', [ServiceController::class, 'renew']);
    });

    Route::prefix('invoices')->group(function(){
        Route::get('/', [InvoiceController::class, 'invoices']);
        Route::get('/{invoice_id}', [InvoiceController::class, 'invoice_detail']);
        Route::post('/{invoice_id}/pay', [InvoiceController::class, 'invoice_pay']);
    });
});