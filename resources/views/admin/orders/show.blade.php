@extends('layouts.admin')

@section('title', 'Detalhes do Pedido #' . substr($order->id, 0, 8))

@section('content')
<div class="grid lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-700">Itens do Pedido</h3>
            </div>
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="p-4">
                                <div class="font-bold text-gray-800">{{ $item->product_name }}</div>
                                <div class="text-xs text-gray-500">Unit: R$ {{ number_format($item->unit_price, 2, ',', '.') }}</div>
                            </td>
                            <td class="p-4 text-center text-gray-600">x{{ $item->quantity }}</td>
                            <td class="p-4 text-right font-bold text-gray-800">
                                R$ {{ number_format($item->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex flex-col items-end gap-1">
                <div class="flex justify-between w-64 text-sm"><span>Subtotal:</span> <span>R$ {{ number_format($order->sub_total, 2, ',', '.') }}</span></div>
                <div class="flex justify-between w-64 text-sm text-green-600"><span>Desconto:</span> <span>- R$ {{ number_format($order->discount, 2, ',', '.') }}</span></div>
                <div class="flex justify-between w-64 text-sm text-blue-600"><span>Frete:</span> <span>R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}</span></div>
                <div class="flex justify-between w-64 text-lg font-bold border-t border-gray-200 mt-2 pt-2"><span>Total:</span> <span>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span></div>
            </div>
        </div>

        @if($order->status === 'Reembolso Solicitado')
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <h3 class="font-bold text-red-800 mb-2 flex items-center gap-2"><i data-lucide="alert-triangle"></i> Solicitação de Reembolso</h3>
                <p class="text-sm text-red-700 mb-4">O cliente solicitou reembolso para este pedido.</p>
                <div class="flex gap-4">
                    <form action="/admin/orders/{{ $order->id }}/refund/approve" method="POST">
                        @csrf
                        <button class="bg-green-600 text-white px-4 py-2 rounded font-bold text-sm">Aprovar Reembolso</button>
                    </form>
                    <button onclick="document.getElementById('reject-refund-modal').classList.remove('hidden')" class="bg-red-600 text-white px-4 py-2 rounded font-bold text-sm">Reprovar</button>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-700 mb-4">Status do Pedido</h3>
            <form action="/admin/orders/{{ $order->id }}/status" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <select name="status" class="w-full border border-gray-300 rounded-lg p-3">
                    @foreach(['Pendente', 'Pago', 'Enviado', 'Entregue', 'Cancelado', 'Reembolsado'] as $st)
                        <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
                
                <div>
                    <label class="text-xs font-bold text-gray-500 mb-1 block">Código de Rastreio</label>
                    <input type="text" name="tracking_code" value="{{ $order->tracking_code }}" class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Ex: AA123456789BR">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg">
                    Atualizar Status
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-700 mb-4">Cliente</h3>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">
                    {{ substr($order->user->name ?? 'V', 0, 1) }}
                </div>
                <div>
                    <p class="font-bold text-sm">{{ $order->user->name ?? 'Visitante' }}</p>
                    <p class="text-xs text-gray-500">{{ $order->user->email ?? $order->guest_email }}</p>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h4 class="font-bold text-xs text-gray-500 uppercase mb-2">Endereço de Entrega</h4>
                <p class="text-sm text-gray-600 leading-relaxed">
                    {{ $order->shipping_address }}
                </p>
                <p class="text-xs text-gray-400 mt-1">CEP: {{ $order->shipping_zip_code }}</p>
            </div>
        </div>

    </div>
</div>

<div id="reject-refund-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
        <h3 class="font-bold text-lg mb-4">Reprovar Reembolso</h3>
        <form action="/admin/orders/{{ $order->id }}/refund/reject" method="POST">
            @csrf
            <textarea name="reason" class="w-full border border-gray-300 rounded p-3 mb-4" placeholder="Motivo da reprovação..." required></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('reject-refund-modal').classList.add('hidden')" class="px-4 py-2 border rounded">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded font-bold">Confirmar Reprovação</button>
            </div>
        </form>
    </div>
</div>
@endsection