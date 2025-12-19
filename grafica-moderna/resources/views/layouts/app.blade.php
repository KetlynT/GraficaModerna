'use client'

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { MessageSquare, ShoppingCart, User, LogOut, Package } from 'lucide-react';
import * as ContentService from '@/app/(website)/_services/contentService';
import { useCart } from '@/app/(website)/_context/CartContext';
import { useAuth } from '@/app/_context/AuthContext';

export const Header = () => {
  const [logoUrl, setLogoUrl] = useState('');
  const [siteName, setSiteName] = useState('');
  const [settingsLoading, setSettingsLoading] = useState(true);
  const [purchaseEnabled, setPurchaseEnabled] = useState(true);
  
  const { cartCount } = useCart();
  const { user, logout, isAuthenticated } = useAuth();
  const router = useRouter();
  
  const isAdmin = user?.role === 'Admin';

  useEffect(() => {
    const loadSettings = async () => {
      try {
        const settings = await ContentService.getSettings();
        if (settings) {
            if (settings.site_logo) setLogoUrl(settings.site_logo);
            setSiteName(settings.site_name || 'Gráfica Online');
            if (settings.purchase_enabled === 'false') setPurchaseEnabled(false);
        }
      } catch (error) {
        console.error("Erro ao carregar topo", error);
        setSiteName('Gráfica Online');
      } finally {
        setSettingsLoading(false);
      }
    };
    loadSettings();
  }, []);

  const handleLogout = async () => {
    await logout();
    router.replace('/');
  };

  return (
    <header className="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        
        <a href="/" className="flex items-center gap-2 group">
          {settingsLoading ? (
            <div className="animate-pulse flex items-center gap-2">
                <div className="w-10 h-10 bg-gray-200 rounded-xl"></div>
                <div className="h-4 w-32 bg-gray-200 rounded"></div>
            </div>
          ) : (
            <>
              {logoUrl ? (
                <div className="relative h-10 w-32">
                    <Image 
                        src={logoUrl} 
                        alt="Logo" 
                        fill
                        className="object-contain transition-transform group-hover:scale-105"
                        unoptimized
                    />
                </div>
              ) : (
                <div className="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:shadow-xl transition-all">
                    {siteName.charAt(0) || 'G'}
                </div>
              )}
              <div>
                <h1 className="text-xl font-bold text-gray-800 leading-none tracking-tight">{siteName}</h1>
              </div>
            </>
          )}
        </a>
        
        <nav className="flex items-center gap-6">
          {settingsLoading ? (
             <div className="animate-pulse h-4 w-24 bg-gray-200 rounded hidden md:block"></div>
          ) : (
             <a href="/contato" className="hidden md:flex items-center gap-2 text-gray-500 hover:text-primary transition-colors font-medium">
                <MessageSquare size={20} />
                <span className="hidden lg:inline">Fale Conosco</span>
             </a>
          )}

          {!isAdmin && !settingsLoading && purchaseEnabled && (
            <a href="/carrinho" className="relative group p-2">
                <ShoppingCart size={24} className="text-gray-600 group-hover:text-primary transition-colors" />
                {cartCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow-sm animate-bounce">
                        {cartCount}
                    </span>
                )}
            </a>
          )}

          {!settingsLoading && (
            isAuthenticated ? (
                <div className="flex items-center gap-4 border-l pl-6 border-gray-200">

                        <>
                            <a href="/meus-pedidos" className="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary" title="Meus Pedidos">
                                <Package size={20} />
                            </a>
                            <a href="/perfil" className="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary" title="Meu Perfil">
                                <User size={20} />
                            </a>
                        </>
                    
                    <button onClick={handleLogout} className="text-gray-400 hover:text-red-500 ml-2" title="Sair">
                        <LogOut size={20} />
                    </button>
                </div>
            ) : (
                purchaseEnabled && (
                    <a href="/login" className="flex items-center gap-2 text-sm font-bold text-primary hover:brightness-75 border border-gray-200 bg-gray-50 px-4 py-2 rounded-full transition-all hover:shadow-md">
                        <User size={18} /> Entrar
                    </a>
                )
            )
          )}
        </nav>
      </div>
    </header>
  );
};

'use client'

import { useEffect, useState } from 'react';
import a from 'next/a';
import { Phone, Mail, MapPin } from 'lucide-react';
import * as ContentService from '@/app/(website)/_services/contentService';

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
            <li><a href="/" className="hover:text-primary transition-colors">Início</a></li>
            <li><a href="/contato" className="hover:text-primary transition-colors">Contato</a></li>
            <li><a href="/politica-privacidade" className="hover:text-primary transition-colors">Política de Privacidade</a></li>
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