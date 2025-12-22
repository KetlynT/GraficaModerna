<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\StripeWebhookController;

Route::post('webhook/stripe', [StripeWebhookController::class, 'handleWebhook']);

Route::middleware(['api', 'throttle:60,1'])->group(function () {
    
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('auth/confirm-email', [AuthController::class, 'confirmEmail']);
        
        Route::get('auth/check-auth', [AuthController::class, 'checkAuth']);
    });

    Route::get('products', [ProductsController::class, 'index']);
    Route::get('products/{id}', [ProductsController::class, 'show']);

    Route::get('content/{slug}', [ContentController::class, 'show']);

    Route::middleware('throttle:10,1')->post('admin/auth/login', [AdminController::class, 'login']);
});

Route::middleware(['api', 'auth.jwt', 'jwt.blacklist', 'throttle:100,1'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::get('auth/profile', [AuthController::class, 'me']); 
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::patch('items/{id}', [CartController::class, 'updateItem']); 
        Route::delete('items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clearCart']);
    });

    Route::apiResource('addresses', AddressController::class);

    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    Route::post('orders', [OrdersController::class, 'checkout']);
    Route::post('orders/checkout', [OrdersController::class, 'checkout']);
    Route::get('orders', [OrdersController::class, 'index']);
    Route::get('orders/{id}', [OrdersController::class, 'show']);
    Route::post('orders/{id}/request-refund', [OrdersController::class, 'requestRefund']);
});

Route::middleware(['api', 'auth.jwt', 'jwt.blacklist', 'admin', 'throttle:100,1'])->prefix('admin')->group(function () {

    Route::get('dashboard/stats', [AdminController::class, 'dashboardStats']);

    Route::post('upload', [AdminController::class, 'upload']);

    Route::get('orders', [AdminController::class, 'getOrders']);
    Route::patch('orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

    Route::get('products', [AdminController::class, 'getProducts']);
    Route::get('products/{id}', [AdminController::class, 'getProductById']);
    Route::post('products', [AdminController::class, 'createProduct']);
    Route::put('products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);

    Route::get('coupons', [AdminController::class, 'getCoupons']);
    Route::post('coupons', [AdminController::class, 'createCoupon']);
    Route::delete('coupons/{id}', [AdminController::class, 'deleteCoupon']);

    Route::get('content/pages', [AdminController::class, 'getAllPages']);
    Route::get('content/pages/{slug}', [AdminController::class, 'getPageBySlug']);
    Route::post('content/pages', [AdminController::class, 'createPage']);
    Route::put('content/pages/{slug}', [AdminController::class, 'updatePage']);
    Route::post('content/settings', [AdminController::class, 'updateSettings']);

    Route::get('email-templates', [AdminController::class, 'getEmailTemplates']);
    Route::get('email-templates/{id}', [AdminController::class, 'getEmailTemplateById']);
    Route::put('email-templates/{id}', [AdminController::class, 'updateEmailTemplate']);
});