@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="text-center mb-8">
            <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="log-in" width="32" class="text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Área do Cliente</h2>
            <p class="text-gray-500 text-sm">Bem-vindo de volta!</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 text-red-500 text-sm rounded border border-red-100">
                Email ou senha incorretos.
            </div>
        @endif

        <form action="/login" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="from" value="{{ request('from', '/') }}">
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Email</label>
                <input 
                    type="email" 
                    name="email"
                    class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all"
                    required
                    value="{{ old('email') }}"
                />
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Senha</label>
                <input 
                    type="password" 
                    name="password"
                    class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all"
                    required
                />
            </div>
            <button 
                type="submit"
                class="w-full bg-primary hover:brightness-90 text-white font-bold py-3 rounded-lg transition-all flex justify-center shadow-lg shadow-blue-900/30"
            >
                Entrar
            </button>
        </form>

        <div class="mt-6 text-center pt-6 border-t border-gray-100 space-y-2">
            <p class="text-sm text-gray-600">
                Não tem uma conta?
                <a href="/cadastro" class="text-primary font-bold hover:underline">
                    Cadastre-se
                </a>
            </p>
            <p>
                <a href="/login/recuperar-senha" class="text-sm text-primary hover:underline">
                    Esqueci minha senha
                </a>
            </p>
            <div>
                <a href="/" class="text-xs text-gray-400 hover:text-gray-600">← Voltar para a loja</a>
            </div>
        </div>
    </div>
</div>
@endsection