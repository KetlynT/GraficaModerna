@extends('layouts.admin')

@section('title', isset($coupon) ? 'Editar Cupom' : 'Novo Cupom')

@section('content')
<form action="{{ isset($coupon) ? '/admin/cupons/'.$coupon->id : '/admin/cupons' }}" method="POST" class="max-w-2xl mx-auto">
    @csrf
    @if(isset($coupon))
        @method('PUT')
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Código do Cupom</label>
            <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" class="w-full border border-gray-300 rounded-lg p-3 uppercase font-mono tracking-wider focus:ring-2 focus:ring-blue-500 outline-none" placeholder="EX: VERAO10" required>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desconto (%)</label>
                <input type="number" name="discount_percentage" value="{{ old('discount_percentage', $coupon->discount_percentage ?? '') }}" class="w-full border border-gray-300 rounded-lg p-3" min="1" max="100" required>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Limite de Usos (Opcional)</label>
                <input type="number" name="max_usages" value="{{ old('max_usages', $coupon->max_usages ?? '') }}" class="w-full border border-gray-300 rounded-lg p-3" placeholder="Infinito">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Válido Até (Opcional)</label>
                <input type="date" name="valid_until" value="{{ old('valid_until', isset($coupon->valid_until) ? \Carbon\Carbon::parse($coupon->valid_until)->format('Y-m-d') : '') }}" class="w-full border border-gray-300 rounded-lg p-3">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Valor Mínimo do Pedido</label>
                <input type="number" step="0.01" name="min_order_value" value="{{ old('min_order_value', $coupon->min_order_value ?? 0) }}" class="w-full border border-gray-300 rounded-lg p-3">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <input type="checkbox" name="is_active" id="is_active" class="w-5 h-5 text-blue-600" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="font-bold text-gray-700">Cupom Ativo</label>
        </div>

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
            <a href="/admin/cupons" class="text-gray-600 hover:text-gray-800 font-medium">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg">
                Salvar Cupom
            </button>
        </div>
    </div>
</form>
@endsection