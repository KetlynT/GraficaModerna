@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="lock" width="32" class="text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Nova Senha</h2>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-500 p-3 rounded mb-4 text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/password/reset" method="POST" class="space-y-4" onsubmit="return validatePasswords()">
            @csrf
            <input type="hidden" name="token" value="{{ request('token') }}">
            <input type="hidden" name="email" value="{{ request('email') }}">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nova Senha</label>
                <input 
                    type="password"
                    name="password"
                    id="new_password"
                    required
                    class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary)]"
                />
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                <input 
                    type="password"
                    name="password_confirmation"
                    id="confirm_password"
                    required
                    class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary)]"
                />
            </div>

            <button 
                type="submit" 
                class="w-full bg-primary)] hover:brightness-90 text-white font-bold py-3 rounded-lg mt-4 transition-all shadow-lg"
            >
                Redefinir Senha
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function validatePasswords() {
        const p1 = document.getElementById('new_password').value;
        const p2 = document.getElementById('confirm_password').value;
        if(p1 !== p2) {
            alert("As senhas não conferem.");
            return false;
        }
        if(p1.length < 8) {
            alert("A senha deve ter no mínimo 8 caracteres.");
            return false;
        }
        return true;
    }
</script>
@endpush
@endsection