<div class="space-y-4" id="address-manager-component">
    <div class="flex justify-between items-center">
        <h3 class="font-bold text-gray-700 flex items-center gap-2">
            <i data-lucide="map-pin" width="18"></i> Endereços Cadastrados
        </h3>
        <button onclick="openAddressForm()" class="text-primary border border-primary/30 hover:bg-primary/5 px-3 py-1 rounded text-sm flex items-center gap-1 transition-colors">
            <i data-lucide="plus" width="16"></i> Novo
        </button>
    </div>

    <div id="am-address-list" class="grid gap-3 max-h-[60vh] overflow-y-auto pr-1">
        <div class="text-center py-4 text-gray-400 text-sm">Carregando...</div>
    </div>

    <div id="am-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-60 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-gray-800" id="am-modal-title">Novo Endereço</h3>
                <button onclick="closeAddressForm()"><i data-lucide="x" class="text-gray-400 hover:text-gray-600"></i></button>
            </div>
            
            <form id="am-form" onsubmit="saveAddress(event)" class="p-5 space-y-3">
                <input type="hidden" name="id" id="am-id">
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="text-xs font-bold text-gray-500">Nome (Ex: Casa)</label>
                        <input name="name" id="am-name" class="input-base" required />
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-bold text-gray-500">Quem recebe?</label>
                        <input name="receiver_name" id="am-receiver" class="input-base" required />
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-500">CEP</label>
                        <input name="zip_code" id="am-zip" class="input-base" required maxlength="9" onblur="fetchCep(this.value)" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Telefone</label>
                        <input name="phone_number" id="am-phone" class="input-base" required maxlength="15" oninput="maskPhone(this)" />
                    </div>
                    
                    <div class="col-span-2">
                        <label class="text-xs font-bold text-gray-500">Rua</label>
                        <input name="street" id="am-street" class="input-base" required />
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-500">Número</label>
                        <input name="number" id="am-number" class="input-base" required />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Comp.</label>
                        <input name="complement" id="am-comp" class="input-base" />
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-500">Bairro</label>
                        <input name="neighborhood" id="am-neighborhood" class="input-base" required />
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="text-xs font-bold text-gray-500">Cidade</label>
                            <input name="city" id="am-city" class="input-base" required />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500">UF</label>
                            <input name="state" id="am-state" class="input-base uppercase" required maxlength="2" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_default" id="am-default" class="w-4 h-4" />
                    <label for="am-default" class="text-sm">Endereço padrão</label>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeAddressForm()" class="px-4 py-2 hover:bg-gray-100 rounded text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:brightness-90 flex items-center gap-2">
                        <i data-lucide="save" width="16"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .input-base { width: 100%; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.5rem; font-size: 0.875rem; outline: none; transition: border-color 0.2s; } 
        .input-base:focus { border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light, rgba(37, 99, 235, 0.2)); }
    </style>
</div>

@push('scripts')
<script>
    let amAddresses = [];

    document.addEventListener('DOMContentLoaded', loadAmAddresses);

    async function loadAmAddresses() {
        try {
            const res = await fetch('/api/user/addresses');
            amAddresses = await res.json();
            renderAmAddresses();
        } catch (e) {
            console.error(e);
        }
    }

    function renderAmAddresses() {
        const list = document.getElementById('am-address-list');
        if (amAddresses.length === 0) {
            list.innerHTML = '<div class="text-center py-6 text-gray-400 text-sm border-2 border-dashed rounded-lg">Nenhum endereço encontrado.</div>';
            return;
        }

        list.innerHTML = amAddresses.map(addr => `
            <div class="bg-white p-4 rounded-lg border transition-all ${addr.is_default ? 'border-primary/50 bg-blue-50' : 'border-gray-200'}">
                <div class="flex justify-between items-start">
                    <div class="grow">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-gray-800">${addr.name}</span>
                            ${addr.is_default ? '<span class="bg-blue-100 text-primary text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1"><i data-lucide="star" width="10" fill="currentColor"></i> Padrão</span>' : ''}
                        </div>
                        <p class="text-gray-600 text-xs">
                            ${addr.street}, ${addr.number} ${addr.complement || ''}
                        </p>
                        <p class="text-gray-600 text-xs">
                            ${addr.neighborhood}, ${addr.city} - ${addr.state}
                        </p>
                        <p class="text-gray-500 text-[10px] mt-1">CEP: ${addr.zip_code}</p>
                    </div>
                    
                    <div class="flex gap-1 ml-2">
                        <button onclick='editAddress(${JSON.stringify(addr)})' class="p-1.5 text-gray-400 hover:text-primary hover:bg-blue-50 rounded"><i data-lucide="edit" width="16"></i></button>
                        <button onclick="deleteAddress(${addr.id})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"><i data-lucide="trash-2" width="16"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    }

    function openAddressForm() {
        document.getElementById('am-form').reset();
        document.getElementById('am-id').value = '';
        document.getElementById('am-modal-title').innerText = 'Novo Endereço';
        document.getElementById('am-modal').classList.replace('hidden', 'flex');
    }

    function closeAddressForm() {
        document.getElementById('am-modal').classList.replace('flex', 'hidden');
    }

    function editAddress(addr) {
        document.getElementById('am-id').value = addr.id;
        document.getElementById('am-name').value = addr.name;
        document.getElementById('am-receiver').value = addr.receiver_name;
        document.getElementById('am-zip').value = addr.zip_code;
        document.getElementById('am-phone').value = addr.phone_number;
        document.getElementById('am-street').value = addr.street;
        document.getElementById('am-number').value = addr.number;
        document.getElementById('am-comp').value = addr.complement || '';
        document.getElementById('am-neighborhood').value = addr.neighborhood;
        document.getElementById('am-city').value = addr.city;
        document.getElementById('am-state').value = addr.state;
        document.getElementById('am-default').checked = !!addr.is_default;
        
        document.getElementById('am-modal-title').innerText = 'Editar Endereço';
        document.getElementById('am-modal').classList.replace('hidden', 'flex');
    }

    async function saveAddress(e) {
        e.preventDefault();
        const form = document.getElementById('am-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_default = document.getElementById('am-default').checked;
        const id = document.getElementById('am-id').value;

        try {
            const url = id ? `/api/user/addresses/${id}` : '/api/user/addresses';
            const method = id ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });

            if (!res.ok) throw new Error();
            
            closeAddressForm();
            loadAmAddresses();
        } catch (err) {
            alert('Erro ao salvar endereço.');
        }
    }

    async function deleteAddress(id) {
        if(!confirm('Excluir este endereço?')) return;
        try {
            await fetch(`/api/user/addresses/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            loadAmAddresses();
        } catch (e) { alert('Erro ao excluir'); }
    }

    async function fetchCep(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length === 8) {
            try {
                const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await res.json();
                if (!data.erro) {
                    document.getElementById('am-street').value = data.logradouro;
                    document.getElementById('am-neighborhood').value = data.bairro;
                    document.getElementById('am-city').value = data.localidade;
                    document.getElementById('am-state').value = data.uf;
                }
            } catch (e) {}
        }
    }
</script>
@endpush