@extends('layouts.admin')

@section('title', 'Configurações do Site')

@section('content')
<form action="/admin/configuracoes" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="grid lg:grid-cols-2 gap-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
            <h3 class="font-bold text-gray-800 border-b pb-2">Aparência & Identidade</h3>
            
            <div>
                <label class="label-admin">Nome do Site</label>
                <input type="text" name="site_name" value="{{ $settings->site_name ?? 'Gráfica' }}" class="input-admin">
            </div>

            <div>
                <label class="label-admin">URL do Logo</label>
                <input type="text" name="site_logo" value="{{ $settings->site_logo ?? '' }}" class="input-admin" placeholder="https://...">
            </div>

            <div>
                <label class="label-admin">Título Hero (Home)</label>
                <input type="text" name="hero_title" value="{{ $settings->hero_title ?? '' }}" class="input-admin">
            </div>

            <div>
                <label class="label-admin">Subtítulo Hero</label>
                <textarea name="hero_subtitle" class="input-admin h-24 resize-none">{{ $settings->hero_subtitle ?? '' }}</textarea>
            </div>

            <div>
                <label class="label-admin">Background Hero (Imagem/Vídeo URL)</label>
                <input type="text" name="hero_bg_url" value="{{ $settings->hero_bg_url ?? '' }}" class="input-admin">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
            <h3 class="font-bold text-gray-800 border-b pb-2">Contato & Sistema</h3>
            
            <div>
                <label class="label-admin">WhatsApp (Apenas Números)</label>
                <input type="text" name="whatsapp_number" value="{{ $settings->whatsapp_number ?? '' }}" class="input-admin" placeholder="5511999999999">
            </div>
            
            <div>
                <label class="label-admin">WhatsApp (Exibição)</label>
                <input type="text" name="whatsapp_display" value="{{ $settings->whatsapp_display ?? '' }}" class="input-admin" placeholder="(11) 99999-9999">
            </div>

            <div>
                <label class="label-admin">E-mail de Contato</label>
                <input type="email" name="contact_email" value="{{ $settings->contact_email ?? '' }}" class="input-admin">
            </div>

            <div>
                <label class="label-admin">Endereço Físico</label>
                <input type="text" name="address" value="{{ $settings->address ?? '' }}" class="input-admin">
            </div>

            <div class="pt-4 flex items-center gap-3">
                <input type="checkbox" name="purchase_enabled" id="purchase_enabled" class="w-5 h-5" {{ ($settings->purchase_enabled ?? 'true') === 'true' ? 'checked' : '' }}>
                <label for="purchase_enabled" class="font-bold text-gray-700">Habilitar Compras no Site</label>
            </div>
        </div>

    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-lg font-bold shadow-lg flex items-center gap-2">
            <i data-lucide="save" width="20"></i> Salvar Configurações
        </button>
    </div>
</form>

<style>
    .label-admin { @apply block text-sm font-bold text-gray-700 mb-1; }
    .input-admin { @apply w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none; }
</style>
@endsection