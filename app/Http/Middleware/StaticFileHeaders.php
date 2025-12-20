<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaticFileHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('storage/*') || $request->is('uploads/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}