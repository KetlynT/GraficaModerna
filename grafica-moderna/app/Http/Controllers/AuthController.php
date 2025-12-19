<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

use App\Services\AuthService;
use App\Services\TokenBlacklistService;
use App\Services\ContentService;

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
        $this->middleware('throttle:auth')->only([
            'register',
            'login'
        ]);

        $this->authService = $authService;
        $this->blacklistService = $blacklistService;
        $this->contentService = $contentService;
    }

    // ======================================================
    // REGISTER
    // ======================================================

    public function register(Request $request)
    {
        $data = $request->validate([
            'email'       => 'required|email|unique:users',
            'password'    => 'required|min:6',
            'fullName'    => 'required|string',
            'cpfCnpj'     => 'required|string',
            'phoneNumber' => 'nullable|string'
        ]);

        $result = $this->authService->register($data);

        $this->setTokenCookies(
            $result->accessToken,
            $result->refreshToken
        );

        return response()->json([
            'email'   => $result->email,
            'role'    => $result->role,
            'message' => 'Cadastro realizado com sucesso.'
        ]);
    }

    // ======================================================
    // LOGIN (USER)
    // ======================================================

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $data['isAdminLogin'] = false;

        $result = $this->authService->login($data);

        $this->setTokenCookies(
            $result->accessToken,
            $result->refreshToken
        );

        return response()->json([
            'email'   => $result->email,
            'role'    => $result->role,
            'message' => 'Login realizado com sucesso.'
        ]);
    }

    // ======================================================
    // LOGOUT
    // ======================================================

    public function logout(Request $request)
    {
        $token = $request->cookie('jwt') ?? $request->bearerToken();

        if ($token) {
            try {
                $decoded = JWT::decode(
                    $token,
                    new Key(config('app.jwt_secret'), 'HS256')
                );

                if (isset($decoded->exp)) {
                    $this->blacklistService->blacklist(
                        $token,
                        $decoded->exp
                    );
                }
            } catch (\Exception $e) {
                Log::warning(
                    'Erro ao processar blacklist no logout',
                    ['message' => $e->getMessage()]
                );
            }
        }

        return response()->json([
            'message' => 'Deslogado com sucesso'
        ])
        ->withCookie(cookie()->forget('jwt'))
        ->withCookie(cookie()->forget('refreshToken'));
    }

    // ======================================================
    // CHECK AUTH
    // ======================================================

    public function checkAuth(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'isAuthenticated' => false,
                'role' => null
            ]);
        }

        return response()->json([
            'isAuthenticated' => true,
            'role' => $request->user()->role
        ]);
    }

    // ======================================================
    // PROFILE
    // ======================================================

    public function getProfile(Request $request)
    {
        if (!$request->user()) {
            return response()->json([], 401);
        }

        return response()->json(
            $this->authService->getProfile($request->user()->id)
        );
    }

    public function updateProfile(Request $request)
    {
        $settings = $this->contentService->getSettings();

        if (
            isset($settings['purchase_enabled']) &&
            $settings['purchase_enabled'] === 'false'
        ) {
            if ($request->user()->role !== 'Admin') {
                return response()->json([
                    'message' => 'Edição de perfil desativada temporariamente.'
                ], 403);
            }
        }

        $data = $request->validate([
            'fullName'    => 'required|string',
            'cpfCnpj'     => 'required|string',
            'phoneNumber' => 'nullable|string'
        ]);

        $this->authService->updateProfile(
            $request->user()->id,
            $data
        );

        return response()->noContent();
    }

    // ======================================================
    // REFRESH TOKEN
    // ======================================================

    public function refreshToken(Request $request)
    {
        $accessToken  = $request->cookie('jwt');
        $refreshToken = $request->cookie('refreshToken');

        if (!$accessToken || !$refreshToken) {
            return response()->json([
                'message' => 'Tokens não encontrados nos cookies.'
            ], 400);
        }

        $result = $this->authService->refreshToken([
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken
        ]);

        $this->setTokenCookies(
            $result->accessToken,
            $result->refreshToken
        );

        return response()->json([
            'email' => $result->email,
            'role'  => $result->role
        ]);
    }

    // ======================================================
    // EMAIL / PASSWORD
    // ======================================================

    public function confirmEmail(Request $request)
    {
        $this->authService->confirmEmail($request->all());
        return response()->json([
            'message' => 'Email confirmado com sucesso!'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $this->authService->forgotPassword($request->all());
        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de recuperação.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $this->authService->resetPassword($request->all());
        return response()->json([
            'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.'
        ]);
    }

    // ======================================================
    // HELPERS
    // ======================================================

    private function setTokenCookies(string $access, string $refresh): void
    {
        Cookie::queue(
            cookie('jwt', $access, 15, null, null, true, true, false, 'Lax')
        );

        Cookie::queue(
            cookie('refreshToken', $refresh, 60 * 24 * 7, null, null, true, true, false, 'Lax')
        );
    }
}
