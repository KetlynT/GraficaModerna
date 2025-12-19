<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RoutePrefixServiceProvider extends ServiceProvider
{
    /**
     * Prefixo global equivalente ao RoutePrefixConvention do ASP.NET Core
     */
    protected string $globalPrefix = 'api';

    public function boot(): void
    {
        parent::boot();

        Route::macro('withGlobalApiPrefix', function (callable $routes) {
            Route::prefix($this->globalPrefix)
                ->middleware('api')
                ->group($routes);
        });
    }
}
