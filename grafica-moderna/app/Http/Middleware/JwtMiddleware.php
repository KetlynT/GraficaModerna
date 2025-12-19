<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Tenta pegar do Cookie (prioridade) ou Header Bearer
        $token = $request->cookie('jwt') ?? $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        try {
            // 2. Decodifica e Valida
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET'), env('JWT_ALGO', 'HS256')));
            
            // 3. Busca o usuário (Opcional: para performance, pode confiar só no token, 
            // mas buscar no banco garante que o user não foi deletado)
            $user = User::find($credentials->sub);

            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado'], 401);
            }

            // 4. Autentica o usuário na requisição atual do Laravel
            Auth::login($user);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['message' => 'Token expirado'], 401);
        } catch (Exception $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        return $next($request);
    }
}