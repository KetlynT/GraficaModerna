<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    public function register(array $data)
    {
        // ... (Lógica de validação de settings igual C#)
        
        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => $this->securityService->hashPassword($data['password']), // Usa o Peppered Hasher
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User',
            'email_confirmed' => false
        ]);

        // Enviar Email de Confirmação
        try {
            $token = Str::random(64);
            $user->confirmation_token = $token;
            $user->save();

            $link = config('app.frontend_url') . "/confirm-email?userid={$user->id}&token={$token}";

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
        $user = User::where('email', $data['email'])->first();

        if (!$user || !$this->securityService->verifyPassword($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        if (isset($data['isAdminLogin']) && $data['isAdminLogin'] && $user->role !== 'Admin') {
            throw ValidationException::withMessages(['email' => 'Acesso não autorizado.']);
        }

        // Verifica Rehash (Rotação de Pepper)
        if ($this->securityService->needsRehash($user->password)) {
            $user->password = $this->securityService->hashPassword($data['password']);
            $user->save();
        }

        // Alerta de Login
        $this->emailService->send($user->email, 'LoginAlert', [
            'name' => $user->full_name,
            'date' => now()->format('d/m/Y H:i')
        ]);

        return $this->generateAuthResponse($user);
    }

    public function forgotPassword(string $email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) return; // Silencioso por segurança

        $token = Str::random(64);
        
        // No Laravel padrão usa-se a tabela password_reset_tokens, 
        // mas para manter simples e igual ao C# Identity customizado:
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $link = config('app.frontend_url') . "/reset-password?email={$email}&token={$token}";

        $this->emailService->send($user->email, 'ForgotPassword', [
            'name' => $user->full_name,
            'link' => $link
        ]);
    }

    public function resetPassword(array $data)
    {
        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (!$record || !Hash::check($data['token'], $record->token)) {
            throw new Exception("Token inválido ou expirado.");
        }

        // Validade de 15 min (exemplo)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw new Exception("Token expirado.");
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->password = $this->securityService->hashPassword($data['newPassword']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        $this->emailService->send($user->email, 'PasswordChanged', ['name' => $user->full_name]);
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