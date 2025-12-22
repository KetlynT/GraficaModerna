@php
    $isAdmin = auth()->check() && auth()->user()->role === 'Admin';
    $siteName = $settings->site_name ?? 'Gráfica Online';
    $logoUrl = $settings->site_logo ?? null;
    $purchaseEnabled = ($settings->purchase_enabled ?? 'true') !== 'false';
    // Assumindo que $cartCount é compartilhado globalmente via ViewComposer
    $cartCount = $cartCount ?? 0; 
@endphp

<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        
        <a href="/" class="flex items-center gap-2 group">
            @if($logoUrl)
                <div class="relative h-10 w-32">
                    <img 
                        src="{{ $logoUrl }}" 
                        alt="Logo" 
                        class="object-contain w-full h-full transition-transform group-hover:scale-105"
                    />
                </div>
            @else
                <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:shadow-xl transition-all">
                    {{ substr($siteName, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800 leading-none tracking-tight">{{ $siteName }}</h1>
                </div>
            @endif
        </a>
        
        <nav class="flex items-center gap-6">
            <a href="/contato" class="hidden md:flex items-center gap-2 text-gray-500 hover:text-primary transition-colors font-medium">
                <i data-lucide="message-square" width="20" height="20"></i>
                <span class="hidden lg:inline">Fale Conosco</span>
            </a>

            @if(!$isAdmin && $purchaseEnabled)
                <a href="/carrinho" class="relative group p-2">
                    <i data-lucide="shopping-cart" width="24" height="24" class="text-gray-600 group-hover:text-primary transition-colors"></i>
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow-sm animate-bounce">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            @endif

            @auth
                <div class="flex items-center gap-4 border-l pl-6 border-gray-200">
                    <a href="/meus-pedidos" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary" title="Meus Pedidos">
                        <i data-lucide="package" width="20" height="20"></i>
                    </a>
                    <a href="/perfil" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary" title="Meu Perfil">
                        <i data-lucide="user" width="20" height="20"></i>
                    </a>
                    
                    <form action="/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-500 ml-2 pt-1" title="Sair">
                            <i data-lucide="log-out" width="20" height="20"></i>
                        </button>
                    </form>
                </div>
            @else
                @if($purchaseEnabled)
                    <a href="/login" class="flex items-center gap-2 text-sm font-bold text-primary hover:brightness-75 border border-gray-200 bg-gray-50 px-4 py-2 rounded-full transition-all hover:shadow-md">
                        <i data-lucide="user" width="18" height="18"></i> Entrar
                    </a>
                @endif
            @endauth
        </nav>
    </div>
</header>