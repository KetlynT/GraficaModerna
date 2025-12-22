@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-gray-50 px-4">
    <div class="bg-white p-10 rounded-2xl shadow-xl text-center max-w-lg w-full border border-gray-200">
        
        <div id="status-verifying" class="hidden">
            <i data-lucide="loader" class="w-16 h-16 text-blue-600 animate-spin mx-auto mb-6"></i>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Confirmando seu Pedido...</h1>
            <p class="text-gray-600 mb-4">Estamos validando a transação com o banco.</p>
        </div>

        <div id="status-success" class="hidden">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check-circle" class="text-green-600 w-12 h-12"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Pagamento Confirmado!</h1>
            <p class="text-gray-600 mb-6">
                Recebemos seu pagamento. Seu pedido já está sendo preparado.
            </p>
        </div>

        <div id="status-processing" class="hidden">
            <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="package" class="text-blue-600 w-12 h-12"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Pedido Realizado!</h1>
            <p class="text-gray-600 mb-6">
                Seu pedido foi gerado com sucesso (<strong id="order-display-id"></strong>).<br/>
                Ainda estamos aguardando a confirmação do pagamento pelo banco, mas você já pode visualizá-lo em sua conta.
            </p>
        </div>

        <div id="status-issue" class="hidden">
            <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="alert-triangle" class="text-orange-600 w-12 h-12"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Pedido Registrado</h1>
            <p class="text-gray-600 mb-6">
                O pedido foi criado, mas não conseguimos confirmar o pagamento automaticamente aqui.<br/>
                Por favor, acesse seus pedidos para verificar se é necessário tentar pagar novamente.
            </p>
        </div>

        <div class="space-y-3 pt-4 hidden" id="action-buttons">
            <a href="/perfil/pedidos" class="w-full bg-primary hover:brightness-90 text-white font-bold py-3 rounded-lg flex items-center justify-center transition-colors">
                <i data-lucide="package" width="18" class="mr-2"></i> 
                <span id="btn-text-status">Acompanhar Meus Pedidos</span>
            </a>
            
            <a href="/" class="w-full bg-transparent hover:bg-gray-50 text-primary font-bold py-3 rounded-lg flex items-center justify-center transition-colors">
                Voltar para a Loja <i data-lucide="arrow-right" width="18" class="ml-2"></i>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

@push('scripts')
<script>
    const orderId = "{{ request('order_id') }}";
    let attempts = 0;
    const maxAttempts = 5;

    document.addEventListener('DOMContentLoaded', () => {
        if (!orderId) {
            showStatus('issue');
            return;
        }

        showStatus('verifying');
        checkStatus();
    });

    async function checkStatus() {
        try {
            // Ajuste a rota da API conforme necessário
            const response = await fetch(`/api/orders/${orderId}/status`);
            const data = await response.json();
            
            if (data.status === 'Pago' || data.status === 'Paid') {
                showStatus('success');
                confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
            } 
            else if (data.status === 'Cancelado' || data.status === 'Falha') {
                showStatus('issue'); 
            } 
            else {
                if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(checkStatus, 3000);
                } else {
                    document.getElementById('order-display-id').innerText = '#' + orderId.slice(0, 8).toUpperCase();
                    showStatus('processing');
                }
            }
        } catch (error) {
            console.error("Erro na verificação:", error);
            if (attempts < maxAttempts) {
                attempts++;
                setTimeout(checkStatus, 3000);
            } else {
                showStatus('processing');
            }
        }
    }

    function showStatus(status) {
        // Esconde todos
        ['verifying', 'success', 'processing', 'issue'].forEach(id => {
            document.getElementById(`status-${id}`).classList.add('hidden');
        });
        
        // Mostra o atual
        document.getElementById(`status-${status}`).classList.remove('hidden');

        // Mostra botões se não estiver verificando
        const actions = document.getElementById('action-buttons');
        if (status !== 'verifying') {
            actions.classList.remove('hidden');
            const btnText = document.getElementById('btn-text-status');
            btnText.innerText = status === 'success' ? 'Acompanhar Meus Pedidos' : 'Ver Status do Pedido';
        } else {
            actions.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection