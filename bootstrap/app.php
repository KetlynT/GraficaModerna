<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $prefix = env('API_PREFIX', 'api/v1'); 
            
            Route::middleware('api')
                ->prefix($prefix)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'auth.jwt'      => JwtAuthMiddleware::class,      
            'admin'         => AdminMiddleware::class,        
            'throttle'      => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })->create();