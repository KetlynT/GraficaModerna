<div id="refund-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4 transition-opacity opacity-0">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full shadow-2xl transform transition-all scale-95">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Solicitar Reembolso</h2>
        
        <form id="refund-form" onsubmit="event.preventDefault(); submitRefundRequest();">
            <input type="hidden" id="refund-order-id">
            
            <div class="mb-4 space-y-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="radio" name="type" value="Total" checked onchange="toggleRefundType()" class="h-4 w-4">
                    <span class="text-gray-700 font-medium">Reembolso Total (Pedido Inteiro)</span>
                </label>
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="radio" name="type" value="Parcial" onchange="toggleRefundType()" class="h-4 w-4">
                    <span class="text-gray-700 font-medium">Reembolso Parcial (Selecionar Itens)</span>
                </label>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Motivo <span class="text-red-500">*</span></label>
                <textarea id="refund-reason" rows="3" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-black outline-none" 
                    placeholder="Descreva o problema detalhadamente (mínimo 10 caracteres)..."></textarea>
            </div>

            <div id="partial-items-container" class="mb-4 max-h-60 overflow-y-auto border border-gray-200 p-2 rounded-lg hidden bg-white"></div>

            <div class="bg-gray-100 p-4 rounded-lg mb-6 text-right">
                <span class="text-gray-600 text-sm">Valor Estimado:</span>
                <p id="refund-value-display" class="text-2xl font-bold text-green-600 mt-1">R$ 0,00</p>
                <p class="text-[10px] text-gray-500">* O valor final será confirmado pelo administrador.</p>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeRefundModal()" class="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btn-confirm-refund" class="px-6 py-2 bg-black text-white rounded font-bold hover:opacity-90 disabled:opacity-50">
                    Confirmar Solicitação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentRefundOrder = null;
    const modal = document.getElementById('refund-modal');
    const container = modal.querySelector('div');

    function openRefundModal(order) {
        currentRefundOrder = order;
        document.getElementById('refund-order-id').value = order.id;
        document.getElementById('refund-reason').value = '';
        document.querySelector('input[name="type"][value="Total"]').checked = true;
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('flex');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
        
        toggleRefundType();
    }

    function closeRefundModal() {
        modal.classList.add('opacity-0');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentRefundOrder = null;
        }, 300);
    }

    function toggleRefundType() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const itemsContainer = document.getElementById('partial-items-container');
        const display = document.getElementById('refund-value-display');
        
        if (type === 'Total') {
            itemsContainer.classList.add('hidden');
            const total = parseFloat(currentRefundOrder.total_amount || currentRefundOrder.total || 0);
            display.innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        } else {
            itemsContainer.classList.remove('hidden');
            renderPartialItems();
            updatePartialTotal();
        }
    }

    function renderPartialItems() {
        const container = document.getElementById('partial-items-container');
        container.innerHTML = '';
        
        currentRefundOrder.items.forEach(item => {
            const unitPrice = parseFloat(item.unit_price || item.price || 0);
            
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between py-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 px-2 rounded';
            div.innerHTML = `
                <div class="flex items-center gap-3 flex-1">
                    <input type="checkbox" class="refund-item-check w-4 h-4 text-primary rounded" 
                           data-id="${item.id}" 
                           data-price="${unitPrice}" 
                           onchange="updatePartialTotal()">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-800">${item.product?.name || item.product_name}</span>
                        <span class="text-xs text-gray-500">Qtd comprada: ${item.quantity}</span>
                    </div>
                </div>
                <input type="number" class="w-16 p-1 border rounded text-center text-sm refund-item-qty" 
                       value="${item.quantity}" min="1" max="${item.quantity}" 
                       onchange="updatePartialTotal()" disabled>
            `;
            container.appendChild(div);
        });
    }

    function updatePartialTotal() {
        let total = 0;
        let hasSelection = false;
        
        document.querySelectorAll('.refund-item-check').forEach(chk => {
            const qtyInput = chk.closest('div').nextElementSibling;
            qtyInput.disabled = !chk.checked;
            
            if (chk.checked) {
                hasSelection = true;
                let qty = parseInt(qtyInput.value);
                if (qty > parseInt(qtyInput.max)) qtyInput.value = qtyInput.max;
                if (qty < 1) qtyInput.value = 1;
                
                total += parseFloat(chk.dataset.price) * parseInt(qtyInput.value);
            }
        });

        const display = document.getElementById('refund-value-display');
        display.innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    async function submitRefundRequest() {
        const btn = document.getElementById('btn-confirm-refund');
        const reason = document.getElementById('refund-reason').value;
        const type = document.querySelector('input[name="type"]:checked').value;
        const orderId = document.getElementById('refund-order-id').value;

        if (reason.length < 10) {
            alert('Por favor, descreva o motivo com pelo menos 10 caracteres.');
            return;
        }

        let itemsPayload = [];
        if (type === 'Total') {
            itemsPayload = currentRefundOrder.items.map(item => ({ 
                order_item_id: item.id, 
                quantity: item.quantity 
            }));
        } else {
            document.querySelectorAll('.refund-item-check:checked').forEach(chk => {
                const qtyInput = chk.closest('div').nextElementSibling;
                itemsPayload.push({
                    order_item_id: parseInt(chk.dataset.id),
                    quantity: parseInt(qtyInput.value)
                });
            });

            if (itemsPayload.length === 0) {
                alert('Selecione pelo menos um item para o reembolso parcial.');
                return;
            }
        }

        try {
            btn.disabled = true;
            btn.innerText = 'Enviando...';

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch(`/orders/${orderId}/refund-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ reason: reason, items: itemsPayload })
            });

            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'Erro ao processar');

            alert('Solicitação enviada com sucesso!');
            closeRefundModal();
            window.location.reload();

        } catch (error) {
            console.error(error);
            alert('Erro: ' + error.message);
            btn.disabled = false;
            btn.innerText = 'Confirmar Solicitação';
        }
    }
</script>