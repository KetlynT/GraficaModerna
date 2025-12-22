@props(['product'])
@php
    $purchaseEnabled = ($settings->purchase_enabled ?? 'true') !== 'false';
    $isAdmin = auth()->check() && auth()->user()->role === 'Admin';
    $imageUrl = $product->imageUrls[0] ?? 'https://placehold.co/400x300?text=Sem+Imagem';
@endphp

<div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden">
    <div class="relative h-64 w-full overflow-hidden bg-gray-50">
        <img 
            src="{{ $imageUrl }}"
            alt="{{ $product->name }}" 
            class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" 
            onerror="this.src='https://placehold.co/400x300?text=Erro+Imagem'"
        />
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 z-10">
            <a href="/produto/{{ $product->id }}" class="bg-white text-gray-900 hover:bg-gray-100 rounded-full p-3 shadow-lg inline-flex items-center justify-center">
                <i data-lucide="search" width="20" height="20"></i>
            </a>
        </div>
    </div>

    <div class="p-6 flex flex-col grow">
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-lg font-bold text-gray-800 line-clamp-1 group-hover:text-primary transition-colors">
                <a href="/produto/{{ $product->id }}">{{ $product->name }}</a>
            </h3>
        </div>
        
        <p class="text-gray-500 text-sm mb-4 line-clamp-2 grow">
            {{ $product->description }}
        </p>
        
        <div class="pt-4 border-t border-gray-100 flex items-center justify-between mt-auto">
            <div>
                <span class="text-xs text-gray-400 uppercase font-bold block">A partir de</span>
                <span class="text-xl font-bold text-color-primary">
                    R$ {{ number_format($product->price, 2, ',', '.') }}
                </span>
            </div>
            
            @if(!$isAdmin && $purchaseEnabled)
                <form action="/carrinho/adicionar" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button 
                        type="submit"
                        class="rounded-full px-3 py-2 shadow-sm hover:shadow-md bg-blue-600 text-white hover:bg-blue-700 transition-colors flex items-center justify-center"
                        title="Adicionar ao Carrinho"
                    >
                        <i data-lucide="shopping-cart" width="18" height="18"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>