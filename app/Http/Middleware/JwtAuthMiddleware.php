<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use App\Models\User;
use App\Services\TokenBlacklistService;

class JwtAuthMiddleware
{
    protected TokenBlacklistService $blacklistService;

    public function __construct(TokenBlacklistService $blacklistService)
    {
        $this->blacklistService = $blacklistService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Token não fornecido ou inválido.'], 401);
        }

        $token = substr($header, 7);

        if ($this->blacklistService->isBlacklisted($token)) {
            return response()->json(['message' => 'Token inválido (logout realizado).'], 401);
        }

        try {
            $key = config('app.jwt_secret');
            if (!$key) {
                throw new Exception('Chave JWT não configurada no servidor.');
            }

            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            $user = User::find($decoded->sub);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 401);
            }

            $request->setUserResolver(function () use ($user) {
                return $user;
            });

        } catch (Exception $e) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return $next($request);
    }
}