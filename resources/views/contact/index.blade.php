@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-16">
    <h1 class="text-4xl font-bold text-center mb-12 text-gray-800">Fale Conosco</h1>
    
    <div class="grid md:grid-cols-2 gap-12">
        <div class="space-y-8">
            <div class="bg-primary/5 p-6 rounded-2xl border border-primary/10">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-secondary">
                    <i data-lucide="phone" class="text-primary"></i> WhatsApp
                </h3>
                <p class="text-gray-600">Atendimento rápido para orçamentos e dúvidas.</p>
                <p class="font-bold text-lg mt-2 text-gray-800">{{ $settings->whatsapp_display ?? '...' }}</p>
            </div>

            <div class="bg-primary/5 p-6 rounded-2xl border border-primary/10">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-secondary">
                    <i data-lucide="mail" class="text-primary"></i> E-mail
                </h3>
                <p class="text-gray-600">Envie seus arquivos ou solicitações formais.</p>
                <p class="font-bold text-lg mt-2 text-gray-800">{{ $settings->contact_email ?? '...' }}</p>
            </div>

            <div class="bg-primary)]/5 p-6 rounded-2xl border border-primary)]/10">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-secondary)]">
                    <i data-lucide="map-pin" class="text-primary)]"></i> Endereço
                </h3>
                <p class="text-gray-600">Venha tomar um café conosco em nossa sede.</p>
                <p class="font-bold text-lg mt-2 text-gray-800">{{ $settings->address ?? '...' }}</p>
            </div>
        </div>

        <form action="/contato/enviar" method="POST" class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            @csrf
            
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-4">
                <input 
                    type="text" 
                    name="website_url" 
                    style="display: none; opacity: 0; position: absolute; left: -9999px;" 
                    tabindex="-1" 
                    autocomplete="off"
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input 
                        type="text" 
                        name="name"
                        class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary)] transition-all" 
                        placeholder="Seu nome"
                        required 
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input 
                        type="email" 
                        name="email"
                        class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary)] transition-all" 
                        placeholder="seu@email.com"
                        required 
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Como podemos ajudar?</label>
                    <textarea 
                        name="message"
                        class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-primary)] transition-all h-32 resize-none" 
                        placeholder="Descreva seu projeto ou dúvida..."
                        required
                    ></textarea>
                </div>
                <button type="submit" class="w-full bg-primary)] hover:brightness-90 text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2">
                    <i data-lucide="send" width="18"></i> Enviar Mensagem
                </button>
            </div>
        </form>
    </div>
</div>
@endsection