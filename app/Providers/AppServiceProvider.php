<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

// Interfaces e Serviços
use App\Application\Interfaces as I;
use App\Services as S;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // =================================================================
        // DEPENDENCY INJECTION (DI)
        // =================================================================
        
        // Security Services
        $this->app->scoped(S\MetadataSecurityService::class);

        // HTML Sanitizer (Singleton configurado)
        $this->app->singleton(HtmlSanitizer::class, function () {
            return new HtmlSanitizer(
                (new HtmlSanitizerConfig())
                    ->allowSafeElements()
                    ->allowRelativeLinks()
                    ->allowAttribute('class', '*')
                    ->allowAttribute('style', '*')
            );
        });
    }

    public function boot(): void
    {
        // =================================================================
        // HTTP CLIENT MACROS (Melhor Envio Resilience)
        // =================================================================
        
        Http::macro('melhorEnvio', function () {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => env('MELHOR_ENVIO_USER_AGENT'),
            ])
            ->withToken(env('MELHOR_ENVIO_TOKEN'))
            ->baseUrl(env('MELHOR_ENVIO_URL'))
            ->timeout(10) // 10 seconds total timeout
            ->retry(3, 100); // 3 retries, 100ms delay (Simple Circuit Breaker equivalent)
        });

        // =================================================================
        // RATE LIMITING
        // =================================================================

        // Global
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Auth (Login/Register)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->ip());
        });

        // Upload
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Shipping
        RateLimiter::for('shipping', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
        
        // Payment
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->user()?->id ?: $request->ip());
        });

        // Admin
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // User Actions
        RateLimiter::for('user-actions', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // =================================================================
        // PASSWORD DEFAULTS (Identity)
        // =================================================================
        
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}