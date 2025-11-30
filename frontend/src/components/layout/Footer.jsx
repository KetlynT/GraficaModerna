import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentService } from '../../services/contentService';
import { Phone, Mail, MapPin } from 'lucide-react';

export const Footer = () => {
  const [settings, setSettings] = useState({
    whatsapp_display: 'Carregando...',
    contact_email: 'Carregando...',
    address: 'Carregando...'
  });
  const [pages, setPages] = useState([]);

  useEffect(() => {
    const loadData = async () => {
      // Carrega configurações e lista de páginas em paralelo
      const [settingsData, pagesData] = await Promise.all([
        ContentService.getSettings(),
        ContentService.getAllPages()
      ]);

      if (settingsData) setSettings(settingsData);
      if (pagesData) setPages(pagesData);
    };
    loadData();
  }, []);

  return (
    <footer className="bg-gray-900 text-gray-300 py-12 border-t border-gray-800">
      <div className="max-w-7xl mx-auto px-4 grid md:grid-cols-3 gap-8 text-center md:text-left">
        {/* Marca */}
        <div>
          <h3 className="text-white text-lg font-bold mb-4 flex items-center justify-center md:justify-start gap-2">
            <span className="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">M</span>
            Gráfica A Moderna
          </h3>
          <p className="text-sm leading-relaxed text-gray-400">
            Tecnologia de impressão de ponta gerenciada dinamicamente para oferecer a melhor qualidade do mercado.
          </p>
        </div>

        {/* Links Dinâmicos */}
        <div>
          <h3 className="text-white text-lg font-bold mb-4">Informações</h3>
          <ul className="space-y-3 text-sm">
            <li>
                <Link to="/" className="hover:text-blue-400 transition-colors flex items-center justify-center md:justify-start gap-2">
                    Início
                </Link>
            </li>
            {/* Renderiza links para todas as páginas criadas no CMS */}
            {pages.map(page => (
              <li key={page.id}>
                <Link to={`/pagina/${page.slug}`} className="hover:text-blue-400 transition-colors flex items-center justify-center md:justify-start gap-2">
                    {page.title}
                </Link>
              </li>
            ))}
            <li>
                <Link to="/contato" className="hover:text-blue-400 transition-colors flex items-center justify-center md:justify-start gap-2">
                    Contato
                </Link>
            </li>
          </ul>
        </div>

        {/* Contato via Banco de Dados */}
        <div>
          <h3 className="text-white text-lg font-bold mb-4">Contato</h3>
          <ul className="space-y-3 text-sm">
            <li className="flex items-center justify-center md:justify-start gap-2">
                <Phone size={16} className="text-blue-500" />
                {settings.whatsapp_display}
            </li>
            <li className="flex items-center justify-center md:justify-start gap-2">
                <Mail size={16} className="text-blue-500" />
                {settings.contact_email}
            </li>
            <li className="flex items-center justify-center md:justify-start gap-2">
                <MapPin size={16} className="text-blue-500" />
                {settings.address}
            </li>
          </ul>
        </div>
      </div>
      
      <div className="text-center mt-12 pt-8 border-t border-gray-800 text-xs text-gray-600">
        © {new Date().getFullYear()} Gráfica A Moderna. Sistema Gerenciado.
      </div>
    </footer>
  );
};