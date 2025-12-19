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
        // 1. Global (300/1min)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // 2. Auth (Login/Register - 10/5min)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->ip());
        });

        // 3. Shipping (15/1min)
        RateLimiter::for('shipping', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Upload (5/1min)
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // 5. Payment (10/5min - checkout sessions)
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->user()?->id ?: $request->ip());
        });

        // 6. Strict Payment (5/5min - uso crítico)
        RateLimiter::for('strict_payment', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->user()?->id ?: $request->ip());
        });

        // 7. Admin (20/1min)
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // 8. User Actions (60/1min - endereços, perfil)
        RateLimiter::for('user_action', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 9. Webhook (20/1min)
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}