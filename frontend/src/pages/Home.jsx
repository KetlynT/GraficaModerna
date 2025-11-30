import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { ProductCard } from '../components/ProductCard';
import { Button } from '../components/ui/Button';
import { Search, Printer } from 'lucide-react';
import { motion } from 'framer-motion';

export const Home = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  
  const [settings, setSettings] = useState({
    hero_badge: 'Carregando...',
    hero_title: '...',
    hero_subtitle: '...',
    home_products_title: 'Nossos Produtos', // Default
    home_products_subtitle: 'Confira nosso catálogo', // Default
    whatsapp_number: '',
    hero_bg_url: '' 
  });

  useEffect(() => {
    const loadData = async () => {
      try {
        const [prods, serverSettings] = await Promise.all([
           ProductService.getAll(),
           ContentService.getSettings()
        ]);
        setProducts(prods);
        if (serverSettings) {
            setSettings(prev => ({...prev, ...serverSettings}));
        }
      } catch (error) {
        console.error("Erro dados:", error);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, []);

  const handleQuoteRedirect = (product) => {
    const message = `Olá! Gostaria de cotar: *${product.name}*.`;
    window.open(`https://wa.me/${settings.whatsapp_number}?text=${encodeURIComponent(message)}`, '_blank');
  };

  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <>
      {/* Hero Section */}
      <div className="relative bg-blue-900 text-white overflow-hidden transition-all duration-500">
        <div 
            className="absolute inset-0 bg-cover bg-center opacity-20 transform scale-105"
            style={{ 
                backgroundImage: `url('${settings.hero_bg_url || 'https://images.unsplash.com/photo-1562654501-a0ccc0fc3fb1?q=80&w=1932'}')` 
            }}
        ></div>
        
        <div className="relative max-w-7xl mx-auto px-4 py-24 sm:px-6 lg:px-8 flex flex-col items-center text-center">
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <span className="inline-block py-1 px-3 rounded-full bg-blue-800/50 border border-blue-700 text-blue-200 text-sm font-semibold mb-6 backdrop-blur-sm">
              {settings.hero_badge}
            </span>
            <h2 className="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 leading-tight drop-shadow-lg">
              {settings.hero_title}
            </h2>
            <p className="text-xl text-blue-100 mb-10 max-w-2xl mx-auto drop-shadow-md">
              {settings.hero_subtitle}
            </p>
            <div className="flex gap-4 justify-center">
              <Button variant="success" className="rounded-full px-8 py-4 text-lg shadow-xl shadow-green-900/20" onClick={() => document.getElementById('catalogo').scrollIntoView({behavior: 'smooth'})}>
                Ver Catálogo
              </Button>
            </div>
          </motion.div>
        </div>
      </div>

      <section id="catalogo" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div className="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
          <div>
            <h2 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
              <Printer className="text-blue-600" />
              {settings.home_products_title}
            </h2>
            <p className="text-gray-500 mt-2">{settings.home_products_subtitle}</p>
          </div>
          
          <div className="relative w-full md:w-96">
            <input 
              type="text" 
              placeholder="Buscar produto..." 
              className="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none shadow-sm transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <Search className="absolute left-4 top-3.5 text-gray-400" size={20} />
          </div>
        </div>

        {loading ? (
          <div className="text-center py-20">Carregando catálogo...</div>
        ) : filteredProducts.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            {filteredProducts.map((prod) => (
              <ProductCard 
                key={prod.id} 
                product={prod} 
                onQuote={handleQuoteRedirect} 
              />
            ))}
          </div>
        ) : (
          <div className="text-center py-20">
            <p className="text-gray-500 text-lg">Nenhum produto encontrado.</p>
          </div>
        )}
      </section>
    </>
  );
};