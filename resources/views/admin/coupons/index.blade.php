@extends('layouts.admin')

@section('title', 'Cupons de Desconto')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-700">Cupons Ativos e Inativos</h3>
        <a href="/admin/cupons/criar" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
            <i data-lucide="plus" width="16"></i> Novo Cupom
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4">Código</th>
                    <th class="px-6 py-4">Desconto</th>
                    <th class="px-6 py-4">Validade</th>
                    <th class="px-6 py-4">Uso</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($coupons as $coupon)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-gray-800 uppercase">{{ $coupon->code }}</td>
                        <td class="px-6 py-4 text-green-600 font-bold">{{ $coupon->discount_percentage }}% OFF</td>
                        <td class="px-6 py-4">
                            @if($coupon->valid_until)
                                {{ \Carbon\Carbon::parse($coupon->valid_until)->format('d/m/Y') }}
                            @else
                                <span class="text-gray-400">Indeterminado</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{ $coupon->used_count }} / {{ $coupon->max_usages ?? '∞' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($coupon->is_active)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Ativo</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded text-xs font-bold">Inativo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="/admin/cupons/{{ $coupon->id }}/editar" class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                    <i data-lucide="edit" width="18"></i>
                                </a>
                                <form action="/admin/cupons/{{ $coupon->id }}" method="POST" onsubmit="return confirm('Excluir este cupom?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">
                                        <i data-lucide="trash-2" width="18"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection