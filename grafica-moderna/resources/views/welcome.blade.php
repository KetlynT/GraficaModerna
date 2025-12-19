@extends('layouts.app')

@section('content')
<div class="relative bg-gradient-to-r from-blue-600 to-blue-800 text-white overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10 text-center">
        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-6">
            Qualidade de Impressão <br class="hidden md:block" /> Profissional
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-xl text-blue-100 mb-10">
            Transforme suas ideias em realidade com nossa tecnologia de ponta e acabamento impecável.
        </p>
        <div class="flex justify-center gap-4">
            <a href="#produtos" class="bg-white text-blue-700 px-8 py-3 rounded-full font-bold shadow-lg hover:bg-gray-100 transition-all transform hover:scale-105">
                Ver Catálogo
            </a>
            <a href="{{ url('/contato') }}" class="border-2 border-white text-white px-8 py-3 rounded-full font-bold hover:bg-white hover:text-blue-700 transition-all">
                Orçamento Personalizado
            </a>
        </div>
    </div>
</div>

<div id="produtos" class="sticky top-0 z-40 bg-white border-b shadow-sm py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('home') }}" method="GET" class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            <div class="flex gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 scrollbar-hide">
                @foreach($categories as $cat)
                    <button type="submit" name="category" value="{{ $cat }}" 
                        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors
                        {{ (request('category') == $cat || (!request('category') && $cat == 'Todos')) 
                            ? 'bg-blue-600 text-white shadow-md' 
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $cat }}
                    </button>
                @endforeach
            </div>

            <div class="relative w-full md:w-72">
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Buscar produtos..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-shadow">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </form>
    </div>
</div>

<div class="bg-gray-50 py-12 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        @if($products->isEmpty())
            <div class="text-center py-20">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Nenhum produto encontrado</h3>
                <p class="mt-1 text-gray-500">Tente ajustar seus filtros ou busca.</p>
                <a href="{{ route('home') }}" class="mt-6 inline-block text-blue-600 hover:text-blue-800 font-medium">Limpar filtros &rarr;</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @foreach($products as $product)
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col h-full">
                        <div class="relative h-56 overflow-hidden bg-gray-200">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="flex items-center justify-center h-full text-gray-400">Sem Imagem</div>
                            @endif
                            
                            @if($product->is_featured)
                                <span class="absolute top-3 right-3 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                                    Destaque
                                </span>
                            @endif
                        </div>

                        <div class="p-5 flex flex-col flex-grow">
                            <div class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">
                                {{ $product->category }}
                            </div>
                            
                            <a href="{{ route('products.show', $product->id) }}" class="block">
                                <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-1">
                                    {{ $product->name }}
                                </h3>
                            </a>
                            
                            <p class="text-gray-500 text-sm mb-4 line-clamp-2 flex-grow">
                                {{ $product->description }}
                            </p>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-50 mt-auto">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-400">A partir de</span>
                                    <span class="text-xl font-bold text-gray-900">
                                        R$ {{ number_format($product->price, 2, ',', '.') }}
                                    </span>
                                </div>
                                <button class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors duration-200" title="Adicionar ao carrinho">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection