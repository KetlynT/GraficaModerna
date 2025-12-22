@extends('layouts.admin')

@section('title', 'Gerenciar Pedidos')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-4 justify-between items-center bg-gray-50">
        <form action="" method="GET" class="relative w-full sm:max-w-xs">
            <i data-lucide="search" class="absolute left-3 top-3 text-gray-400" width="18"></i>
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}"
                placeholder="Buscar ID, Cliente..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </form>
        
        <div class="flex gap-2">
            <select onchange="window.location.href=this.value" class="border border-gray-300 rounded-lg px-4 py-2 bg-white focus:outline-none">
                <option value="?status=">Todos os Status</option>
                <option value="?status=Pendente" {{ request('status') == 'Pendente' ? 'selected' : '' }}>Pendente</option>
                <option value="?status=Pago" {{ request('status') == 'Pago' ? 'selected' : '' }}>Pago</option>
                <option value="?status=Enviado" {{ request('status') == 'Enviado' ? 'selected' : '' }}>Enviado</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Data</th>
                    <th class="px-6 py-4">Cliente</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Pagamento</th>
                    <th class="px-6 py-4 text-right">Total</th>
                    <th class="px-6 py-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-6 py-4 font-mono font-bold text-gray-700">
                            #{{ substr($order->id, 0, 8) }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $order->user->name ?? 'Visitante' }}</div>
                            <div class="text-xs text-gray-500">{{ $order->user->email ?? $order->guest_email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border
                                {{ $order->status == 'Pago' ? 'bg-green-100 text-green-700 border-green-200' : 
                                  ($order->status == 'Enviado' ? 'bg-blue-100 text-blue-700 border-blue-200' : 
                                  ($order->status == 'Cancelado' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200')) }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $order->payment_method ?? 'Stripe' }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">
                            R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="/admin/pedidos/{{ $order->id }}" class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors inline-block" title="Ver Detalhes">
                                <i data-lucide="eye" width="18"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-100 bg-gray-50">
        {{ $orders->links() }}
    </div>
</div>
@endsection