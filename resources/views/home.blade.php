@extends('layouts.app')

@section('content')
@php
    $heroBgUrl = $settings->hero_bg_url ?? 'https://images.unsplash.com/photo-1562564055-71e051d33c19?q=80&w=2070';
    $isHeroVideo = preg_match('/\.(mp4|webm|ogg)$/i', $heroBgUrl);
@endphp

<div class="relative bg-secondary text-white overflow-hidden transition-all duration-500">
    @if($isHeroVideo)
        <div class="absolute inset-0 opacity-20 overflow-hidden">
            <video autoplay loop muted playsinline class="w-full h-full object-cover transform scale-105" src="{{ $heroBgUrl }}"></video>
        </div>
    @else
        <div class="absolute inset-0 bg-cover bg-center opacity-20 transform scale-105" style="background-image: url('{{ $heroBgUrl }}')"></div>
    @endif
    
    <div class="relative max-w-7xl mx-auto px-4 py-24 sm:px-6 lg:px-8 flex flex-col items-center text-center">
        <div class="animate-fade-in-up"> <span class="inline-block py-1 px-3 rounded-full bg-white/10 border border-white/20 text-white text-sm font-semibold mb-6 backdrop-blur-sm">
                {{ $settings->hero_badge ?? 'Bem-vindo' }}
            </span>
            <h2 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 leading-tight drop-shadow-lg">
                {{ $settings->hero_title ?? 'Título Principal' }}
            </h2>
            <p class="text-xl text-gray-200 mb-10 max-w-2xl mx-auto drop-shadow-md">
                {{ $settings->hero_subtitle ?? 'Subtítulo aqui' }}
            </p>
            <div class="flex gap-4 justify-center">
                <a href="#catalogo" class="rounded-full px-8 py-4 text-lg shadow-xl shadow-black/20 bg-green-600 hover:bg-green-700 text-white font-bold transition-all">
                    Ver Catálogo
                </a>
            </div>
        </div>
    </div>
</div>

<section id="catalogo" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="flex flex-col lg:flex-row justify-between items-end mb-12 gap-6">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="printer" class="text-primary"></i>
                {{ $settings->home_products_title ?? 'Nossos Produtos' }}
            </h2>
            <p class="text-gray-500 mt-2">{{ $settings->home_products_subtitle ?? 'Confira nosso catálogo' }}</p>
        </div>
        
        <form method="GET" action="/" class="w-full lg:w-auto flex flex-col sm:flex-row gap-4">
            <div class="relative grow sm:w-80">
                <input 
                    type="text" 
                    name="search"
                    placeholder="Buscar produto..." 
                    value="{{ request('search') }}"
                    class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none shadow-sm transition-all"
                />
                <i data-lucide="search" class="absolute left-4 top-3.5 text-gray-400" width="20"></i>
            </div>

            <div class="relative min-w-50">
                <i data-lucide="filter" class="absolute left-4 top-3.5 text-gray-400" width="20"></i>
                <select 
                    name="sort"
                    onchange="this.form.submit()"
                    class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none shadow-sm appearance-none cursor-pointer"
                >
                    <option value="">Mais Recentes</option>
                    <option value="name-asc" {{ request('sort') == 'name-asc' ? 'selected' : '' }}>Nome (A-Z)</option>
                    <option value="name-desc" {{ request('sort') == 'name-desc' ? 'selected' : '' }}>Nome (Z-A)</option>
                    <option value="price-asc" {{ request('sort') == 'price-asc' ? 'selected' : '' }}>Menor Preço</option>
                    <option value="price-desc" {{ request('sort') == 'price-desc' ? 'selected' : '' }}>Maior Preço</option>
                </select>
            </div>
        </form>
    </div>

    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($products as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>

        <div class="mt-16">
            {{ $products->appends(request()->query())->links() }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 text-center">
            <p class="text-gray-500 text-lg mb-4">Nenhum produto encontrado com estes filtros.</p>
            <a href="/" class="text-primary font-medium hover:underline">
                Limpar Filtros
            </a>
        </div>
    @endif
</section>
@endsection