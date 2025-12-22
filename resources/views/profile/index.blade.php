@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-2xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Meu Perfil</h1>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 sm:p-8">
            
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <form action="/perfil/atualizar" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">E-mail (não alterável)</label>
                    <input 
                        value="{{ auth()->user()->email }}"
                        readonly
                        class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-gray-500 cursor-not-allowed"
                    />
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                        <input 
                            name="name"
                            value="{{ old('name', auth()->user()->name) }}"
                            class="w-full border border-gray-300 rounded-lg pl-10 p-3 focus:ring-2 focus:ring-primary outline-none"
                            required
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">CPF ou CNPJ</label>
                    <div class="relative">
                        <i data-lucide="file-text" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                        <input 
                            name="cpf_cnpj"
                            value="{{ old('cpf_cnpj', auth()->user()->cpf_cnpj) }}"
                            class="w-full border border-gray-300 rounded-lg pl-10 p-3 focus:ring-2 focus:ring-primary outline-none"
                            placeholder="000.000.000-00"
                            maxlength="18"
                            required
                            oninput="maskCpfCnpj(this)"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Telefone / Celular</label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                        <input 
                            name="phone"
                            value="{{ old('phone', auth()->user()->phone) }}"
                            class="w-full border border-gray-300 rounded-lg pl-10 p-3 focus:ring-2 focus:ring-primary outline-none"
                            placeholder="(00) 00000-0000"
                            maxlength="15"
                            required
                            oninput="maskPhone(this)"
                        />
                    </div>
                </div>

                <div class="pt-4 flex gap-4">
                    <button 
                        type="submit"
                        class="flex-1 bg-primary hover:brightness-90 text-white font-bold py-3 rounded-lg flex justify-center items-center gap-2 shadow-lg shadow-blue-900/30 transition-all"
                    >
                        <i data-lucide="save" width="20"></i> Salvar Alterações
                    </button>

                    <form action="/logout" method="POST">
                        @csrf
                        <button 
                            type="submit"
                            class="px-6 py-3 border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition-colors h-full"
                        >
                            Sair da Conta
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // As mesmas funções de máscara do cadastro podem ser reutilizadas aqui
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