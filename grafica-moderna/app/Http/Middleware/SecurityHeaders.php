<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Gera o Nonce para Scripts (igual ao C#)
        $nonce = Str::random(32);
        
        // Passa o nonce para a View (se usar Blade) ou Headers
        // No caso de API Rest com React, o CSP é mais complexo pois o HTML é servido pelo Next.js
        // Mas se a API servir algum conteúdo ou para proteger o endpoint:
        
        $response = $next($request);

        // Headers idênticos ao seu Program.cs
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // CSP (Adaptado do seu C#)
        // Nota: Em API pura (JSON), CSP é menos crítico que no Frontend, 
        // mas é boa prática para endpoints que retornam HTML ou arquivos.
        $csp = "default-src 'self'; " .
               "script-src 'self' 'nonce-{$nonce}' https://js.stripe.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "img-src 'self' data: https: blob:; " .
               "connect-src 'self' https://api.stripe.com; " .
               "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}