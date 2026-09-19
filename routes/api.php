<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use App\Http\Controllers\Courier;
use App\Services\QPayService;
use Illuminate\Support\Facades\Route;

// ---------- Public ----------
Route::get('/config', fn () => response()->json([
    'name' => config('shop.name'),
    'currency' => config('shop.currency'),
    'shipping_fee' => config('shop.shipping_fee'),
    'free_shipping_threshold' => config('shop.free_shipping_threshold'),
    'cities' => config('shop.cities'),
    'districts' => config('shop.districts'),
    'qpay_mock' => app(QPayService::class)->isMock(),
]));

Route::get('/home', [Api\CatalogController::class, 'home']);
Route::get('/categories', [Api\CatalogController::class, 'categories']);
Route::get('/products', [Api\CatalogController::class, 'products']);
Route::get('/products/filters', [Api\CatalogController::class, 'filters']);
Route::get('/products/{slug}', [Api\CatalogController::class, 'show']);

Route::get('/cart', [Api\CartController::class, 'show']);
Route::post('/cart/items', [Api\CartController::class, 'add']);
Route::patch('/cart/items/{item}', [Api\CartController::class, 'update']);
Route::delete('/cart/items/{item}', [Api\CartController::class, 'remove']);
Route::delete('/cart', [Api\CartController::class, 'clear']);

Route::post('/auth/register', [Api\AuthController::class, 'register']);
Route::post('/auth/login', [Api\AuthController::class, 'login']);
Route::get('/payments/qpay/callback', [Api\PaymentController::class, 'callback']);
Route::get('/verify/callback/{token}', [Api\VerifyController::class, 'callback']);

// ---------- Authenticated ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [Api\AuthController::class, 'logout']);
    Route::get('/auth/me', [Api\AuthController::class, 'me']);
    Route::put('/auth/profile', [Api\AuthController::class, 'updateProfile']);

    Route::get('/account/dashboard', [Api\AccountController::class, 'dashboard']);
    Route::get('/verify/status', [Api\VerifyController::class, 'status']);
    Route::post('/verify/start', [Api\VerifyController::class, 'start']);
    Route::get('/verify/sessions/{sessionId}/check', [Api\VerifyController::class, 'check']);
    Route::post('/verify/sessions/{sessionId}/mock-confirm', [Api\VerifyController::class, 'mockConfirm']);

    Route::get('/orders', [Api\OrderController::class, 'index']);
    Route::post('/orders', [Api\OrderController::class, 'store']);
    Route::get('/orders/{orderNumber}', [Api\OrderController::class, 'show']);
    Route::post('/orders/{orderNumber}/cancel', [Api\OrderController::class, 'cancel']);

    Route::post('/orders/{orderNumber}/payment/invoice', [Api\PaymentController::class, 'invoice']);
    Route::get('/orders/{orderNumber}/payment/check', [Api\PaymentController::class, 'check']);
    Route::post('/orders/{orderNumber}/payment/simulate', [Api\PaymentController::class, 'simulate']);

    // ---------- Admin ----------
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index']);
        Route::apiResource('categories', Admin\CategoryController::class)->except('show');
        Route::post('/products/upload', [Admin\ProductController::class, 'uploadImage']);
        Route::apiResource('products', Admin\ProductController::class);
        Route::get('/orders', [Admin\OrderController::class, 'index']);
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus']);
        Route::patch('/orders/{order}/payment', [Admin\OrderController::class, 'updatePayment']);
        Route::patch('/orders/{order}/courier', [Admin\OrderController::class, 'assignCourier']);
        Route::apiResource('users', Admin\UserController::class)->except('show');
    });

    // ---------- Courier ----------
    Route::prefix('courier')->middleware('role:courier,admin')->group(function () {
        Route::get('/deliveries', [Courier\DeliveryController::class, 'index']);
        Route::get('/deliveries/{order}', [Courier\DeliveryController::class, 'show']);
        Route::patch('/deliveries/{order}/status', [Courier\DeliveryController::class, 'updateStatus']);
    });
});
