<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    // Helper privado para aplicar o Pepper
    private function pepperPassword(string $password): string
    {
        $pepper = config('app.security_pepper', env('SECURITY_PEPPER'));
        if (empty($pepper)) {
            Log::warning("Security Pepper is not configured!");
        }
        // HMAC é mais seguro que concatenação simples, mas para manter igual ao C# (concatenação):
        // Se no C# era: password + pepper, use:
        return $password . $pepper;
    }

    public function register(array $data)
    {
        // Aplica o Pepper antes de fazer o Hash
        $pepperedPassword = $this->pepperPassword($data['password']);

        $user = User::create([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => Hash::make($pepperedPassword), // Hash do (senha + pepper)
            'cpf_cnpj' => $data['cpfCnpj'],
            'phone_number' => $data['phoneNumber'] ?? null,
            'role' => 'User' // Padrão
        ]);

        // Envia email de boas vindas
        // $this->emailService->send($user->email, 'REGISTRATION_CONFIRMATION', ['fullName' => $user->full_name]);

        return $this->generateAuthResponse($user);
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        // Verifica: Usuário existe E Hash(Senha + Pepper) bate com o banco
        if (!$user || !Hash::check($this->pepperPassword($data['password']), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        if (isset($data['isAdminLogin']) && $data['isAdminLogin'] && $user->role !== 'Admin') {
            throw ValidationException::withMessages(['email' => 'Acesso não autorizado para esta área.']);
        }

        return $this->generateAuthResponse($user);
    }

    public function refreshToken(string $refreshToken)
    {
        // 1. Achar o usuário que tem esse hash de refresh token
        // Como o token no banco é hash, e o cookie é o "segredo", precisamos verificar um a um ou mudar a estratégia.
        // ESTRATÉGIA MAIS SEGURA E PERFORMÁTICA (Igual ao .NET Identity):
        // O Refresh Token no banco não deve ser hasheado se for usado para busca direta, 
        // OU salvamos o Token ID e o Token Secret separados.
        
        // Simulação da lógica do .NET (Buscar pelo Hash exato ou Token):
        // Vamos assumir que você busca pelo usuário que tem esse refresh token salvo.
        
        // OBS: Para performance, recomendo salvar o token encriptado mas buscável, ou hash simples.
        // Aqui buscaremos pelo hash (verifique se sua migration suporta índices no refresh_token_hash)
        
        // Como Hash::make é unidirecional, não dá pra buscar "where hash = input".
        // Solução: O RefreshToken deve ser salvo "cru" ou buscar o user pelo ID no JWT expirado.
        
        // Vamos supor que passamos o ID do user no request ou decodificamos do JWT expirado.
        // Se não tiver o ID, essa implementação fica lenta (scan table).
        // Vamos simplificar: O Refresh Token será opaco e salvo direto (com data de expiração).
        
        $user = User::where('refresh_token_hash', $refreshToken)
                    ->where('refresh_token_expiry', '>', now())
                    ->first();

        if (!$user) {
            throw new \Exception("Refresh Token inválido ou expirado.");
        }

        // Rotação de Token (Gera um novo e invalida o anterior)
        return $this->generateAuthResponse($user);
    }

    private function generateAuthResponse(User $user)
    {
        // Limpa tokens antigos (opcional, para manter sessão única)
        $user->tokens()->delete();

        // Cria Access Token (JWT Like via Sanctum)
        $accessToken = $user->createToken('access_token')->plainTextToken;

        // Cria Refresh Token (Opaco) e salva no banco
        $refreshToken = Str::random(64);
        
        $user->update([
            'refresh_token_hash' => $refreshToken, // Salva direto para poder buscar (ou use hash SHA256 se quiser mais segurança)
            'refresh_token_expiry' => Carbon::now()->addDays(7)
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->full_name,
                'role' => $user->role
            ],
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ];
    }
}