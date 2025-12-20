<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Exception;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected $securityService;
    protected $emailService;
    protected $contentService;

    public function __construct(
        SecurityService $securityService, 
        EmailService $emailService,
        ContentService $contentService
    ) {
        $this->securityService = $securityService;
        $this->emailService = $emailService;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled()
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            throw new Exception("O sistema está em modo orçamento. Login de clientes desativado.");
        }
    }

    private function checkRegistrationEnabled()
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            throw new Exception("O cadastro de novos clientes está temporariamente suspenso.");
        }
    }

    public function register(array $data)
    {
        $this->checkRegistrationEnabled();

        // Nota: Validação de CPF/CNPJ deve ocorrer no Request usando a Rule CpfCnpj

        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            // Hash password com Pepper (via SecurityService)
            'password' => $this->securityService->hashPassword($data['password']), 
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User',
            'email_confirmed' => false
        ]);

        // Envia Email de Confirmação
        try {
            $token = Str::random(64);
            $user->confirmation_token = $token;
            $user->save();

            $encodedToken = urlencode($token);
            $link = config('app.frontend_url') . "/confirm-email?userid={$user->id}&token={$encodedToken}";

            $this->emailService->send($user->email, 'RegisterConfirmation', [
                'name' => $user->full_name,
                'link' => $link,
                'year' => date('Y')
            ]);
        } catch (Exception $e) {
            Log::error("Erro ao enviar email de cadastro: " . $e->getMessage());
        }

        return $this->generateAuthResponse($user);
    }

    public function login(array $data)
    {
        if (empty($data['isAdminLogin'])) {
            $this->checkPurchaseEnabled();
        }

        $user = User::where('email', $data['email'])->first();

        // 1. Verifica Bloqueio Temporário (Lockout)
        if ($user && $user->lockout_end && Carbon::parse($user->lockout_end)->isFuture()) {
            $timeLeft = Carbon::parse($user->lockout_end)->diffInMinutes(now());
            throw new Exception("Conta bloqueada temporariamente. Tente novamente em " . ceil($timeLeft) . " minutos.");
        }

        // 2. Verifica Credenciais
        if (!$user || !$this->securityService->verifyPassword($data['password'], $user->password)) {
            // Incrementa falhas para simular UserManager do C#
            if ($user) {
                $user->increment('access_failed_count');
                if ($user->access_failed_count >= 5) { // Limite de 5 tentativas
                    $user->lockout_end = now()->addMinutes(15);
                    $user->access_failed_count = 0;
                    $user->save();
                    throw new Exception("Muitas tentativas falhas. Conta bloqueada temporariamente.");
                }
                $user->save();
            }
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        // Sucesso: Reseta falhas
        if ($user->access_failed_count > 0) {
            $user->update(['access_failed_count' => 0, 'lockout_end' => null]);
        }

        // 3. Verifica Roles
        $isAdmin = $user->role === 'Admin';
        if (!empty($data['isAdminLogin']) && $data['isAdminLogin']) {
            if (!$isAdmin) throw new Exception("Acesso não autorizado para contas de cliente.");
        } else {
            if ($isAdmin) throw new Exception("Administradores devem acessar exclusivamente pelo Painel Administrativo.");
        }

        // 4. Rehash (Rotação de Pepper/Algoritmo se necessário)
        if ($this->securityService->needsRehash($user->password)) {
            $user->password = $this->securityService->hashPassword($data['password']);
            $user->save();
        }

        // 5. Envia Alerta de Login (Fire and forget)
        try {
            $this->emailService->send($user->email, 'LoginAlert', [
                'name' => $user->full_name,
                'date' => now()->format('d/m/Y H:i')
            ]);
        } catch (Exception $e) {}

        return $this->generateAuthResponse($user);
    }

    public function refreshToken(string $accessToken, string $refreshTokenRaw)
    {
        // Busca usuário pelo hash do refresh token (igual ao C# Identity)
        $user = User::where('refresh_token_expiry', '>', now())->get()
            ->first(function ($u) use ($refreshTokenRaw) {
                return Hash::check($refreshTokenRaw, $u->refresh_token_hash);
            });

        if (!$user) {
            throw new Exception("Refresh token inválido ou expirado.");
        }

        if ($user->lockout_end && Carbon::parse($user->lockout_end)->isFuture()) {
            throw new Exception("Conta bloqueada temporariamente.");
        }

        return $this->generateAuthResponse($user);
    }

    public function getProfile(string $userId)
    {
        return User::findOrFail($userId);
    }

    public function updateProfile(string $userId, array $data)
    {
        $user = User::findOrFail($userId);
        
        $user->full_name = $data['fullName'];
        $user->cpf_cnpj = $data['cpfCnpj']; // Validação deve vir do Request
        $user->phone_number = $data['phoneNumber'] ?? null;
        
        $user->save();
    }

    public function confirmEmail(string $userId, string $token)
    {
        $user = User::findOrFail($userId);
        
        if ($user->confirmation_token !== $token) {
            throw new Exception("Token inválido.");
        }

        $user->email_confirmed = true;
        $user->confirmation_token = null;
        $user->email_verified_at = now();
        $user->save();
    }

    public function forgotPassword(string $email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) return; // Retorno silencioso por segurança

        $token = Str::random(64);
        
        // Simula tabela de reset
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $encodedToken = urlencode($token);
        $link = config('app.frontend_url') . "/reset-password?email={$email}&token={$encodedToken}";

        $this->emailService->send($user->email, 'ForgotPassword', [
            'name' => $user->full_name,
            'link' => $link
        ]);
    }

    public function resetPassword(array $data)
    {
        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (!$record || !Hash::check($data['token'], $record->token)) {
            // Delay aleatório para mitigar timing attacks (igual C#)
            usleep(rand(100000, 300000)); 
            throw new Exception("Não foi possível redefinir a senha. Verifique os dados.");
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw new Exception("Token expirado.");
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->password = $this->securityService->hashPassword($data['newPassword']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        $this->emailService->send($user->email, 'PasswordChanged', ['name' => $user->full_name]);
    }

    private function generateAuthResponse(User $user)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 15); // 15 minutos (AccessToken)
        
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'role' => $user->role,
            'email' => $user->email,
            'unique_name' => $user->email, // claim do C#
            'jti' => Str::uuid()->toString()
        ];

        $jwt = JWT::encode($payload, config('app.jwt_secret'), 'HS256');

        $refreshTokenRaw = Str::random(64);
        
        // Atualiza Refresh Token no Banco (Hash)
        $user->refresh_token_hash = Hash::make($refreshTokenRaw);
        $user->refresh_token_expiry = Carbon::now()->addDays(7);
        $user->save();

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ],
            'accessToken' => $jwt,
            'refreshToken' => $refreshTokenRaw
        ];
    }
}