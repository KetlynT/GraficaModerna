@extends('layouts.app')

@section('content')
@php
    $subTotal = collect($cartItems)->sum(fn($i) => $i['totalPrice']);
    $coupon = session('coupon');
    $discountAmount = $coupon ? $subTotal * ($coupon['discountPercentage'] / 100) : 0;
    // O frete selecionado geralmente é guardado na sessão após o cálculo
    $selectedShipping = session('selected_shipping'); 
    $shippingCost = $selectedShipping['price'] ?? 0;
    $total = $subTotal - $discountAmount + $shippingCost;
@endphp

@if(count($cartItems) === 0)
    <div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
        <div class="bg-gray-100 p-6 rounded-full mb-4">
            <i data-lucide="shopping-bag" class="text-gray-400" width="48" height="48"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Seu carrinho está vazio</h2>
        <a href="/" class="bg-gray-900 text-white px-6 py-2 rounded hover:bg-gray-800 transition">
            Voltar para a Loja
        </a>
    </div>
@else
    <div class="max-w-7xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 flex items-center gap-2">
            <i data-lucide="shopping-bag"></i> Meu Carrinho
        </h1>

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
                            <tr>
                                <th class="p-4">Produto</th>
                                <th class="p-4 text-center">Qtd</th>
                                <th class="p-4 text-right">Total</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($cartItems as $item)
                                <tr>
                                    <td class="p-4">
                                        <div class="flex items-center gap-4">
                                            @if(!empty($item['productImage']))
                                                <img src="{{ $item['productImage'] }}" class="w-16 h-16 object-cover rounded border" alt="" />
                                            @else
                                                <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center text-xs">Sem Imagem</div>
                                            @endif
                                            <div>
                                                <div class="font-bold text-gray-800">{{ $item['productName'] ?? 'Item Indisponível' }}</div>
                                                <div class="text-xs text-gray-500">Unit: R$ {{ number_format($item['unitPrice'], 2, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center justify-center border rounded-lg w-fit mx-auto">
                                            <form action="/carrinho/atualizar" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $item['productId'] }}">
                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                                                <button type="submit" class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50" {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                                    <i data-lucide="minus" width="14"></i>
                                                </button>
                                            </form>
                                            
                                            <span class="px-2 font-medium text-sm w-8 text-center">{{ $item['quantity'] }}</span>
                                            
                                            <form action="/carrinho/atualizar" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $item['productId'] }}">
                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                                <button type="submit" class="px-3 py-1 text-gray-600 hover:bg-gray-100">
                                                    <i data-lucide="plus" width="14"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right font-bold text-primary">
                                        R$ {{ number_format($item['totalPrice'], 2, ',', '.') }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <form action="/carrinho/remover" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['productId'] }}">
                                            <button type="submit" class="text-red-500 hover:bg-red-50 p-2 rounded-full">
                                                <i data-lucide="trash-2" width="18"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('components.shipping-calculator', ['items' => $cartItems, 'className' => 'bg-white shadow-sm border border-gray-100'])
            </div>

            <div class="h-fit space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">Cupom de Desconto</h3>
                    @include('components.coupon-input', ['initialCoupon' => $coupon])
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 border-b pb-2">Resumo</h3>
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>R$ {{ number_format($subTotal, 2, ',', '.') }}</span>
                        </div>
                        
                        @if($coupon)
                            <div class="flex justify-between text-green-600 font-medium">
                                <span>Desconto ({{ $coupon['code'] }})</span>
                                <span>- R$ {{ number_format($discountAmount, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-primary">
                            <span>Frete</span>
                            <span>{{ $selectedShipping ? 'R$ ' . number_format($shippingCost, 2, ',', '.') : 'Não calculado' }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-lg font-bold text-gray-900 mt-4 pt-4 border-t border-gray-100 mb-6">
                        <span>Total</span>
                        <span class="text-2xl text-primary">
                            R$ {{ number_format($total, 2, ',', '.') }}
                        </span>
                    </div>
                    
                    <a href="{{ auth()->check() ? '/checkout' : '/login?from=/carrinho' }}" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg flex items-center justify-center gap-2 text-lg shadow-lg shadow-green-200 transition-all">
                        {{ auth()->check() ? 'Continuar para Entrega' : 'Login para Finalizar' }} 
                        <i data-lucide="arrow-right" width="20"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection