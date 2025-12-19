<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    // Simula o PepperedPasswordHasher do C#
    private function pepperPassword(string $password): string
    {
        $pepper = env('SECURITY_PEPPER', 'DefaultPepperKey'); 
        return $password . $pepper;
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        // Verifica Senha com Pepper
        if (!$user || !Hash::check($this->pepperPassword($data['password']), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        // Verifica Permissão de Admin
        if (isset($data['isAdminLogin']) && $data['isAdminLogin'] && $user->role !== 'Admin') {
            throw ValidationException::withMessages(['email' => 'Acesso não autorizado.']);
        }

        return $this->generateAuthResponse($user);
    }

    public function register(array $data)
    {
        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => Hash::make($this->pepperPassword($data['password'])), // Hash(Senha + Pepper)
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User'
        ]);

        return $this->generateAuthResponse($user);
    }

    public function refreshToken(string $refreshTokenRaw)
    {
        // Busca usuário pelo hash do refresh token (igual ao C# Identity)
        // OBS: Em produção com muitos usuários, considere indexar ou usar ID + Token
        $user = User::where('refresh_token_expiry', '>', now())->get()
            ->first(fn($u) => Hash::check($refreshTokenRaw, $u->refresh_token_hash));

        if (!$user) {
            throw new \Exception("Refresh Token inválido ou expirado.");
        }

        return $this->generateAuthResponse($user);
    }

    private function generateAuthResponse(User $user)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 15); // 15 minutos
        
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'role' => $user->role,
            'email' => $user->email
        ];

        // Gera JWT usando HS256
        $jwt = JWT::encode($payload, env('JWT_SECRET', 'secret'), 'HS256');

        // Gera Refresh Token Opaco
        $refreshTokenRaw = Str::random(64);
        
        $user->update([
            'refresh_token_hash' => Hash::make($refreshTokenRaw),
            'refresh_token_expiry' => Carbon::now()->addDays(7)
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->full_name,
                'role' => $user->role
            ],
            'accessToken' => $jwt,
            'refreshToken' => $refreshTokenRaw,
            'expiresIn' => 900
        ];
    }
}