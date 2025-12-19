<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Rotas Públicas
Route::prefix('auth')->group(function () {
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('check-auth', [AuthController::class, 'checkAuth']);
});

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);

// Rotas Protegidas (Requer Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'getProfile']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    
    // Admin routes para produtos, se houver
    // Route::post('products', ...);
});

Route::get('content/settings', [ContentController::class, 'getSettings']);
Route::get('content/pages/{slug}', [ContentController::class, 'getPage']);
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']); // Deve ser isento de CSRF se não for API-only

// -- Admin (Protegidas) --
Route::middleware(['auth:sanctum', 'admin'])->group(function () { 
    // Middleware 'admin' é um custom middleware que checa role == 'Admin'
    
    Route::get('admin/dashboard', [DashboardController::class, 'index']);
    
    Route::post('content/settings', [ContentController::class, 'updateSettings']);
    Route::post('content/pages', [ContentController::class, 'savePage']);
});