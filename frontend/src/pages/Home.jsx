import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { ProductCard } from '../components/ProductCard';
import { Button } from '../components/ui/Button';
import { Search, Printer, ChevronLeft, ChevronRight, Filter } from 'lucide-react';
import { motion } from 'framer-motion';

export const Home = () => {
  // Estado dos Dados
  const [products, setProducts] = useState([]);
  const [pagination, setPagination] = useState({ page: 1, totalPages: 1, totalItems: 0 });
  const [loading, setLoading] = useState(true);
  
  // Estado dos Filtros
  const [searchTerm, setSearchTerm] = useState("");
  const [sortOption, setSortOption] = useState(""); // formato: "price-asc"
  
  // Estado das Configurações
  const [settings, setSettings] = useState({
    hero_badge: 'Carregando...',
    hero_title: '...',
    hero_subtitle: '...',
    home_products_title: 'Nossos Produtos',
    home_products_subtitle: 'Confira nosso catálogo',
    whatsapp_number: '',
    hero_bg_url: '' 
  });

  // Debounce para busca
  useEffect(() => {
    const timer = setTimeout(() => {
      loadProducts(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm, sortOption]);

  useEffect(() => {
    ContentService.getSettings().then(data => {
      if (data) setSettings(prev => ({...prev, ...data}));
    });
  }, []);

  const loadProducts = async (page) => {
    setLoading(true);
    try {
      let sort = '', order = '';
      if (sortOption) {
        [sort, order] = sortOption.split('-');
      }

      const data = await ProductService.getAll(page, 8, searchTerm, sort, order);
      
      setProducts(data.items);
      setPagination({
        page: data.page,
        totalPages: data.totalPages,
        totalItems: data.totalItems
      });
    } catch (error) {
      console.error("Erro ao carregar catálogo:", error);
    } finally {
      setLoading(false);
    }
  };

  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= pagination.totalPages) {
      loadProducts(newPage);
      const section = document.getElementById('catalogo');
      if (section) section.scrollIntoView({ behavior: 'smooth' });
    }
  };

  const handleQuoteRedirect = (product) => {
    const message = `Olá! Gostaria de cotar: *${product.name}*.`;
    window.open(`https://wa.me/${settings.whatsapp_number}?text=${encodeURIComponent(message)}`, '_blank');
  };

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
        {/* Barra de Ferramentas */}
        <div className="flex flex-col lg:flex-row justify-between items-end mb-12 gap-6">
          <div>
            <h2 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
              <Printer className="text-blue-600" />
              {settings.home_products_title}
            </h2>
            <p className="text-gray-500 mt-2">{settings.home_products_subtitle}</p>
          </div>
          
          <div className="w-full lg:w-auto flex flex-col sm:flex-row gap-4">
            {/* Campo de Busca */}
            <div className="relative flex-grow sm:w-80">
              <input 
                type="text" 
                placeholder="Buscar produto..." 
                className="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none shadow-sm transition-all"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
              <Search className="absolute left-4 top-3.5 text-gray-400" size={20} />
            </div>

            {/* Filtro de Ordenação */}
            <div className="relative min-w-[200px]">
                <Filter className="absolute left-4 top-3.5 text-gray-400" size={20} />
                <select 
                    className="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer"
                    value={sortOption}
                    onChange={(e) => setSortOption(e.target.value)}
                >
                    <option value="">Mais Recentes</option>
                    <option value="name-asc">Nome (A-Z)</option>
                    <option value="name-desc">Nome (Z-A)</option>
                    <option value="price-asc">Menor Preço</option>
                    <option value="price-desc">Maior Preço</option>
                </select>
            </div>
          </div>
        </div>

        {/* Grid de Produtos */}
        {loading ? (
          <div className="text-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mx-auto mb-4"></div>
            <p className="text-gray-500">Carregando catálogo...</p>
          </div>
        ) : products.length > 0 ? (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
              {products.map((prod) => (
                <ProductCard 
                  key={prod.id} 
                  product={prod} 
                  onQuote={handleQuoteRedirect} 
                />
              ))}
            </div>

            {/* Paginação */}
            {pagination.totalPages > 1 && (
                <div className="flex justify-center items-center gap-4 mt-16">
                    <Button 
                        variant="outline" 
                        className="text-gray-600 border-gray-300 hover:bg-gray-50"
                        onClick={() => handlePageChange(pagination.page - 1)}
                        disabled={pagination.page === 1}
                    >
                        <ChevronLeft size={20} /> Anterior
                    </Button>
                    
                    <span className="text-gray-600 font-medium">
                        Página {pagination.page} de {pagination.totalPages}
                    </span>

                    <Button 
                        variant="outline"
                        className="text-gray-600 border-gray-300 hover:bg-gray-50" 
                        onClick={() => handlePageChange(pagination.page + 1)}
                        disabled={pagination.page === pagination.totalPages}
                    >
                        Próximo <ChevronRight size={20} />
                    </Button>
                </div>
            )}
          </>
        ) : (
          <div className="flex flex-col items-center justify-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 text-center">
            <p className="text-gray-500 text-lg mb-4">Nenhum produto encontrado com estes filtros.</p>
            <Button variant="ghost" className="text-blue-600" onClick={() => {setSearchTerm(''); setSortOption('');}}>
                Limpar Filtros
            </Button>
          </div>
        )}
      </section>
    </>
  );
};