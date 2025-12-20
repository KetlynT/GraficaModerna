<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtBlacklistMiddleware;
use App\Http\Middleware\ExceptionMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Substituímos o carregamento padrão 'api' para aplicar o prefixo dinâmico do .env
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $prefix = env('API_PREFIX', 'api/v1'); // Pega API_SEGMENT_KEY
            
            Route::middleware('api')
                ->prefix($prefix)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Middlewares Globais (Rodam em toda requisição, igual ao app.Use... no C#)
        $middleware->append(SecurityHeaders::class); // CSP, X-Frame, etc.

        // 2. Aliases para uso nas rotas (middleware('role:admin'))
        $middleware->alias([
            'auth.jwt'   => JwtBlacklistMiddleware::class, // Validação JWT customizada
            'role'       => AdminMiddleware::class,        // Validação de Role
            'throttle'   => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // 3. Configuração de Proxies (app.UseForwardedHeaders no C#)
        $middleware->trustProxies(at: '*');
        
        // 4. Middlewares de API padrão
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Registra o middleware de tratamento de erro global customizado
        // No Laravel 11, o ideal é customizar o render ou usar reportable, 
        // mas para manter a lógica do seu ExceptionMiddleware.php existente:
    })
    ->create();