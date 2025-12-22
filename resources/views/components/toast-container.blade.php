<div id="toast-container" class="fixed top-4 right-4 z-9999 space-y-4 pointer-events-none">
    @if(session('success'))
        <div class="toast-message bg-white border-l-4 border-green-500 shadow-lg rounded-r p-4 flex items-center gap-3 animate-slide-in pointer-events-auto min-w-[300px]">
            <div class="text-green-500"><i data-lucide="check-circle"></i></div>
            <div class="flex-1 text-sm font-medium text-gray-800">{{ session('success') }}</div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" width="16"></i></button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="toast-message bg-white border-l-4 border-red-500 shadow-lg rounded-r p-4 flex items-center gap-3 animate-slide-in pointer-events-auto min-w-[300px]">
            <div class="text-red-500"><i data-lucide="alert-circle"></i></div>
            <div class="flex-1 text-sm font-medium text-gray-800">
                {{ session('error') ?? ($errors->first() ?? 'Verifique os erros no formulário.') }}
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" width="16"></i></button>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toasts = document.querySelectorAll('.toast-message');
        toasts.forEach(t => {
            setTimeout(() => {
                t.style.opacity = '0';
                t.style.transform = 'translateX(100%)';
                setTimeout(() => t.remove(), 300);
            }, 5000);
        });
    });

    window.showToast = (message, type = 'success') => {
        const container = document.getElementById('toast-container');
        const div = document.createElement('div');
        const colorClass = type === 'success' ? 'border-green-500' : 'border-red-500';
        const icon = type === 'success' ? 'check-circle' : 'alert-circle';
        const iconColor = type === 'success' ? 'text-green-500' : 'text-red-500';
        
        div.className = `bg-white border-l-4 ${colorClass} shadow-lg rounded-r p-4 flex items-center gap-3 animate-slide-in pointer-events-auto min-w-[300px] transition-all duration-300`;
        div.innerHTML = `
            <div class="${iconColor}"><i data-lucide="${icon}"></i></div>
            <div class="flex-1 text-sm font-medium text-gray-800">${message}</div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" width="16"></i></button>
        `;
        
        container.appendChild(div);
        lucide.createIcons();
        
        setTimeout(() => {
            div.style.opacity = '0';
            div.style.transform = 'translateX(100%)';
            setTimeout(() => div.remove(), 300);
        }, 5000);
    };
</script>

<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
    .animate-slide-in { animation: slideIn 0.3s ease-out forwards; }
</style>