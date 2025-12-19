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
    // Lógica do Pepper igual ao anterior
    private function pepperPassword(string $password): string
    {
        $pepper = env('SECURITY_PEPPER');
        return $password . $pepper;
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($this->pepperPassword($data['password']), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        if (isset($data['isAdminLogin']) && $data['isAdminLogin'] && $user->role !== 'Admin') {
            throw ValidationException::withMessages(['email' => 'Acesso não autorizado.']);
        }

        return $this->generateAuthResponse($user);
    }

    public function refreshToken(string $refreshTokenRaw)
    {
        // Busca usuário pelo token hash e validade
        $user = User::where('refresh_token_expiry', '>', now())->get()
            ->first(fn($u) => Hash::check($refreshTokenRaw, $u->refresh_token_hash));

        if (!$user) {
            throw new \Exception("Refresh Token inválido ou expirado.");
        }

        return $this->generateAuthResponse($user);
    }

    public function register(array $data)
    {
        $pepperedPassword = $this->pepperPassword($data['password']);

        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => Hash::make($pepperedPassword),
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User'
        ]);

        return $this->generateAuthResponse($user);
    }

    private function generateAuthResponse(User $user)
    {
        // 1. Gerar JWT Manualmente (Igual ao .NET)
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 15); // 15 minutos
        
        $payload = [
            'iss' => config('app.url'),      // Issuer
            'sub' => $user->id,              // Subject (ID do usuário)
            'iat' => $issuedAt,              // Issued At
            'exp' => $expirationTime,        // Expiration
            'role' => $user->role,           // Custom Claim
            'email' => $user->email          // Custom Claim
        ];

        $jwt = JWT::encode($payload, env('JWT_SECRET'), env('JWT_ALGO', 'HS256'));

        // 2. Gerar Refresh Token
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
            'expiresIn' => 900 // 15 min em segundos
        ];
    }
}