<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh-token', [AuthController::class, 'refreshToken']);
});

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('content/settings', [ContentController::class, 'getSettings']);
Route::get('content/pages/{slug}', [ContentController::class, 'getPage']);
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::get('coupons/validate/{code}', [CouponController::class, 'validateCode']);

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (JWT) - Usuário Logado
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.auth')->group(function () {
    
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'getProfile']);

    // Carrinho
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::put('cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('cart', [CartController::class, 'clear']);

    // Pedidos
    Route::post('checkout', [OrderController::class, 'checkout']); // Cria o Pedido
    Route::get('my-orders', [OrderController::class, 'index']);
    Route::post('orders/{id}/refund', [OrderController::class, 'requestRefund']);

    // Pagamentos (NOVO)
    // Se o seu front chama 'api/payments/checkout' passando orderId:
    Route::post('payments/checkout', [PaymentsController::class, 'createCheckoutSession']);

    // Endereços e Frete
    Route::apiResource('addresses', AddressController::class);
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    /*
    |--------------------------------------------------------------------------
    | Área Administrativa - Centralizada no AdminController
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        Route::get('dashboard', [AdminController::class, 'getDashboardData']);

        // Produtos
        Route::post('products', [AdminController::class, 'createProduct']);
        Route::put('products/{id}', [AdminController::class, 'updateProduct']);
        Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);

        // Pedidos
        Route::get('orders', [AdminController::class, 'getAllOrders']);
        Route::put('orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

        // Cupons
        Route::get('coupons', [AdminController::class, 'getCoupons']);
        Route::post('coupons', [AdminController::class, 'createCoupon']);
        Route::delete('coupons/{id}', [AdminController::class, 'deleteCoupon']);

        // Conteúdo
        Route::post('content/settings', [AdminController::class, 'updateSettings']);
        Route::post('content/pages', [AdminController::class, 'savePage']);
        
        // Templates de Email
        Route::get('email-templates', [AdminController::class, 'getEmailTemplates']);
        Route::put('email-templates/{id}', [AdminController::class, 'updateEmailTemplate']);
    });
});