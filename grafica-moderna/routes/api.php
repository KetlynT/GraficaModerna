<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\StripeWebhookController;

// -- Público --
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::get('content/settings', [ContentController::class, 'getSettings']);

// -- Protegido (Usuário Logado) --
Route::middleware('jwt.auth')->group(function () {
    // Carrinho
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::put('cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('cart', [CartController::class, 'clear']);

    // Pedidos
    Route::post('checkout', [OrderController::class, 'checkout']);
    Route::get('my-orders', [OrderController::class, 'index']);
    
    // Endereços
    Route::apiResource('addresses', AddressController::class);
    
    // Frete
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);
});

Route::middleware('jwt.auth')->group(function () {
    
    // Rotas de Usuário
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'getProfile']);

    // Carrinho
    Route::get('cart', [CartController::class, 'index']);
    // ... outras rotas do carrinho ...

    // Admin (Encadeamento de middlewares: primeiro valida JWT, depois checa Admin)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    
    Route::post('content/settings', [ContentController::class, 'updateSettings']);
        // ... outras rotas admin ...
    });
});