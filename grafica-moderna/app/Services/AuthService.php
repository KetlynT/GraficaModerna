<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\EmailService; // Supondo existência
use Exception;

class AuthService
{
    protected $emailService;
    protected $contentService; // Supondo existência para configs

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
        // $this->contentService = ...;
    }

    public function register(array $data)
    {
        // Validação de CPF/CNPJ deve ser feita no Request Validation antes daqui
        
        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User'
        ]);

        // Envio de email de confirmação (Simulado)
        // $this->emailService->sendConfirmation($user);

        return $this->createTokenPair($user);
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new Exception("Credenciais inválidas.");
        }

        // Validação de Admin
        if (isset($data['isAdminLogin']) && $data['isAdminLogin'] && $user->role !== 'Admin') {
            throw new Exception("Acesso não autorizado para contas de cliente.");
        }

        return $this->createTokenPair($user);
    }

    public function refreshToken(string $refreshToken)
    {
        // Busca usuário (em cenário real, buscaríamos pelo hash ou ID no token JWT decodificado)
        // Simplificação: Laravel Sanctum cuida disso melhor, mas seguindo sua lógica manual:
        
        // A implementação manual de refresh token é complexa em PHP puro.
        // Recomendação: Use Laravel Sanctum.
        // Abaixo, lógica simulada baseada no seu C#:
        
        throw new Exception("Em Laravel, recomendo usar o middleware do Sanctum para refresh.");
    }
    
    public function updateProfile($userId, array $data)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'full_name' => $data['fullName'],
            'phone_number' => $data['phoneNumber'],
            'cpf_cnpj' => $data['cpfCnpj']
        ]);
        return $user;
    }

    private function createTokenPair(User $user)
    {
        // Usando Sanctum para gerar token simples
        // Para refresh token real, JWT-Auth é melhor, ou Sanctum com expiração
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Simulação de refresh token opaco
        $refreshToken = Str::random(64);
        $user->update([
            'refresh_token_hash' => Hash::make($refreshToken),
            'refresh_token_expiry' => now()->addDays(7)
        ]);

        return [
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'email' => $user->email,
            'role' => $user->role
        ];
    }
}