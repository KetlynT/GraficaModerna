@extends('layouts.app')

@section('content')
<div class="bg-gray-50 py-12 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Seu Carrinho</h1>

        @if($cart->items->isEmpty())
            <div class="bg-white p-8 rounded-lg shadow text-center">
                <p class="text-gray-500 text-lg mb-4">Seu carrinho está vazio.</p>
                <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 font-semibold">Voltar a comprar</a>
            </div>
        @else
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="flex-grow">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <ul class="divide-y divide-gray-200">
                            @foreach($cart->items as $item)
                                <li class="p-6 flex flex-col sm:flex-row items-center gap-6">
                                    <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded-md overflow-hidden">
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                    </div>

                                    <div class="flex-grow text-center sm:text-left">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <a href="{{ route('products.show', $item->product->id) }}">{{ $item->product->name }}</a>
                                        </h3>
                                        <p class="text-gray-500 text-sm mt-1">{{ $item->product->category }}</p>
                                        <p class="text-blue-600 font-bold mt-2">R$ {{ number_format($item->price, 2, ',', '.') }}</p>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}" class="p-1 rounded-full hover:bg-gray-100 text-gray-500 disabled:opacity-50" {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="w-8 text-center font-medium">{{ $item->quantity }}</span>
                                            <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" class="p-1 rounded-full hover:bg-gray-100 text-gray-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="w-full lg:w-96 flex-shrink-0">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                        <h2 class="text-lg font-bold text-gray-900 mb-6">Resumo</h2>
                        
                        <div class="flex justify-between mb-4 text-gray-600">
                            <span>Subtotal</span>
                            <span>R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between mb-4 text-gray-600">
                            <span>Frete</span>
                            <span class="text-green-600 text-xs font-semibold uppercase bg-green-50 px-2 py-1 rounded">Grátis</span>
                        </div>
                        
                        <div class="border-t pt-4 flex justify-between items-center mb-6">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-blue-600">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>

                        <button class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 duration-200">
                            Finalizar Compra
                        </button>
                        
                        <p class="mt-4 text-xs text-gray-400 text-center">
                            Transação segura criptografada
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection