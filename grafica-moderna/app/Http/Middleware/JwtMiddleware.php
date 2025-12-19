<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\TokenBlacklistService; // Importar

class JwtMiddleware
{
    protected $blacklistService;

    public function __construct(TokenBlacklistService $blacklistService)
    {
        $this->blacklistService = $blacklistService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('jwt') ?? $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        // 1. CHECAGEM DE BLACKLIST (Segurança)
        if ($this->blacklistService->isBlacklisted($token)) {
            return response()->json(['message' => 'Token revogado ou inválido.'], 401);
        }

        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET', 'secret'), 'HS256'));
            
            // Opcional: Validar se user ainda existe/está ativo
            $user = User::find($credentials->sub);
            if (!$user) throw new \Exception();

            Auth::login($user);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Token inválido ou expirado'], 401);
        }

        return $next($request);
    }
}