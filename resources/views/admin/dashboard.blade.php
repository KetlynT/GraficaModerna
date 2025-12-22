@extends('layouts.admin')

@section('title', 'Visão Geral')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm text-gray-500 mb-1">Vendas Hoje</p>
                <h3 class="text-2xl font-bold text-gray-900">R$ {{ number_format($stats['sales_today'] ?? 0, 2, ',', '.') }}</h3>
            </div>
            <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                <i data-lucide="dollar-sign" width="24"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm text-gray-500 mb-1">Pedidos Pendentes</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] ?? 0 }}</h3>
            </div>
            <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg">
                <i data-lucide="clock" width="24"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm text-gray-500 mb-1">Produtos Ativos</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $stats['active_products'] ?? 0 }}</h3>
            </div>
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <i data-lucide="package" width="24"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm text-gray-500 mb-1">Clientes</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] ?? 0 }}</h3>
            </div>
            <div class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                <i data-lucide="users" width="24"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Pedidos Recentes</h3>
            <a href="/admin/pedidos" class="text-sm text-blue-600 hover:underline">Ver todos</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Cliente</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-mono">#{{ substr($order->id, 0, 8) }}</td>
                            <td class="px-6 py-4">{{ $order->user->name ?? 'Visitante' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase 
                                    {{ $order->status == 'Pago' ? 'bg-green-100 text-green-700' : 
                                      ($order->status == 'Pendente' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-700">
                                R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhum pedido recente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-4">Acesso Rápido</h3>
        <div class="space-y-3">
            <a href="/admin/produtos/novo" class="block w-full p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-center font-medium transition-colors border border-gray-200 text-gray-700">
                <i data-lucide="plus" class="inline w-4 h-4 mr-1"></i> Adicionar Produto
            </a>
            <a href="/admin/cupons" class="block w-full p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-center font-medium transition-colors border border-gray-200 text-gray-700">
                <i data-lucide="tag" class="inline w-4 h-4 mr-1"></i> Gerenciar Cupons
            </a>
            <a href="/" target="_blank" class="block w-full p-3 bg-blue-50 hover:bg-blue-100 rounded-lg text-center font-medium transition-colors border border-blue-100 text-blue-700">
                <i data-lucide="external-link" class="inline w-4 h-4 mr-1"></i> Ir para Loja
            </a>
        </div>
    </div>
</div>
@endsection