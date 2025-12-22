@extends('layouts.admin')

@section('title', 'Produtos')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-700">Catálogo</h3>
        <a href="/admin/produtos/criar" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
            <i data-lucide="plus" width="16"></i> Novo Produto
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4">Imagem</th>
                    <th class="px-6 py-4">Nome</th>
                    <th class="px-6 py-4">Preço</th>
                    <th class="px-6 py-4">Estoque</th>
                    <th class="px-6 py-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            @php $img = $product->image_urls[0] ?? null; @endphp
                            @if($img)
                                <img src="{{ $img }}" class="w-12 h-12 object-cover rounded border" />
                            @else
                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">Sem foto</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $product->name }}</td>
                        <td class="px-6 py-4">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($product->stock_quantity > 0)
                                <span class="text-green-600 font-bold">{{ $product->stock_quantity }} unid.</span>
                            @else
                                <span class="text-red-500 font-bold">Esgotado</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="/admin/produtos/{{ $product->id }}/editar" class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                    <i data-lucide="edit" width="18"></i>
                                </a>
                                <form action="/admin/produtos/{{ $product->id }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir?')">
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
    
    <div class="p-4 border-t border-gray-100">
        {{ $products->links() }}
    </div>
</div>
@endsection