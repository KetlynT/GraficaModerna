<?php

namespace App\Http\Middleware;

use App\Services\TokenBlacklistService;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class JwtBlacklistMiddleware
{
    protected TokenBlacklistService $blacklistService;

    public function __construct(TokenBlacklistService $blacklistService)
    {
        $this->blacklistService = $blacklistService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        // Mesmo comportamento do .NET:
        // se não tem token, deixa seguir (Authorize decide depois)
        if (!$token) {
            return $next($request);
        }

        if (!$this->isTokenValid($token)) {
            return response()->json([
                'message' => 'Token inválido ou expirado.'
            ], 401);
        }

        return $next($request);
    }

    private function isTokenValid(string $token): bool
    {
        // Blacklist (equivalente ao ITokenBlacklistService)
        if ($this->blacklistService->isBlacklisted($token)) {
            return false;
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key(config('app.jwt_secret'), 'HS256')
            );

            // Expiração
            if (!isset($decoded->exp) || $decoded->exp < time()) {
                return false;
            }

            // Claims obrigatórias (igual ao middleware .NET)
            if (!isset($decoded->sub) && !isset($decoded->nameid)) {
                return false;
            }

            if (!isset($decoded->email)) {
                return false;
            }

            if (!isset($decoded->role)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('JWT inválido', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function extractToken(Request $request): ?string
    {
        // Authorization: Bearer xxx
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        // Cookie jwt (igual ao ASP.NET)
        return $request->cookie('jwt');
    }
}
