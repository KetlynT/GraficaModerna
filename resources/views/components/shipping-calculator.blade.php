@props(['items' => [], 'productId' => null, 'className' => ''])

<div class="bg-gray-50 p-5 rounded-lg border border-gray-200 {{ $className }}">
    <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
        <i data-lucide="truck" width="18" class="text-primary"></i> Calcular Frete e Prazo
    </h3>
    
    <div class="flex gap-2 mb-4">
        <input 
            type="text" 
            id="shipping-cep"
            placeholder="Digite seu CEP" 
            maxlength="9"
            class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary bg-white"
            oninput="this.value = this.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')"
        />
        <button onclick="calculateShipping()" id="btn-calc-shipping" class="bg-gray-800 hover:bg-gray-900 text-white px-4 rounded text-sm font-bold">
            OK
        </button>
    </div>

    <div id="shipping-error" class="text-red-500 text-sm mb-3 hidden items-center gap-1 bg-red-50 p-2 rounded border border-red-100">
        <i data-lucide="alert-circle" width="14"></i> <span id="shipping-error-msg"></span>
    </div>

    <div id="shipping-options" class="space-y-2 hidden animate-in fade-in slide-in-from-top-2">
        </div>
</div>

@push('scripts')
<script>
    async function calculateShipping() {
        const cepInput = document.getElementById('shipping-cep');
        const btn = document.getElementById('btn-calc-shipping');
        const errorDiv = document.getElementById('shipping-error');
        const optionsDiv = document.getElementById('shipping-options');
        
        const cep = cepInput.value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            showError('Digite um CEP válido.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '...';
        errorDiv.classList.add('hidden');
        optionsDiv.innerHTML = '';
        
        try {
            const response = await fetch('/api/shipping/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cep: cep,
                    product_id: '{{ $productId }}',
                })
            });

            if (!response.ok) throw new Error('Erro no cálculo');
            
            const options = await response.json();
            
            if(options.length === 0) {
                 optionsDiv.innerHTML = '<div class="text-sm text-gray-500">Nenhuma opção encontrada.</div>';
            } else {
                 options.forEach(opt => {
                    const el = document.createElement('div');
                    el.className = 'flex justify-between items-center p-3 rounded border cursor-pointer bg-white border-gray-200 hover:border-blue-500 transition-all';
                    el.onclick = () => selectShippingOption(opt);
                    el.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div>
                                <span class="font-bold text-gray-800 text-sm block">${opt.name}</span>
                                <span class="text-xs text-gray-500">Até ${opt.deliveryDays} dias úteis</span>
                            </div>
                        </div>
                        <span class="font-bold text-green-700 text-sm">
                            R$ ${parseFloat(opt.price).toFixed(2).replace('.', ',')}
                        </span>
                    `;
                    optionsDiv.appendChild(el);
                 });
            }
            optionsDiv.classList.remove('hidden');

        } catch (e) {
            showError('Erro ao calcular frete.');
            console.error(e);
        } finally {
            btn.disabled = false;
            btn.innerText = 'OK';
        }
    }

    function showError(msg) {
        document.getElementById('shipping-error-msg').innerText = msg;
        document.getElementById('shipping-error').classList.replace('hidden', 'flex');
    }

    async function selectShippingOption(option) {
        await fetch('/cart/shipping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ option })
        });
        window.location.reload();
    }
</script>
@endpush