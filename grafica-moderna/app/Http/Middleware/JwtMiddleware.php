<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Aceita Cookie ou Header
        $token = $request->cookie('jwt') ?? $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET', 'secret'), 'HS256'));
            
            // Otimização: Se quiser evitar consulta ao banco em toda requisição, 
            // hidrate um User genérico apenas com o ID/Role do token.
            $user = User::find($credentials->sub);

            if (!$user) throw new \Exception();

            Auth::login($user);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Token inválido ou expirado'], 401);
        }

        return $next($request);
    }
}