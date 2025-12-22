@if(!empty($settings->whatsapp_number))
<div id="whatsapp-widget" class="fixed bottom-6 right-6 z-50 hidden animate-in fade-in zoom-in">
    <a
      href="https://wa.me/{{ preg_replace('/\D/', '', $settings->whatsapp_number) }}"
      target="_blank"
      rel="noopener noreferrer"
      class="flex items-center justify-center w-14 h-14 bg-[#25D366] text-white rounded-full shadow-lg hover:bg-[#1EBE5A] hover:scale-110 transition-all duration-300 group"
      aria-label="Falar no WhatsApp"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        
        <span class="absolute right-full mr-3 bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
            Fale Conosco
        </span>
    </a>
</div>
<script>
    // Mostrar apenas se não for rota bloqueada (lógica simples em JS)
    const blockedRoutes = ['/login', '/cadastro'];
    if (!blockedRoutes.some(r => window.location.pathname.startsWith(r))) {
        document.getElementById('whatsapp-widget').classList.remove('hidden');
    }
</script>
@endif