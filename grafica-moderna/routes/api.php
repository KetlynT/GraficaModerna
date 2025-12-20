<?php

use Illuminate\Support\Facades\Route;

// Importação dos Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CouponsController; // Para uso público se houver
use App\Http\Controllers\ContentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\PaymentsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// ROTA PÚBLICA (WEBHOOK) - Sem Rate Limit restritivo, sem Auth
// =========================================================================
Route::post('webhook/stripe', [StripeWebhookController::class, 'handleWebhook']);


// =========================================================================
// ROTAS PÚBLICAS GERAIS (Throttle: 60 req/min)
// =========================================================================
Route::middleware(['api', 'throttle:60,1'])->group(function () {
    
    // Auth (Login/Register precisam de Throttle mais agressivo)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('auth/confirm-email', [AuthController::class, 'confirmEmail']);
    });

    // Catálogo
    Route::get('products', [ProductsController::class, 'index']);
    Route::get('products/{id}', [ProductsController::class, 'show']);

    // Conteúdo
    Route::get('content/{slug}', [ContentController::class, 'show']);

    // Admin Login (Endpoint específico)
    Route::middleware('throttle:10,1')->post('admin/auth/login', [AdminController::class, 'login']);
});


// =========================================================================
// ROTAS PROTEGIDAS (CLIENTE) - Requer Token JWT
// =========================================================================
Route::middleware(['api', 'auth.jwt', 'jwt.blacklist', 'throttle:100,1'])->group(function () {

    // Perfil
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    // Carrinho
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items', [CartController::class, 'updateItem']);
        Route::delete('items/{productId}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clearCart']);
    });

    // Endereços
    Route::apiResource('addresses', AddressController::class);

    // Frete
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    // Pedidos (Checkout e Histórico)
    Route::post('orders/checkout', [OrdersController::class, 'checkout']);
    Route::get('orders', [OrdersController::class, 'index']); // Histórico do usuário
    Route::get('orders/{id}', [OrdersController::class, 'show']);
    Route::post('orders/{id}/refund', [OrdersController::class, 'requestRefund']);
});


// =========================================================================
// ROTAS PROTEGIDAS (ADMINISTRADOR) - Requer Role 'Admin'
// =========================================================================
Route::middleware(['api', 'auth.jwt', 'jwt.blacklist', 'admin', 'throttle:100,1'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('dashboard/stats', [AdminController::class, 'dashboardStats']);

    // Upload
    Route::post('upload', [AdminController::class, 'upload']);

    // Pedidos (Gestão)
    Route::get('orders', [AdminController::class, 'getOrders']);
    Route::patch('orders/{id}/status', [AdminController::class, 'updateOrderStatus']);
    // Route::get('orders/{id}', [AdminController::class, 'getOrderById']); // Se necessário

    // Produtos (CRUD)
    Route::get('products', [AdminController::class, 'getProducts']); // Listagem Admin pode ter filtros diferentes
    Route::get('products/{id}', [AdminController::class, 'getProductById']);
    Route::post('products', [AdminController::class, 'createProduct']);
    Route::put('products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);

    // Cupons
    Route::get('coupons', [AdminController::class, 'getCoupons']);
    Route::post('coupons', [AdminController::class, 'createCoupon']);
    Route::delete('coupons/{id}', [AdminController::class, 'deleteCoupon']);

    // Conteúdo (CMS) & Configurações
    Route::post('content/pages', [AdminController::class, 'createPage']);
    Route::put('content/pages/{slug}', [AdminController::class, 'updatePage']);
    Route::post('content/settings', [AdminController::class, 'updateSettings']);

    // Templates de Email
    Route::get('email-templates', [AdminController::class, 'getEmailTemplates']);
    Route::get('email-templates/{id}', [AdminController::class, 'getEmailTemplateById']);
    Route::put('email-templates/{id}', [AdminController::class, 'updateEmailTemplate']);
});