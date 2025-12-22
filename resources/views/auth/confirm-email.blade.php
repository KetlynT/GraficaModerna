@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        
        @if(isset($status) && $status === 'success')
            <div class="flex flex-col items-center">
                <i data-lucide="check-circle" width="64" height="64" class="text-green-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">E-mail Confirmado!</h2>
                <p class="text-gray-600">Sua conta foi ativada com sucesso.</p>
                <p class="text-sm text-gray-400 mt-4">Você será redirecionado para o login em instantes...</p>
                <script>
                    setTimeout(() => window.location.href = '/login', 5000);
                </script>
            </div>
        @else
            <div class="flex flex-col items-center">
                <i data-lucide="x-circle" width="64" height="64" class="text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Falha na Confirmação</h2>
                <p class="text-gray-600">O link é inválido ou já expirou.</p>
                <a 
                    href="/"
                    class="mt-6 px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-bold text-gray-700 transition-colors inline-block"
                >
                    Voltar ao Início
                </a>
            </div>
        @endif

    </div>
</div>
@endsection