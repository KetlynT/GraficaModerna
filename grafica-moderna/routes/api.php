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
use App\Http\Controllers\AddressController;
use App\Http\Controllers\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==============================================================================
// 1. ROTAS PÚBLICAS E DE AUTENTICAÇÃO
// ==============================================================================

Route::controller(AuthController::class)->group(function () {
    // Autenticação
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
    Route::post('/auth/refresh-token', 'refreshToken');
    
    // Recuperação de conta
    Route::post('/auth/forgot-password', 'forgotPassword');
    Route::post('/auth/reset-password', 'resetPassword');
    Route::post('/auth/confirm-email', 'confirmEmail');
    
    // Checagem simples (usada pelo front para verificar estado)
    Route::get('/auth/check', 'checkAuth');

    // Rotas que exigem login (Logout e Perfil)
    // Usamos auth.jwt para validar e jwt.blacklist para impedir tokens antigos
    Route::middleware(['auth.jwt', 'jwt.blacklist'])->group(function () {
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

// Conteúdo (Páginas Institucionais e Configurações)
Route::controller(ContentController::class)->group(function () {
    Route::get('/content/pages', 'getAllPages');
    Route::get('/content/pages/{slug}', 'getPage');
    Route::get('/content/settings', 'getSettings');
});

// Validação de Cupons (Público para cálculo no carrinho)
Route::get('/coupons/validate/{code}', [CouponsController::class, 'validateCode']);

// Cálculo de Frete
Route::controller(ShippingController::class)->group(function () {
    Route::post('/shipping/calculate', 'calculate');
    Route::get('/shipping/product/{productId}/cep/{cep}', 'calculateForProduct');
});

// ==============================================================================
// 3. ÁREA DO CLIENTE (Requer Login)
// ==============================================================================

Route::middleware(['auth.jwt', 'jwt.blacklist'])->group(function () {

    // Carrinho
    Route::controller(CartController::class)->prefix('cart')->group(function () {
        Route::get('/', 'index');
        Route::post('/items', 'addItem');
        Route::put('/items/{itemId}', 'updateItem');
        Route::delete('/items/{itemId}', 'removeItem');
        Route::delete('/', 'clear');
    });

    // Endereços
    Route::controller(AddressController::class)->prefix('addresses')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    // Pedidos
    Route::controller(OrdersController::class)->prefix('orders')->group(function () {
        Route::post('/', 'checkout');           // Criar pedido
        Route::get('/', 'index');               // Meus pedidos
        Route::post('/{id}/request-refund', 'requestRefund');
    });

    // Pagamentos (Sessão do Stripe)
    Route::post('/payments/checkout-session', [PaymentsController::class, 'createCheckoutSession']);
});

// ==============================================================================
// 4. ÁREA ADMINISTRATIVA (Requer Login + Role Admin)
// ==============================================================================

// Login Administrativo (Separado se necessário, ou usa o login padrão)
Route::post('/admin/login', [AdminController::class, 'login']);

// Grupo protegido por Auth + Admin Middleware
Route::middleware(['auth.jwt', 'jwt.blacklist', 'admin', 'throttle:admin'])
    ->prefix('admin')
    ->controller(AdminController::class)
    ->group(function () {
        
        // Dashboard
        Route::get('/dashboard', 'dashboardStats');
        
        // Uploads (Imagens de produtos/site)
        Route::post('/upload', 'upload');
        
        // Gestão de Pedidos
        Route::get('/orders', 'getOrders');
        Route::put('/orders/{id}/status', 'updateOrderStatus');
        
        // Gestão de Produtos
        Route::get('/products', 'getProducts');
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

// Webhook do Stripe (Não usa auth, validado pela assinatura do Stripe no Controller)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleStripe']);