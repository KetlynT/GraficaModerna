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

    public function logout()
    {
        // Revoga tokens do usuário atual
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
        }

        // Limpa cookies
        return response()->json(['message' => 'Deslogado com sucesso'])
            ->withCookie(Cookie::forget('jwt'))
            ->withCookie(Cookie::forget('refreshToken'));
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
}