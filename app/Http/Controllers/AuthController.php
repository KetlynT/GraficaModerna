<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use App\Services\AuthService;
use App\Services\TokenBlacklistService;
use App\Services\ContentService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected TokenBlacklistService $blacklistService;
    protected ContentService $contentService;

    public function __construct(
        AuthService $authService,
        TokenBlacklistService $blacklistService,
        ContentService $contentService
    ) {
        $this->authService = $authService;
        $this->blacklistService = $blacklistService;
        $this->contentService = $contentService;
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        
        $result = $this->authService->register($data);

        $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

        return response()->json([
            'email'   => $result['user']['email'],
            'role'    => $result['user']['role'],
            'message' => 'Cadastro realizado com sucesso.'
        ]);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        // Define flag interna para diferenciar login de Admin vs Cliente (Lógica do C#)
        $data['isAdminLogin'] = false; 

        $result = $this->authService->login($data);

        $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

        return response()->json([
            'email'   => $result['user']['email'],
            'role'    => $result['user']['role'],
            'message' => 'Login realizado com sucesso.'
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->cookie('jwt') ?? $request->bearerToken();

        if ($token) {
            try {
                $decoded = JWT::decode($token, new Key(config('app.jwt_secret'), 'HS256'));
                if (isset($decoded->exp)) {
                    $this->blacklistService->blacklist($token, $decoded->exp);
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao processar blacklist no logout', ['message' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'Deslogado com sucesso'])
            ->withCookie(Cookie::forget('jwt'))
            ->withCookie(Cookie::forget('refreshToken'));
    }

    public function checkAuth(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['isAuthenticated' => false, 'role' => null]);
        }
        return response()->json(['isAuthenticated' => true, 'role' => $request->user()->role]);
    }

    public function getProfile(Request $request)
    {
        // Retorna camelCase via Resource
        return new UserProfileResource($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $settings = $this->contentService->getSettings();
        
        // Bloqueio de edição em modo orçamento (exceto Admin)
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            if ($request->user()->role !== 'Admin') {
                return response()->json(['message' => 'Edição de perfil desativada temporariamente.'], 403);
            }
        }

        $this->authService->updateProfile($request->user()->id, $request->validated());
        return response()->noContent();
    }

    public function refreshToken(Request $request)
    {
        $accessToken  = $request->cookie('jwt');
        $refreshToken = $request->cookie('refreshToken');

        if (!$accessToken || !$refreshToken) {
            return response()->json(['message' => 'Tokens não encontrados nos cookies.'], 400);
        }

        // Passa os tokens para o serviço validar e rotacionar
        $result = $this->authService->refreshToken($accessToken, $refreshToken);

        $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

        return response()->json([
            'email' => $result['user']['email'],
            'role'  => $result['user']['role']
        ]);
    }

    public function confirmEmail(Request $request)
    {
        $request->validate([
            'userId' => 'required|string',
            'token'  => 'required|string'
        ]);

        $this->authService->confirmEmail($request->userId, $request->token);
        
        return response()->json(['message' => 'Email confirmado com sucesso!']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $this->authService->forgotPassword($request->email);
        
        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de recuperação.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'newPassword' => 'required|min:6|confirmed'
        ]);

        $this->authService->resetPassword($data);
        
        return response()->json([
            'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.'
        ]);
    }

    private function setTokenCookies(string $access, string $refresh): void
    {
        // Access Token: 15 minutos, HttpOnly, Secure, SameSite Lax
        Cookie::queue(cookie('jwt', $access, 15, null, null, true, true, false, 'Lax'));
        
        // Refresh Token: 7 dias
        Cookie::queue(cookie('refreshToken', $refresh, 60 * 24 * 7, null, null, true, true, false, 'Lax'));
    }
}