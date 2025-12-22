<div id="cookie-banner" class="fixed bottom-0 left-0 w-full bg-gray-900/95 backdrop-blur text-white p-6 z-100 border-t border-gray-700 shadow-2xl hidden transform transition-transform duration-500 translate-y-full">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex-1">
            <h4 class="text-lg font-bold flex items-center gap-2 mb-2 text-blue-400">
                <i data-lucide="shield-check" width="24"></i> Privacidade e Transparência
            </h4>
            <p class="text-sm text-gray-300 leading-relaxed">
                Utilizamos cookies essenciais para manter sua sessão segura e funcional. 
                Ao continuar, você concorda com nossa
                <a href="/politica-privacidade" class="underline hover:text-blue-400">Política de Privacidade</a>.
            </p>
        </div>

        <div class="flex gap-4 min-w-fit">
            <button onclick="document.getElementById('cookie-banner').classList.add('hidden')" class="text-gray-400 hover:text-white px-4 py-2 hover:bg-white/10 rounded transition">
                Agora não
            </button>

            <button id="accept-cookies" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded shadow-lg transition">
                Entendi e Concordo
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const banner = document.getElementById('cookie-banner');
        const hasConsent = localStorage.getItem('lgpd_consent');
        
        if (!hasConsent) {
            banner.classList.remove('hidden');
            setTimeout(() => {
                banner.classList.remove('translate-y-full');
            }, 100);
        }

        document.getElementById('accept-cookies').addEventListener('click', () => {
            const consentData = {
                accepted: true,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('lgpd_consent', JSON.stringify(consentData));
            banner.classList.add('translate-y-full');
            setTimeout(() => banner.classList.add('hidden'), 500);
        });
    });
</script>