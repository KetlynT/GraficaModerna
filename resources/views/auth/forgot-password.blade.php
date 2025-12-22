@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="text-center mb-8">
            <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="mail" width="32" class="text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Recuperar Senha</h2>
            <p class="text-gray-500 text-sm mt-2">
                Digite seu e-mail para receber o link de redefinição.
            </p>
        </div>

        @if (session('status'))
            <div class="bg-green-50 text-green-600 p-3 rounded mb-4 text-sm font-bold text-center">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 text-red-500 p-3 rounded mb-4 text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/forgot-password" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail Cadastrado</label>
                <input 
                    type="email"
                    name="email"
                    required
                    class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary"
                    placeholder="seu@email.com"
                    value="{{ old('email') }}"
                />
            </div>

            <button 
                type="submit" 
                class="w-full bg-primary hover:brightness-90 text-white font-bold py-3 rounded-lg transition-colors shadow-lg shadow-blue-900/30"
            >
                Enviar Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="/login" class="text-gray-600 hover:text-primary flex items-center justify-center gap-2 text-sm font-medium">
                <i data-lucide="arrow-left" width="16"></i> Voltar para o Login
            </a>
        </div>
    </div>
</div>
@endsection