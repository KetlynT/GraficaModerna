'use client'

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { Phone, Mail, MapPin } from 'lucide-react';
import { ContentService } from '@/app/(website)/_services/contentService';

export const Footer = () => {
  const [settings, setSettings] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        const settingsData = await ContentService.getSettings();
        if (settingsData) setSettings(settingsData);
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
      <div className="max-w-5xl mx-auto px-4 grid md:grid-cols-3 gap-12">

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

        <div className="text-center md:text-left">
          <h3 className="text-lg font-bold mb-4">Informações</h3>
          <ul className="space-y-3 text-sm">
            <li><Link href="/" className="hover:text-primary transition-colors">Início</Link></li>
            <li><Link href="/contato" className="hover:text-primary transition-colors">Contato</Link></li>
            <li><Link href="/politica-privacidade" className="hover:text-primary transition-colors">Política de Privacidade</Link></li>
          </ul>
        </div>

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

      <div className="text-center mt-6 pt-4 border-t border-white/10 text-xs opacity-60">
        © {new Date().getFullYear()} {settings?.site_name || 'Gráfica'}. Todos os direitos reservados.
      </div>
    </footer>
  );
};