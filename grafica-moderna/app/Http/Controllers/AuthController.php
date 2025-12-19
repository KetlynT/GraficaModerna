<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        // Valide os dados aqui ou use um FormRequest
        $data = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'fullName' => 'required',
            'cpfCnpj' => 'required',
            'phoneNumber' => 'nullable'
        ]);

        try {
            $result = $this->authService->register($data);
            $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

            return response()->json([
                'email' => $result['email'],
                'role' => $result['role'],
                'message' => 'Cadastro realizado com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'isAdminLogin' => 'boolean'
        ]);

        try {
            $result = $this->authService->login($data);
            $this->setTokenCookies($result['accessToken'], $result['refreshToken']);

            return response()->json([
                'email' => $result['email'],
                'role' => $result['role'],
                'message' => 'Login realizado com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->cookie('jwt') ?? $request->bearerToken();
        
        if ($token) {
            try {
                // Decodifica só para pegar a expiração (exp)
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                $minutesLeft = ($decoded->exp - time()) / 60;
                
                if ($minutesLeft > 0) {
                    $this->blacklistService->blacklist($token, (int)ceil($minutesLeft));
                }
            } catch (\Exception $e) {
                // Token já inválido, ignora
            }
        }

        Auth::guard('web')->logout(); // Se estiver usando guard padrão também

        return response()->json(['message' => 'Deslogado com sucesso'])
            ->withCookie(cookie()->forget('jwt'))
            ->withCookie(cookie()->forget('refreshToken'));
    }

    public function checkAuth()
    {
        if (Auth::check()) {
            return response()->json([
                'isAuthenticated' => true, 
                'role' => Auth::user()->role
            ]);
        }
        return response()->json(['isAuthenticated' => false, 'role' => null]);
    }

    public function getProfile()
    {
        return response()->json(Auth::user());
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'fullName' => 'required',
            'cpfCnpj' => 'required',
            'phoneNumber' => 'nullable'
        ]);
        
        $this->authService->updateProfile(Auth::id(), $data);
        return response()->noContent();
    }

    private function setTokenCookies($accessToken, $refreshToken)
    {
        // Cookies HttpOnly
        Cookie::queue('jwt', $accessToken, 15, null, null, true, true);
        Cookie::queue('refreshToken', $refreshToken, 60 * 24 * 7, null, null, true, true);
    }
    public function refreshToken(Request $request)
{
    // Tenta pegar do Cookie, se não, tenta do Body (fallback)
    $refreshToken = $request->cookie('refreshToken') ?? $request->input('refreshToken');

    if (!$refreshToken) {
        return response()->json(['message' => 'Refresh Token não fornecido'], 401);
    }

    try {
        $result = $this->authService->refreshToken($refreshToken);
        
        // Define os novos cookies
        $cookieJwt = cookie('jwt', $result['accessToken'], 15, null, null, true, true); // 15 min
        $cookieRefresh = cookie('refreshToken', $result['refreshToken'], 7 * 24 * 60, null, null, true, true); // 7 dias

        return response()->json([
            'message' => 'Token renovado',
            'accessToken' => $result['accessToken']
        ])->withCookie($cookieJwt)->withCookie($cookieRefresh);

    } catch (\Exception $e) {
        // Se falhar, limpa os cookies
        return response()->json(['message' => $e->getMessage()], 401)
            ->withoutCookie('jwt')
            ->withoutCookie('refreshToken');
    }
}
}