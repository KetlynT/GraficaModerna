@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 flex items-center gap-2">
        <i data-lucide="user" class="text-primary"></i> Minha Conta
    </h1>

    <div class="grid md:grid-cols-3 gap-8">
        <div class="space-y-4">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center">
                <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold text-gray-600">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <h3 class="font-bold text-gray-800">{{ auth()->user()->name }}</h3>
                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                <p class="text-xs text-blue-600 mt-1 font-mono">ID: #{{ auth()->id() }}</p>
            </div>

            <nav class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <a href="/perfil/pedidos" class="flex items-center gap-3 px-6 py-4 hover:bg-gray-50 border-b border-gray-100 transition-colors">
                    <i data-lucide="package" width="18" class="text-blue-600"></i>
                    <span class="font-medium text-gray-700">Meus Pedidos</span>
                </a>
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-6 py-4 hover:bg-red-50 text-red-600 transition-colors text-left">
                        <i data-lucide="log-out" width="18"></i>
                        <span class="font-medium">Sair da Conta</span>
                    </button>
                </form>
            </nav>
        </div>

        <div class="md:col-span-2 space-y-8">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Dados Pessoais</h2>
                
                @if(session('success'))
                    <div class="bg-green-100 text-green-700 p-3 rounded mb-6 text-sm flex items-center gap-2">
                        <i data-lucide="check-circle" width="16"></i> {{ session('success') }}
                    </div>
                @endif

                <form action="/perfil/atualizar" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                            <input name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-600 outline-none" required />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">E-mail</label>
                            <input value="{{ auth()->user()->email }}" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-gray-500 cursor-not-allowed" readonly />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">CPF/CNPJ</label>
                            <input name="cpf_cnpj" value="{{ old('cpf_cnpj', auth()->user()->cpf_cnpj) }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-600 outline-none" oninput="maskCpfCnpj(this)" maxlength="18" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Telefone</label>
                            <input name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-600 outline-none" oninput="maskPhone(this)" maxlength="15" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-lg font-bold text-sm transition-colors flex items-center gap-2">
                            <i data-lucide="save" width="16"></i> Salvar Dados
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                @include('components.address-manager')
            </div>

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
</script>
@endpush
@endsection