<footer class="bg-footer-bg text-footer-text py-6 border-t border-white/10">
    <div class="max-w-5xl mx-auto px-4 grid md:grid-cols-3 gap-12">

        <div class="text-center md:text-left">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 md:justify-start justify-center">
                <span class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold">
                    {{ substr($settings->site_name ?? 'G', 0, 1) }}
                </span>
                {{ $settings->site_name ?? 'Gráfica A Moderna' }}
            </h3>

            <p class="text-sm leading-relaxed opacity-80">
                {{ $settings->footer_about ?? 'Configure o texto "Sobre" no painel administrativo.' }}
            </p>
        </div>

        <div class="text-center md:text-left">
            <h3 class="text-lg font-bold mb-4">Informações</h3>
            <ul class="space-y-3 text-sm">
                <li><a href="/" class="hover:text-primary transition-colors">Início</a></li>
                <li><a href="/contato" class="hover:text-primary transition-colors">Contato</a></li>
                <li><a href="/politica-privacidade" class="hover:text-primary transition-colors">Política de Privacidade</a></li>
            </ul>
        </div>

        <div class="text-center md:text-left">
            <h3 class="text-lg font-bold mb-4">Contato</h3>

            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2 md:justify-start justify-center">
                    <i data-lucide="phone" width="16" height="16" class="text-primary"></i>
                    {{ $settings->whatsapp_display ?? '(00) 0000-0000' }}
                </li>

                <li class="flex items-center gap-2 md:justify-start justify-center">
                    <i data-lucide="mail" width="16" height="16" class="text-primary"></i>
                    {{ $settings->contact_email ?? 'email@exemplo.com' }}
                </li>

                <li class="flex items-center gap-2 md:justify-start justify-center">
                    <i data-lucide="map-pin" width="16" height="16" class="text-primary"></i>
                    {{ $settings->address ?? 'Endereço não configurado' }}
                </li>
            </ul>
        </div>

    </div>

    <div class="text-center mt-6 pt-4 border-t border-white/10 text-xs opacity-60">
        © {{ date('Y') }} {{ $settings->site_name ?? 'Gráfica' }}. Todos os direitos reservados.
    </div>
</footer>