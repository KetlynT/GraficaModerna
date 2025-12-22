@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="text-center mb-8">
            <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="user-plus" width="32" class="text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Crie sua Conta</h2>
            <p class="text-gray-500 text-sm">Junte-se a nós para gerenciar seus pedidos.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-4 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/register" method="POST" class="space-y-4" onsubmit="return validateForm()">
            @csrf
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">CPF ou CNPJ</label>
                <div class="relative">
                    <i data-lucide="file-text" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="cpf_cnpj"
                        id="cpf_cnpj"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="000.000.000-00"
                        maxlength="18"
                        required
                        value="{{ old('cpf_cnpj') }}"
                        oninput="maskCpfCnpj(this)"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                <div class="relative">
                    <i data-lucide="user" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="name"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Seu nome"
                        required
                        value="{{ old('name') }}"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail</label>
                <div class="relative">
                    <i data-lucide="mail" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="email"
                        type="email"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="seu@email.com"
                        required
                        value="{{ old('email') }}"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Telefone / WhatsApp</label>
                <div class="relative">
                    <i data-lucide="phone" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="phone"
                        id="phone"
                        type="tel"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="(11) 99999-9999"
                        maxlength="15"
                        required
                        value="{{ old('phone') }}"
                        oninput="maskPhone(this)"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                <div class="relative">
                    <i data-lucide="lock" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="password"
                        id="password"
                        type="password"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Mínimo 8 caracteres"
                        required
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                <div class="relative">
                    <i data-lucide="lock" width="18" class="absolute left-3 top-3 text-gray-400"></i>
                    <input 
                        name="password_confirmation"
                        id="password_confirmation"
                        type="password"
                        class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Repita a senha"
                        required
                    />
                </div>
            </div>

            <button 
                type="submit"
                class="w-full bg-primary hover:brightness-90 text-white font-bold py-3 rounded-lg transition-colors flex justify-center mt-6 shadow-lg shadow-blue-900/30"
            >
                Cadastrar
            </button>
        </form>

        <div class="mt-6 text-center pt-6 border-t border-gray-100">
            <p class="text-sm text-gray-600">
                Já tem uma conta? 
                <a href="/login" class="text-primary font-bold hover:underline">
                    Fazer Login
                </a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function maskCpfCnpj(input) {
        let v = input.value.replace(/\D/g,"");
        if (v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/,"$1.$2");
            v = v.replace(/(\d{3})(\d)/,"$1.$2");
            v = v.replace(/(\d{3})(\d{1,2})$/,"$1-$2");
        } else {
            v = v.replace(/^(\d{2})(\d)/,"$1.$2");
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3");
            v = v.replace(/\.(\d{3})(\d)/,".$1/$2");
            v = v.replace(/(\d{4})(\d)/,"$1-$2");
        }
        input.value = v;
    }

    function maskPhone(input) {
        let v = input.value.replace(/\D/g,"");
        v = v.replace(/^(\d{2})(\d)/g,"($1) $2");
        v = v.replace(/(\d)(\d{4})$/,"$1-$2");
        input.value = v;
    }

    function validateForm() {
        const p1 = document.getElementById('password').value;
        const p2 = document.getElementById('password_confirmation').value;
        if(p1 !== p2) {
            alert('As senhas não conferem.');
            return false;
        }
        if(p1.length < 8) {
            alert('Senha muito curta.');
            return false;
        }
        return true;
    }
</script>
@endpush
@endsection