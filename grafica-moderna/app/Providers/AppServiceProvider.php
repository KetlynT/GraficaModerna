<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Global (Proteção geral)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Auth Policy (Login/Register - Igual ao seu C#)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->ip()); // 10 tentativas a cada 5 min
        });

        // Shipping Policy (Cálculo de frete custa dinheiro/processamento)
        RateLimiter::for('shipping', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
        
        // Upload Policy
         RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}