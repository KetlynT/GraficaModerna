'use client'

import { useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { motion } from 'framer-motion';
import PropTypes from 'prop-types';
import { ShoppingCart, Search, Edit } from 'lucide-react';
import { Button } from '@/app/_components/ui/Button';
import { useCart } from '@/app/(website)/_context/CartContext';
import { useAuth } from '@/app/_context/AuthContext';
import { getProxyImageUrl } from '@/lib/imageHelper';

export const ProductCard = ({ product, purchaseEnabled = true }) => {
  const { addToCart } = useCart();
  const { user } = useAuth();
  
  const images = product.imageUrls?.length > 0 ? product.imageUrls : [];

  const [currentImgIndex, setCurrentImgIndex] = useState(0);

  const isAdmin = user?.role === 'Admin';

  const formattedPrice = new Intl.NumberFormat('pt-BR', {
    style: 'currency', currency: 'BRL'
  }).format(product.price);

  const handleQuickAdd = (e) => {
    e.preventDefault();
    addToCart(product, 1); 
  };

  const handleImageError = () => {
    const fallbackUrl = 'https://placehold.co/400x300?text=Sem+Imagem';
    if (imgSrc !== fallbackUrl) {
      setImgSrc(fallbackUrl);
    }
  };

  const rawImageUrl = images[currentImgIndex] || 'https://placehold.co/400x300?text=Sem+Imagem';
  const displayImageUrl = getProxyImageUrl(rawImageUrl);

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ duration: 0.4 }}
      className="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full overflow-hidden"
    >
      <div className="relative h-64 w-full overflow-hidden bg-gray-50">
        <Image 
          src={displayImageUrl}
          alt={product.name} 
          fill
          className="object-cover transform group-hover:scale-110 transition-transform duration-700" 
          onError={handleImageError}
          unoptimized
        />
        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 z-10">
            <Link href={`/produto/${product.id}`}>
                <Button variant="ghost" className="bg-white text-gray-900 hover:bg-gray-100 rounded-full p-3 shadow-lg">
                    <Search size={20} />
                </Button>
            </Link>
            {isAdmin && (
               <Link href={`/putiroski/produtos/${product.id}`}>
                  <Button variant="ghost" className="bg-white text-blue-600 hover:bg-blue-50 rounded-full p-3 shadow-lg">
                      <Edit size={20} />
                  </Button>
               </Link>
            )}
        </div>
      </div>

      <div className="p-6 flex flex-col grow">
        <div className="flex justify-between items-start mb-2">
            <h3 className="text-lg font-bold text-gray-800 line-clamp-1 group-hover:text-primary transition-colors">
                <Link href={`/produto/${product.id}`}>{product.name}</Link>
            </h3>
        </div>
        
        <p className="text-gray-500 text-sm mb-4 line-clamp-2 grow">
          {product.description}
        </p>
        
        <div className="pt-4 border-t border-gray-100 flex items-center justify-between mt-auto">
          <div>
            <span className="text-xs text-gray-400 uppercase font-bold block">A partir de</span>
            <span className="text-xl font-bold text-primary">{formattedPrice}</span>
          </div>
          
          {!isAdmin && purchaseEnabled && (
            <Button 
                size="sm"
                onClick={handleQuickAdd}
                className="rounded-full px-3 py-2 shadow-sm hover:shadow-md"
                title="Adicionar ao Carrinho"
            >
                <ShoppingCart size={18} />
            </Button>
          )}
        </div>
      </div>
    </motion.div>
  );
};

ProductCard.propTypes = {
  product: PropTypes.shape({
    id: PropTypes.string.isRequired,
    name: PropTypes.string.isRequired,
    description: PropTypes.string,
    price: PropTypes.number.isRequired,
    imageUrl: PropTypes.string,
    stockQuantity: PropTypes.number
  }).isRequired,
  purchaseEnabled: PropTypes.bool
};