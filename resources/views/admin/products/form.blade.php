@extends('layouts.admin')

@section('title', isset($product) ? 'Editar Produto' : 'Novo Produto')

@section('content')
<form action="{{ isset($product) ? '/admin/produtos/'.$product->id : '/admin/produtos' }}" method="POST" enctype="multipart/form-data" class="max-w-4xl mx-auto">
    @csrf
    @if(isset($product))
        @method('PUT')
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        
        <div class="grid md:grid-cols-2 gap-6">
            <div class="col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome do Produto</label>
                <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Preço (R$)</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $product->price ?? '') }}" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estoque</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Descrição</label>
                <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none">{{ old('description', $product->description ?? '') }}</textarea>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Peso (kg)</label>
                <input type="number" step="0.001" name="weight" value="{{ old('weight', $product->weight ?? 0.5) }}" class="w-full border border-gray-300 rounded-lg p-3">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Largura (cm)</label>
                    <input type="number" name="width" value="{{ old('width', $product->width ?? 11) }}" class="w-full border border-gray-300 rounded-lg p-3">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Altura (cm)</label>
                    <input type="number" name="height" value="{{ old('height', $product->height ?? 2) }}" class="w-full border border-gray-300 rounded-lg p-3">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Comp. (cm)</label>
                    <input type="number" name="length" value="{{ old('length', $product->length ?? 16) }}" class="w-full border border-gray-300 rounded-lg p-3">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Imagens do Produto</label>
            
            @if(isset($product) && !empty($product->image_urls))
                <div class="flex gap-4 mb-4 overflow-x-auto pb-2">
                    @foreach($product->image_urls as $img)
                        <div class="relative w-24 h-24 shrink-0 group">
                            <img src="{{ $img }}" class="w-full h-full object-cover rounded border border-gray-200">
                            </div>
                    @endforeach
                </div>
            @endif

            <input type="file" name="images[]" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="text-xs text-gray-400 mt-1">Selecione novas imagens para adicionar.</p>
        </div>

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
            <a href="/admin/produtos" class="text-gray-600 hover:text-gray-800 font-medium">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg">
                Salvar Produto
            </button>
        </div>

    </div>
</form>
@endsection