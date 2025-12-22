<div id="refund-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full">
        <h2 class="text-xl font-bold mb-4">Solicitar Reembolso</h2>
        
        <form id="refund-form" method="POST" action="/orders/refund">
            @csrf
            <input type="hidden" name="order_id" id="refund-order-id">
            
            <div class="mb-4 space-y-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="type" value="Total" checked onchange="toggleRefundType()">
                    <span>Reembolso Total (Pedido Inteiro)</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="type" value="Parcial" onchange="toggleRefundType()">
                    <span>Reembolso Parcial (Selecionar Itens)</span>
                </label>
            </div>

            <div id="partial-items-container" class="mb-4 max-h-60 overflow-y-auto border p-2 rounded hidden">
                </div>

            <div class="bg-gray-100 p-3 rounded mb-4 text-right">
                <span class="text-gray-600 text-sm">Valor Estimado do Estorno:</span>
                <p id="refund-value-display" class="text-lg font-bold text-green-600">R$ 0,00</p>
                <p class="text-xs text-gray-500 mt-1">
                    * O valor final será analisado e confirmado pelo administrador.
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRefundModal()" class="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btn-confirm-refund" class="px-4 py-2 bg-primary text-white rounded hover:brightness-90 font-bold">
                    Confirmar Solicitação
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let currentRefundOrder = null;

    function openRefundModal(order) {
        currentRefundOrder = order;
        document.getElementById('refund-order-id').value = order.id;
        document.getElementById('refund-modal').classList.replace('hidden', 'flex');
        document.querySelector('input[name="type"][value="Total"]').checked = true;
        toggleRefundType();
    }

    function closeRefundModal() {
        document.getElementById('refund-modal').classList.replace('flex', 'hidden');
        currentRefundOrder = null;
    }

    function toggleRefundType() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const container = document.getElementById('partial-items-container');
        const display = document.getElementById('refund-value-display');
        const btn = document.getElementById('btn-confirm-refund');

        if (type === 'Total') {
            container.classList.add('hidden');
            display.innerText = 'R$ ' + parseFloat(currentRefundOrder.total_amount).toFixed(2).replace('.', ',');
            btn.disabled = false;
        } else {
            container.classList.remove('hidden');
            renderPartialItems();
            updatePartialTotal();
        }
    }

    function renderPartialItems() {
        const container = document.getElementById('partial-items-container');
        container.innerHTML = '';
        
        currentRefundOrder.items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between py-2 border-b last:border-0';
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <input type="checkbox" class="refund-item-check" data-id="${item.product_id}" data-price="${item.unit_price}" onchange="updatePartialTotal()">
                    <span class="text-sm">${item.product_name}</span>
                </div>
                <div class="flex items-center gap-1">
                    <input type="number" class="w-16 p-1 border rounded text-right refund-item-qty" 
                           value="${item.quantity}" min="1" max="${item.quantity}" 
                           onchange="updatePartialTotal()" disabled>
                    <span class="text-xs text-gray-500">/ ${item.quantity}</span>
                </div>
            `;
            container.appendChild(div);
        });
    }

    function updatePartialTotal() {
        let total = 0;
        let hasSelection = false;
        
        document.querySelectorAll('.refund-item-check').forEach(chk => {
            const qtyInput = chk.closest('div').nextElementSibling.querySelector('input');
            qtyInput.disabled = !chk.checked;
            
            if (chk.checked) {
                hasSelection = true;
                const price = parseFloat(chk.dataset.price);
                const qty = parseInt(qtyInput.value);
                total += price * qty;
            }
        });

        const display = document.getElementById('refund-value-display');
        if (document.querySelector('input[name="type"]:checked').value === 'Parcial') {
             display.innerText = hasSelection ? 'R$ ' + total.toFixed(2).replace('.', ',') : 'R$ 0,00';
             document.getElementById('btn-confirm-refund').disabled = !hasSelection;
        }
    }
</script>
@endpush