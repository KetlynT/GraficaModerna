'use client'
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ContentService } from '@/app/(website)/services/contentService';
import { Phone, Mail, MapPin } from 'lucide-react';

export const Footer = () => {
  const [settings, setSettings] = useState(null);
  const [pages, setPages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        const [settingsData, pagesData] = await Promise.all([
            ContentService.getSettings(),
            ContentService.getAllPages()
        ]);

        if (settingsData) setSettings(settingsData);
        if (pagesData) setPages(pagesData);
      } catch (error) {
        console.error("Erro ao carregar rodapé", error);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, []);

  return (
<footer className="bg-footer-bg text-footer-text py-6 border-t border-white/10">
  {/* Container mais estreito para centralizar melhor */}
  <div className="max-w-5xl mx-auto px-4 grid md:grid-cols-3 gap-12">

    {/* COLUNA 1 */}
    <div className="text-center md:text-left">
      <h3 className="text-lg font-bold mb-4 flex items-center gap-2 md:justify-start justify-center">
        <>
          <span className="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold">
            {settings?.site_name?.charAt(0) || 'G'}
          </span>
          {settings?.site_name || 'Gráfica A Moderna'}
        </>
      </h3>

      <p className="text-sm leading-relaxed opacity-80">
        {settings?.footer_about || 'Configure o texto "Sobre" no painel administrativo.'}
      </p>
    </div>

    {/* COLUNA 2 */}
    <div className="text-center md:text-left">
      <h3 className="text-lg font-bold mb-4">Informações</h3>
      <ul className="space-y-3 text-sm">
        <li><Link href="/">Início</Link></li>
        <li><Link href="/contato">Contato</Link></li>
      </ul>
    </div>

    {/* COLUNA 3 */}
    <div className="text-center md:text-left">
      <h3 className="text-lg font-bold mb-4">Contato</h3>

      <ul className="space-y-3 text-sm">
        <li className="flex items-center gap-2 md:justify-start justify-center">
          <Phone size={16} className="text-primary" />
          {settings?.whatsapp_display || '(00) 0000-0000'}
        </li>

        <li className="flex items-center gap-2 md:justify-start justify-center">
          <Mail size={16} className="text-primary" />
          {settings?.contact_email || 'email@exemplo.com'}
        </li>

        <li className="flex items-center gap-2 md:justify-start justify-center">
          <MapPin size={16} className="text-primary" />
          {settings?.address || 'Endereço não configurado'}
        </li>
      </ul>
    </div>

  </div>

  {/* COPYRIGHT - ALTURA REDUZIDA AO MÍNIMO */}
  <div className="text-center mt-6 pt-4 border-t border-white/10 text-xs opacity-60">
    © {new Date().getFullYear()} {settings?.site_name || 'Gráfica'}. Todos os direitos reservados.
  </div>
</footer>

  );
};