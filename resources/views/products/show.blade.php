@extends('layouts.app')

@section('content')
<div class="bg-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex mb-8 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-600">Início</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="rounded-2xl overflow-hidden bg-gray-100 border border-gray-200">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-96 flex items-center justify-center text-gray-400">Sem imagem</div>
                @endif
            </div>

            <div class="flex flex-col justify-center">
                <span class="text-blue-600 font-semibold tracking-wide uppercase text-sm mb-2">
                    {{ $product->category }}
                </span>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                    {{ $product->description }}
                </p>

                <div class="flex items-center mb-8">
                    <span class="text-3xl font-bold text-gray-900">R$ {{ number_format($product->price, 2, ',', '.') }}</span>
                    @if($product->stock > 0)
                        <span class="ml-4 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            Em Estoque
                        </span>
                    @else
                        <span class="ml-4 px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                            Esgotado
                        </span>
                    @endif
                </div>

                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex gap-4">
                    @csrf
                    <div class="w-24">
                        <label for="quantity" class="sr-only">Quantidade</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-3 text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" 
                        class="flex-1 bg-blue-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Adicionar ao Carrinho
                    </button>
                </form>
            </div>
        </div>

        @if($relatedProducts->count() > 0)
            <div class="mt-20">
                <h2 class="text-2xl font-bold mb-6">Produtos Relacionados</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->id) }}" class="group block">
                            <div class="bg-gray-100 rounded-lg overflow-hidden h-48 mb-4">
                                <img src="{{ $related->image_url }}" alt="{{ $related->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            </div>
                            <h3 class="font-medium text-gray-900 group-hover:text-blue-600">{{ $related->name }}</h3>
                            <p class="text-gray-500 font-semibold">R$ {{ number_format($related->price, 2, ',', '.') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection