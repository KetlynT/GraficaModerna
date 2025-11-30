import React from 'react';

// Recebe props puras para renderização
export const ProductCard = ({ product, onQuote }) => {
  const formattedPrice = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(product.price);

  return (
    <div className="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col h-full border border-gray-100">
      
      {/* Container de Imagem */}
      <div className="h-48 w-full bg-gray-200 overflow-hidden relative">
         {product.imageUrl ? (
           <img 
             src={product.imageUrl} 
             alt={product.name} 
             className="w-full h-full object-cover" 
           />
         ) : (
           <div className="flex items-center justify-center h-full text-gray-400">Sem Imagem</div>
         )}
      </div>

      {/* Conteúdo */}
      <div className="p-4 flex flex-col flex-grow">
        <h3 className="text-lg font-bold text-gray-800 mb-2">{product.name}</h3>
        <p className="text-gray-600 text-sm mb-4 line-clamp-3 flex-grow">
          {product.description}
        </p>
        
        <div className="mt-auto">
          <span className="block text-xl font-bold text-blue-600 mb-3">
            {formattedPrice}
          </span>
          
          <button
            onClick={() => onQuote(product)}
            className="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md transition-colors flex items-center justify-center gap-2"
          >
            {/* Ícone SVG inline para não depender de libs externas neste exemplo */}
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            Falar no WhatsApp
          </button>
        </div>
      </div>
    </div>
  );
};