@extends('layouts.app')

@section('content')
@php
    $purchaseEnabled = ($settings->purchase_enabled ?? 'true') !== 'false';
    $isAdmin = auth()->check() && auth()->user()->role === 'Admin';
    $images = $product->imageUrls ?? ['https://placehold.co/600x400?text=Sem+Imagem'];
    $currentImage = $images[0];
    
    $isVideo = function($url) {
        return preg_match('/\.(mp4|webm|ogg)$/i', $url);
    };
@endphp

<div class="min-h-screen bg-gray-50 py-10 px-4">
    <div id="zoom-modal" class="fixed inset-0 z-50 bg-black/95 hidden items-center justify-center p-4">
        <button onclick="closeZoom()" class="absolute top-4 right-4 text-white z-50 hover:text-gray-300 transition-colors">
            <i data-lucide="x" width="40" height="40"></i>
        </button>

        @if(count($images) > 1)
            <button onclick="changeZoomImage(-1)" class="absolute left-4 text-white z-50 hover:text-gray-300">
                <i data-lucide="chevron-left" width="50" height="50"></i>
            </button>
            <button onclick="changeZoomImage(1)" class="absolute right-4 text-white z-50 hover:text-gray-300">
                <i data-lucide="chevron-right" width="50" height="50"></i>
            </button>
        @endif

        <div class="relative w-full h-full max-w-6xl max-h-[90vh] flex items-center justify-center">
            <img id="zoom-image" src="" class="object-contain max-h-full max-w-full hidden" />
            <video id="zoom-video" controls class="max-w-full max-h-full object-contain hidden"></video>
        </div>
    </div>

    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:flex">
        <div class="md:w-1/2 bg-gray-100 flex flex-col">
            <div class="relative h-96 w-full group bg-black flex items-center justify-center overflow-hidden">
                <img id="main-image" src="{{ $images[0] }}" class="object-contain w-full h-full cursor-zoom-in {{ $isVideo($images[0]) ? 'hidden' : '' }}" onclick="openZoom()" />
                
                <video id="main-video" controls class="w-full h-full object-contain {{ !$isVideo($images[0]) ? 'hidden' : '' }}" src="{{ $isVideo($images[0]) ? $images[0] : '' }}"></video>

                <button onclick="openZoom()" class="absolute top-2 right-2 bg-white/80 p-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity" id="btn-zoom">
                    <i data-lucide="maximize-2" width="20" height="20"></i>
                </button>

                @if(count($images) > 1)
                    <button onclick="changeImage(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 p-2 rounded-full hover:bg-white transition-colors">
                        <i data-lucide="chevron-left" width="20" height="20"></i>
                    </button>
                    <button onclick="changeImage(1)" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 p-2 rounded-full hover:bg-white transition-colors">
                        <i data-lucide="chevron-right" width="20" height="20"></i>
                    </button>
                @endif
            </div>

            @if(count($images) > 1)
                <div class="flex gap-2 p-4 overflow-x-auto bg-white border-t">
                    @foreach($images as $index => $img)
                        <div 
                            onclick="selectImage({{ $index }})"
                            class="relative w-20 h-20 shrink-0 cursor-pointer border-2 rounded-md overflow-hidden thumbnail-container {{ $index === 0 ? 'border-primary)]' : 'border-transparent' }}"
                            id="thumb-{{ $index }}"
                            data-url="{{ $img }}"
                            data-is-video="{{ $isVideo($img) ? 'true' : 'false' }}"
                        >
                            @if($isVideo($img))
                                <div class="w-full h-full bg-gray-900 flex items-center justify-center text-white">
                                    <i data-lucide="play-circle" width="24" height="24"></i>
                                </div>
                            @else
                                <img src="{{ $img }}" class="w-full h-full object-cover" />
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="md:w-1/2 p-8 flex flex-col">
            <a href="/" class="text-color-primary text-sm mb-4 hover:underline">
                ← Voltar para o catálogo
            </a>

            <h1 class="text-3xl font-bold mb-2">{{ $product->name }}</h1>
            <div class="text-3xl font-bold text-color-primary mb-6">
                R$ {{ number_format($product->price, 2, ',', '.') }}
            </div>

            <p class="text-gray-500 mb-8 whitespace-pre-line leading-relaxed">
                {{ $product->description }}
            </p>

            @if(!$isAdmin && $purchaseEnabled)
                <div class="flex gap-4 mb-6">
                    <div class="flex border rounded-lg">
                        <button type="button" onclick="updateQuantity(-1)" class="p-3 hover:bg-gray-100 transition-colors">
                            <i data-lucide="minus" width="16" height="16"></i>
                        </button>
                        <span id="qty-display" class="w-12 flex items-center justify-center font-bold">1</span>
                        <button type="button" onclick="updateQuantity(1)" class="p-3 hover:bg-gray-100 transition-colors">
                            <i data-lucide="plus" width="16" height="16"></i>
                        </button>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if(!$isAdmin && $purchaseEnabled)
                    <form action="/carrinho/adicionar" method="POST" id="add-to-cart-form" class="w-full">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" id="form-quantity" value="1">
                        
                        <button type="submit" class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-900 font-medium py-2 rounded-lg transition-colors flex items-center justify-center gap-2 h-full">
                            <i data-lucide="shopping-cart" width="20"></i> Adicionar
                        </button>
                    </form>

                    <button onclick="document.getElementById('add-to-cart-form').submit(); window.location.href='/carrinho'" class="w-full bg-primary hover:brightness-90 text-white font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="zap" width="20"></i> Comprar Agora
                    </button>
                @endif

                <button
                    onclick="openWhatsApp()"
                    class="sm:col-span-2 bg-[#25D366] hover:bg-[#1EBE5A] text-white font-bold py-3 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2 {{ (!$purchaseEnabled || $isAdmin) ? 'mt-0' : '' }}"
                >
                    Orçamento Personalizado
                </button>
            </div>

            @if($purchaseEnabled)
                <div class="mt-8 border-t pt-6">
                    @include('components.shipping-calculator', ['productId' => $product->id])
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentIndex = 0;
    const images = @json($images);
    const quantityEl = document.getElementById('qty-display');
    const quantityInput = document.getElementById('form-quantity');
    let quantity = 1;

    function isVideo(url) {
        return /\.(mp4|webm|ogg)$/i.test(url);
    }

    function updateQuantity(delta) {
        const newQty = quantity + delta;
        if (newQty >= 1) {
            quantity = newQty;
            quantityEl.innerText = quantity;
            quantityInput.value = quantity;
        }
    }

    function selectImage(index) {
        currentIndex = index;
        const url = images[index];
        const isVid = isVideo(url);
        
        const imgEl = document.getElementById('main-image');
        const vidEl = document.getElementById('main-video');
        const zoomBtn = document.getElementById('btn-zoom');

        document.querySelectorAll('.thumbnail-container').forEach((el, idx) => {
            el.classList.toggle('border-[var(--color-primary)]', idx === index);
            el.classList.toggle('border-transparent', idx !== index);
        });

        if (isVid) {
            imgEl.classList.add('hidden');
            vidEl.classList.remove('hidden');
            vidEl.src = url;
            zoomBtn.classList.add('hidden');
        } else {
            vidEl.classList.add('hidden');
            vidEl.pause();
            imgEl.classList.remove('hidden');
            imgEl.src = url;
            zoomBtn.classList.remove('hidden');
        }
    }

    function changeImage(delta) {
        let nextIndex = (currentIndex + delta + images.length) % images.length;
        selectImage(nextIndex);
    }

    function openZoom() {
        const url = images[currentIndex];
        if (isVideo(url)) {
            const vid = document.getElementById('zoom-video');
            vid.src = url;
            vid.classList.remove('hidden');
            document.getElementById('zoom-image').classList.add('hidden');
        } else {
            const img = document.getElementById('zoom-image');
            img.src = url;
            img.classList.remove('hidden');
            document.getElementById('zoom-video').classList.add('hidden');
        }
        document.getElementById('zoom-modal').classList.replace('hidden', 'flex');
    }

    function closeZoom() {
        document.getElementById('zoom-modal').classList.replace('flex', 'hidden');
        document.getElementById('zoom-video').pause();
    }

    function changeZoomImage(delta) {
        changeImage(delta);
        openZoom();
    }

    function openWhatsApp() {
        const message = "Olá! Gostaria de um *orçamento personalizado* para o produto: *{{ $product->name }}*.";
        const number = "{{ $settings->whatsapp_number ?? '5511999999999' }}".replace(/\D/g, '');
        window.open(`https://wa.me/${number}?text=${encodeURIComponent(message)}`, '_blank');
    }
</script>
@endpush
@endsection