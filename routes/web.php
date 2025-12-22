<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdersController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/perfil/pedidos', [OrdersController::class, 'index'])->name('profile.orders');
    Route::post('/orders/{id}/refund-request', [OrdersController::class, 'requestRefund'])->name('orders.request_refund');
});