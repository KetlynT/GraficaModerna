import React from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion'; // Animações
import { MessageCircle, Search } from 'lucide-react'; // Ícones
import { Button } from './ui/Button';

export const ProductCard = ({ product, onQuote }) => {
  const formattedPrice = new Intl.NumberFormat('pt-BR', {
    style: 'currency', currency: 'BRL'
  }).format(product.price);

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ duration: 0.4 }}
      className="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden"
    >
      {/* Imagem com Overlay */}
      <div className="relative h-64 overflow-hidden">
        <img 
          src={product.imageUrl || 'https://via.placeholder.com/400?text=Sem+Imagem'} 
          alt={product.name} 
          className="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" 
        />
        {/* Overlay ao passar o mouse */}
        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2">
            <Link to={`/produto/${product.id}`}>
                <Button variant="ghost" className="bg-white text-gray-900 hover:bg-gray-100 rounded-full p-3">
                    <Search size={20} />
                </Button>
            </Link>
        </div>
      </div>

      <div className="p-6 flex flex-col flex-grow">
        <div className="flex justify-between items-start mb-2">
            <h3 className="text-lg font-bold text-gray-800 line-clamp-1 group-hover:text-blue-600 transition-colors">
            {product.name}
            </h3>
        </div>
        
        <p className="text-gray-500 text-sm mb-4 line-clamp-2 flex-grow">
          {product.description}
        </p>
        
        <div className="pt-4 border-t border-gray-100 flex items-center justify-between">
          <div>
            <span className="text-xs text-gray-400 uppercase font-bold block">A partir de</span>
            <span className="text-xl font-bold text-blue-600">{formattedPrice}</span>
          </div>
          
          <Button 
            variant="success" 
            onClick={() => onQuote(product)}
            className="px-4 py-2 rounded-full text-sm shadow-none hover:shadow-lg"
          >
            <MessageCircle size={18} />
            <span className="hidden lg:inline">Orçar</span>
          </Button>
        </div>
      </div>
    </motion.div>
  );
};