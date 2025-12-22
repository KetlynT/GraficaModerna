@extends('layouts.app')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 flex items-center gap-3">
        <i data-lucide="package" class="text-primary"></i> Meus Pedidos
    </h1>

    @if($orders->isEmpty())
        <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center">
            <div class="mx-auto text-gray-300 mb-4 flex justify-center">
                <i data-lucide="package" width="48" height="48"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700">Nenhum pedido encontrado</h3>
        </div>
    @else
        <div class="space-y-6">
            @foreach($orders as $order)
                @php
                    $validStatuses = ['Pago', 'Enviado', 'Entregue', 'paid', 'delivered'];
                    $showRefundSection = false;
                    $canRefund = false;
                    $refundLabel = '';
                    
                    if (in_array($order->status, $validStatuses)) {
                        if (in_array($order->status, ['Pago', 'Enviado', 'paid'])) {
                            $showRefundSection = true;
                            $canRefund = true;
                            $refundLabel = "Solicitar Cancelamento";
                        } elseif (in_array($order->status, ['Entregue', 'delivered'])) {
                            $showRefundSection = true;
                            if (!$order->delivery_date) {
                                // Assume que pode se não tiver data (legado) ou ajusta lógica
                                $canRefund = true;
                                $refundLabel = "Solicitar Devolução";
                            } else {
                                $deadline = \Carbon\Carbon::parse($order->delivery_date)->addDays(7);
                                $canRefund = now()->lte($deadline);
                                $refundLabel = $canRefund ? "Solicitar Devolução" : "Prazo Expirado";
                            }
                        }
                    }
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md order-card" id="order-{{ $order->id }}">
                    <div onclick="toggleOrder('{{ $order->id }}')" class="p-6 cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-lg text-gray-800">Pedido #{{ strtoupper(substr($order->id, 0, 8)) }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-gray-100 text-gray-800 status-badge status-{{ \Illuminate\Support\Str::slug($order->status) }}">
                                    {{ $order->status }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 flex items-center gap-2">
                                <i data-lucide="calendar" width="14"></i> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between md:justify-end gap-4">
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase font-bold">Total</div>
                                <div class="text-xl font-bold text-green-600">R$ {{ number_format($order->total_amount ?? $order->total, 2, ',', '.') }}</div>
                            </div>
                            
                            @if($order->status === 'Pendente')
                                <a href="/checkout/pay/{{ $order->id }}" onclick="event.stopPropagation()" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded text-sm font-bold flex items-center gap-2">
                                    <i data-lucide="credit-card" width="16"></i> Pagar
                                </a>
                            @endif
                            
                            <i data-lucide="chevron-down" class="text-gray-400 transform transition-transform chevron-icon"></i>
                        </div>
                    </div>

                    <div class="order-details hidden border-t border-gray-100">
                        <div class="p-6 bg-white">
                            
                            @if($order->status === 'Reembolso Reprovado' || $order->status === 'refund_rejected')
                                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 mx-6 mt-4">
                                    <h4 class="text-red-800 font-bold flex items-center gap-2 mb-2">
                                        <i data-lucide="alert-triangle" width="18"></i> Solicitação de Reembolso Negada
                                    </h4>
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-xs font-bold text-red-700 uppercase block mb-1">Motivo da Análise:</span>
                                            <p class="text-sm text-gray-800 bg-white p-3 rounded border border-red-100">
                                                {{ $order->refund_rejection_reason ?? "Entre em contato com o suporte para mais detalhes." }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($order->reverse_logistics_code)
                                <div class="mb-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <h4 class="text-orange-800 font-bold flex items-center gap-2 mb-2">
                                        <i data-lucide="box" width="18"></i> Instruções de Devolução
                                    </h4>
                                    <p class="text-sm text-orange-900 mb-3">{{ $order->return_instructions ?? "Leve o produto aos Correios." }}</p>
                                    <div class="bg-white border border-orange-200 p-3 rounded flex items-center justify-between">
                                        <span class="text-xs text-gray-500 uppercase font-bold">Código de Postagem</span>
                                        <span class="font-mono text-lg font-bold text-gray-800 tracking-wider">{{ $order->reverse_logistics_code }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-6 flex flex-col md:flex-row justify-between gap-4">
                                <div class="flex items-start gap-2 text-gray-600 bg-primary/5 p-3 rounded-lg border border-primary/10 flex-1">
                                    <i data-lucide="map-pin" width="18" class="mt-0.5 text-primary"></i>
                                    <div>
                                        <span class="block font-bold text-primary text-sm">Endereço</span>
                                        <span class="text-sm">{{ $order->shipping_address ?? 'Endereço registrado' }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 space-y-2">
                                    @if($order->tracking_code)
                                        <div class="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-200 flex items-center gap-2">
                                            <i data-lucide="truck" width="16" class="text-primary"></i> Rastreio: <span class="font-mono font-bold">{{ $order->tracking_code }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <table class="w-full text-left text-sm mb-4">
                                <tbody class="divide-y border-b border-gray-100">
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td class="py-2 font-medium text-gray-800">{{ $item->quantity }}x {{ $item->product->name ?? $item->product_name }}</td>
                                            <td class="py-2 text-right font-bold text-gray-800">R$ {{ number_format($item->total ?? ($item->quantity * ($item->unit_price ?? $item->price)), 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex flex-col items-end gap-1 text-sm text-gray-700 mb-6">
                                <div class="flex justify-between w-full max-w-60"><span>Subtotal:</span><span>R$ {{ number_format($order->sub_total ?? $order->total, 2, ',', '.') }}</span></div>
                                @if(($order->discount ?? 0) > 0)
                                    <div class="flex justify-between w-full max-w-60 text-green-600"><span>Desconto:</span><span>- R$ {{ number_format($order->discount, 2, ',', '.') }}</span></div>
                                @endif
                                <div class="flex justify-between w-full max-w-60 text-primary"><span>Frete:</span><span>R$ {{ number_format($order->shipping_cost ?? 0, 2, ',', '.') }}</span></div>
                                <div class="flex justify-between w-full max-w-60 font-bold text-lg mt-2 border-t pt-2 border-gray-200"><span>Total:</span><span>R$ {{ number_format($order->total_amount ?? $order->total, 2, ',', '.') }}</span></div>
                            </div>

                            @if($showRefundSection)
                                <div class="border-t pt-3 flex justify-end">
                                    <button
                                        onclick="openRefundModal({{ json_encode($order) }})"
                                        {{ !$canRefund ? 'disabled' : '' }}
                                        class="text-xs font-medium px-3 py-1.5 rounded transition-colors flex items-center gap-1.5 {{ $canRefund ? 'text-red-600 hover:bg-red-50 border border-red-200' : 'text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed opacity-70' }}"
                                    >
                                        <i data-lucide="refresh-ccw" width="12"></i> {{ $refundLabel }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        </div>
    @endif

    @include('components.refund-request-modal')
</div>

<style>
    .status-pendente { @apply bg-yellow-100 text-yellow-800; }
    .status-pago { @apply bg-green-100 text-green-800; }
    .status-enviado { @apply bg-blue-100 text-blue-800; }
    .status-cancelado { @apply bg-red-100 text-red-800; }
    .status-entregue { @apply bg-gray-100 text-gray-800; }
    .status-delivered { @apply bg-gray-100 text-gray-800; }
</style>

@push('scripts')
<script>
    function toggleOrder(id) {
        const card = document.getElementById('order-' + id);
        const details = card.querySelector('.order-details');
        const chevron = card.querySelector('.chevron-icon');
        
        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            if(chevron) chevron.classList.add('rotate-180');
        } else {
            details.classList.add('hidden');
            if(chevron) chevron.classList.remove('rotate-180');
        }
    }
</script>
@endpush
@endsection