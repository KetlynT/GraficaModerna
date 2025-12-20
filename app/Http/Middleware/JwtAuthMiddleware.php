<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use App\Models\User;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Token não fornecido ou inválido.'], 401);
        }

        $token = substr($header, 7);

        try {
            $key = config('app.jwt_secret');
            if (!$key) {
                throw new Exception('Chave JWT não configurada no servidor.');
            }

            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Opcional: Verificar se o user ainda existe no banco
            $user = User::find($decoded->sub);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 401);
            }

            // Injeta o usuário na requisição para ser usado nos Controllers ($request->user())
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

        } catch (Exception $e) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return $next($request);
    }
}