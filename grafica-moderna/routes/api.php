<?php

use Illuminate\Support\Facades\Route;

// Importação dos Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CouponsController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\Webhook\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==============================================================================
// 1. ROTAS PÚBLICAS E DE AUTENTICAÇÃO
// ==============================================================================

Route::controller(AuthController::class)->group(function () {
    // Rotas com limitação de taxa (throttle:auth definido no controller)
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
    
    // Recuperação de conta
    Route::post('/auth/forgot-password', 'forgotPassword');
    Route::post('/auth/reset-password', 'resetPassword');
    Route::post('/auth/confirm-email', 'confirmEmail');
    
    // Gestão de Sessão
    Route::post('/auth/refresh-token', 'refreshToken');
    Route::get('/auth/check', 'checkAuth');
    
    // Rotas Autenticadas de Auth
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', 'logout');
        Route::get('/auth/profile', 'getProfile');
        Route::put('/auth/profile', 'updateProfile');
    });
});

// ==============================================================================
// 2. CATÁLOGO E CONTEÚDO (PÚBLICO)
// ==============================================================================

// Produtos
Route::controller(ProductsController::class)->group(function () {
    Route::get('/products', 'index');
    Route::get('/products/{id}', 'show');
});

// Conteúdo (Páginas e Configurações)
Route::controller(ContentController::class)->group(function () {
    Route::get('/content/pages', 'getAllPages');
    Route::get('/content/pages/{slug}', 'getPage');
    Route::get('/content/settings', 'getSettings');
});

// Validação de Cupons
Route::get('/coupons/validate/{code}', [CouponsController::class, 'validateCode']);

// Cálculo de Frete
Route::controller(ShippingController::class)->group(function () {
    Route::post('/shipping/calculate', 'calculate');
    Route::get('/shipping/product/{productId}/cep/{cep}', 'calculateForProduct');
});

// ==============================================================================
// 3. ÁREA DO CLIENTE (Requer auth:api)
// ==============================================================================

Route::middleware(['auth:api'])->group(function () {

    // Carrinho (Middleware 'role:user' aplicado no construtor do controller)
    Route::controller(CartController::class)->prefix('cart')->group(function () {
        Route::get('/', 'index');
        Route::post('/items', 'addItem');
        Route::put('/items/{itemId}', 'updateItem');
        Route::delete('/items/{itemId}', 'removeItem');
        Route::delete('/', 'clear');
    });

    // Endereços (Middleware 'throttle:user-actions' aplicado no construtor)
    Route::controller(AddressController::class)->prefix('addresses')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    // Pedidos
    Route::controller(OrderController::class)->prefix('orders')->group(function () {
        Route::post('/', 'checkout');           // Criar pedido (Checkout)
        Route::get('/', 'index');               // Listar pedidos do usuário
        Route::post('/{id}/request-refund', 'requestRefund');
    });

    // Pagamentos
    Route::post('/payments/checkout-session', [PaymentsController::class, 'createCheckoutSession']);
});

// ==============================================================================
// 4. ÁREA ADMINISTRATIVA (Requer auth:api e role:admin)
// ==============================================================================

// Login Administrativo (Público, mas específico para admin)
Route::post('/admin/login', [AdminController::class, 'login']);

Route::middleware(['auth:api', 'role:admin', 'throttle:admin'])
    ->prefix('admin')
    ->controller(AdminController::class)
    ->group(function () {
        
        // Dashboard
        Route::get('/dashboard', 'dashboardStats');
        
        // Uploads
        Route::post('/upload', 'upload');
        
        // Gestão de Pedidos
        Route::get('/orders', 'getOrders');
        Route::put('/orders/{id}/status', 'updateOrderStatus');
        
        // Gestão de Produtos
        Route::get('/products', 'getProducts'); // Lista versão admin (pode incluir inativos, etc)
        Route::get('/products/{id}', 'getProductById');
        Route::post('/products', 'createProduct');
        Route::put('/products/{id}', 'updateProduct');
        Route::delete('/products/{id}', 'deleteProduct');
        
        // Gestão de Cupons
        Route::get('/coupons', 'getCoupons');
        Route::post('/coupons', 'createCoupon');
        Route::delete('/coupons/{id}', 'deleteCoupon');
        
        // Gestão de Conteúdo (CMS)
        Route::post('/content/pages', 'createPage');
        Route::put('/content/pages/{slug}', 'updatePage');
        Route::put('/settings', 'updateSettings');
        
        // Templates de Email
        Route::get('/email-templates', 'getEmailTemplates');
        Route::put('/email-templates/{id}', 'updateEmailTemplate');
    });

// ==============================================================================
// 5. WEBHOOKS (Externo)
// ==============================================================================

// Stripe Webhook (Geralmente requer configuração no VerifyCsrfToken para exceção se fosse web, mas aqui é API)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleStripe']);