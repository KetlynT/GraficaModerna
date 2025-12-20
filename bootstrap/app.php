<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\JwtBlacklistMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // --- MANTENDO SUA LÓGICA DE PREFIXO ---
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $prefix = env('API_PREFIX', 'api/v1'); // Mantém sua lógica original
            
            Route::middleware('api')
                ->prefix($prefix)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Middlewares Globais (já existentes no seu código)
        $middleware->append(SecurityHeaders::class);

        // 2. Registrando os Aliases (Apelidos) para usar no api.php
        $middleware->alias([
            'auth.jwt'      => JwtAuthMiddleware::class,      // Autentica (Lê o token)
            'jwt.blacklist' => JwtBlacklistMiddleware::class, // Segurança (Checa blacklist)
            'admin'         => AdminMiddleware::class,        // Permissão (Checa role)
            'throttle'      => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // 3. Proxies (Mantido do seu arquivo)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();