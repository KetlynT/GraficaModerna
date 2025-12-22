@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Finalizar Compra</h1>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            
            <section class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="map-pin" class="text-primary"></i> Endereço de Entrega
                    </h2>
                    <button onclick="openAddressModal()" class="text-primary text-xs gap-1 flex items-center hover:underline">
                        <i data-lucide="settings" width="14"></i> Gerenciar
                    </button>
                </div>

                <div id="address-list" class="grid md:grid-cols-2 gap-4">
                    <div class="col-span-2 text-center py-8">Carregando endereços...</div>
                </div>
            </section>

            <section id="shipping-section" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hidden animate-in fade-in">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i data-lucide="truck" class="text-primary"></i> Envio
                </h2>
                <div id="shipping-loading" class="text-sm text-gray-500 hidden">Calculando...</div>
                <div id="shipping-options" class="space-y-3">
                    </div>
            </section>
        </div>

        <div class="h-fit bg-white p-6 rounded-xl shadow-lg border border-gray-100 sticky top-24">
            <h3 class="font-bold text-xl text-gray-800 mb-6 border-b pb-4">Resumo</h3>
            
            <div class="mb-6">
                <label class="text-xs font-bold text-gray-500 mb-2 block">CUPOM DE DESCONTO</label>
                @include('components.coupon-input', ['initialCoupon' => session('coupon')])
            </div>

            <div class="space-y-3 text-sm text-gray-600 mb-6">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span id="summary-subtotal">R$ {{ number_format($cartTotal, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-green-600">
                    <span>Desconto</span>
                    <span id="summary-discount">- R$ {{ number_format($discount, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Frete</span>
                    <span id="summary-shipping">--</span>
                </div>
            </div>

            <div class="flex justify-between items-center text-xl font-extrabold text-gray-900 pt-4 border-t border-gray-100 mb-6">
                <span>Total</span>
                <span class="text-primary" id="summary-total">
                    R$ {{ number_format($total, 2, ',', '.') }}
                </span>
            </div>

            <button id="btn-checkout" onclick="processCheckout()" disabled class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-4 rounded-lg flex items-center justify-center gap-2 text-lg shadow-lg transition-all">
                <i data-lucide="credit-card" width="20"></i> Pagar com Stripe
            </button>
            
            <p class="text-xs text-gray-400 text-center mt-3 flex items-center justify-center gap-1">
                <i data-lucide="lock" width="10"></i> Ambiente Seguro Stripe
            </p>
        </div>
    </div>

    <div id="address-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl max-h-[85vh] overflow-hidden flex flex-col">
            <div class="p-5 border-b flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-lg text-gray-800">Meus Endereços</h3>
                <button onclick="closeAddressModal()"><i data-lucide="x" class="text-gray-400 hover:text-red-500"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-gray-500 text-sm mb-4">Para cadastrar novos endereços, vá ao seu perfil.</p>
                <a href="/perfil" class="text-blue-600 underline">Ir para Perfil</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let addresses = []; // Será populado via fetch
    let selectedAddress = null;
    let selectedShipping = null;
    const cartSubtotal = {{ $cartTotal }};
    const discountAmount = {{ $discount }};

    document.addEventListener('DOMContentLoaded', () => {
        loadAddresses();
    });

    async function loadAddresses() {
        try {
            // Assumindo que você criou uma rota API ou Web que retorna JSON
            const response = await fetch('/api/user/addresses'); 
            addresses = await response.json();
            renderAddresses();
            
            // Seleciona padrão ou primeiro
            if (addresses.length > 0) {
                const defaultAddr = addresses.find(a => a.is_default) || addresses[0];
                selectAddress(defaultAddr.id);
            } else {
                document.getElementById('address-list').innerHTML = '<div class="col-span-2 text-center py-8 border-2 border-dashed rounded-lg"><p class="text-gray-500 mb-4">Nenhum endereço cadastrado.</p></div>';
            }
        } catch (e) {
            console.error("Erro ao carregar endereços", e);
        }
    }

    function renderAddresses() {
        const container = document.getElementById('address-list');
        container.innerHTML = addresses.map(addr => `
            <div onclick="selectAddress(${addr.id})"
                class="cursor-pointer p-4 rounded-lg border-2 transition-all relative ${selectedAddress?.id === addr.id ? 'border-[var(--color-primary)] bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300 bg-white'}"
            >
                ${selectedAddress?.id === addr.id ? '<div class="absolute top-2 right-2 text-primary"><i data-lucide="check-circle"></i></div>' : ''}
                <div class="font-bold text-gray-800 text-sm mb-1">${addr.name}</div>
                <div class="text-sm text-gray-600 leading-snug">
                    ${addr.street}, ${addr.number} <br/> ${addr.neighborhood} - ${addr.city}/${addr.state}
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    }

    async function selectAddress(id) {
        selectedAddress = addresses.find(a => a.id === id);
        selectedShipping = null;
        renderAddresses();
        
        // Calcular Frete
        document.getElementById('shipping-section').classList.remove('hidden');
        document.getElementById('shipping-loading').classList.remove('hidden');
        document.getElementById('shipping-options').innerHTML = '';
        updateTotals();

        try {
            const response = await fetch('/api/shipping/calculate-cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ zip_code: selectedAddress.zip_code })
            });
            const options = await response.json();
            renderShippingOptions(options);
        } catch (e) {
            console.error(e);
            alert('Erro ao calcular frete.');
        } finally {
            document.getElementById('shipping-loading').classList.add('hidden');
        }
    }

    function renderShippingOptions(options) {
        const container = document.getElementById('shipping-options');
        container.innerHTML = options.map((opt, idx) => `
            <label class="flex justify-between items-center p-4 rounded-lg border cursor-pointer transition-colors ${selectedShipping?.name === opt.name ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:bg-gray-50'}">
                <div class="flex items-center gap-3">
                    <input type="radio" name="shipping" onchange='selectShipping(${JSON.stringify(opt)})' class="text-green-600" ${selectedShipping?.name === opt.name ? 'checked' : ''}/>
                    <div>
                        <div class="font-bold text-gray-800">${opt.name}</div>
                        <div class="text-xs text-gray-500">Até ${opt.delivery_days} dias úteis</div>
                    </div>
                </div>
                <div class="font-bold text-gray-700">R$ ${parseFloat(opt.price).toFixed(2).replace('.', ',')}</div>
            </label>
        `).join('');
    }

    function selectShipping(option) {
        selectedShipping = option;
        // Re-render para atualizar estilos
        // (Em uma app real, idealmente só atualiza classes, mas aqui reconstruo para simplicidade)
        // renderShippingOptions(...) seria ideal se tivesse salvo as options globais
        // Atualiza visualmente via DOM traversal ou salvar options globalmente
        const labels = document.querySelectorAll('#shipping-options label');
        labels.forEach(l => {
            if(l.innerText.includes(option.name)) {
                l.classList.add('border-green-500', 'bg-green-50');
                l.classList.remove('border-gray-200');
            } else {
                l.classList.remove('border-green-500', 'bg-green-50');
                l.classList.add('border-gray-200');
            }
        });

        updateTotals();
    }

    function updateTotals() {
        const shippingCost = selectedShipping ? parseFloat(selectedShipping.price) : 0;
        const total = cartSubtotal - discountAmount + shippingCost;

        document.getElementById('summary-shipping').innerText = selectedShipping ? `R$ ${shippingCost.toFixed(2).replace('.', ',')}` : '--';
        document.getElementById('summary-total').innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;

        const btn = document.getElementById('btn-checkout');
        btn.disabled = !selectedAddress || !selectedShipping;
    }

    async function processCheckout() {
        const btn = document.getElementById('btn-checkout');
        btn.disabled = true;
        btn.innerText = 'Processando...';

        try {
            const response = await fetch('/checkout/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    address_id: selectedAddress.id,
                    shipping_method: selectedShipping.name,
                    shipping_cost: selectedShipping.price
                })
            });

            const data = await response.json();
            
            if (data.url) {
                window.location.href = data.url;
            } else {
                throw new Error(data.message || 'Erro desconhecido');
            }
        } catch (e) {
            alert('Erro ao processar pedido: ' + e.message);
            btn.disabled = false;
            btn.innerText = 'Pagar com Stripe';
        }
    }

    function openAddressModal() { document.getElementById('address-modal').classList.replace('hidden', 'flex'); }
    function closeAddressModal() { document.getElementById('address-modal').classList.replace('flex', 'hidden'); }
</script>
@endpush
@endsection