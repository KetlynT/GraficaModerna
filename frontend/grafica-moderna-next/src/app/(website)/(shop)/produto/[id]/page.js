'use client';

import { useEffect, useState, useMemo } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import {
  ShoppingCart,
  Plus,
  Minus,
  Zap,
  ChevronLeft,
  ChevronRight,
  Maximize2,
  X
} from 'lucide-react';

import { ProductService } from '@/app/(website)/(shop)/services/productService';
import { ContentService } from '@/app/(website)/services/contentService';
import { useCart } from '@/app/(website)/context/CartContext';
import { Button } from '@/app/(website)/components/ui/Button';
import { ShippingCalculator } from '@/app/(website)/(shop)/carrinho/components/ShippingCalculator';

export default function ProductDetails() {
  const params = useParams();
  const id = params?.id;

  const router = useRouter();
  const { addToCart } = useCart();

  const [product, setProduct] = useState(null);
  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [showZoomModal, setShowZoomModal] = useState(false);
  const [whatsappNumber, setWhatsappNumber] = useState('');
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [purchaseEnabled, setPurchaseEnabled] = useState(true);

  const user =
    typeof window !== 'undefined'
      ? JSON.parse(localStorage.getItem('user') || '{}')
      : {};

  const isAdmin = user?.role === 'Admin';

  useEffect(() => {
    if (!id) return;

    const loadData = async () => {
      try {
        const [prod, settings] = await Promise.all([
          ProductService.getById(id),
          ContentService.getSettings()
        ]);

        setProduct(prod);

        if (settings?.whatsapp_number) {
          setWhatsappNumber(settings.whatsapp_number);
        }

        if (settings?.purchase_enabled === 'false') {
          setPurchaseEnabled(false);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [id]);

  const images = product?.imageUrls?.length
    ? product.imageUrls
    : ['https://placehold.co/600x400?text=Sem+Imagem'];

  const currentImage = useMemo(
    () => images[selectedImageIndex] || images[0],
    [images, selectedImageIndex]
  );

  const handleAddToCart = async () => {
    await addToCart(product, quantity);
  };

  const handleBuyNow = async () => {
    await addToCart(product, quantity);
    router.push('/carrinho');
  };

  const handleCustomQuote = () => {
    const message = `Olá! Gostaria de um *orçamento personalizado* para o produto: *${product.name}*.`;
    const number = whatsappNumber || '5511999999999';

    window.open(
      `https://wa.me/${number}?text=${encodeURIComponent(message)}`,
      '_blank'
    );
  };

  const nextImage = (e) => {
    e?.stopPropagation();
    setSelectedImageIndex((prev) => (prev + 1) % images.length);
  };

  const prevImage = (e) => {
    e?.stopPropagation();
    setSelectedImageIndex((prev) => (prev - 1 + images.length) % images.length);
  };

  if (loading) {
    return <div className="text-center py-20">Carregando...</div>;
  }

  if (!product) {
    return <div className="text-center py-20">Produto não encontrado.</div>;
  }

  const formattedPrice = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(product.price);

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
      {showZoomModal && (
        <div className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4">
          <button
            onClick={() => setShowZoomModal(false)}
            className="absolute top-4 right-4 text-white"
          >
            <X size={40} />
          </button>

          {images.length > 1 && (
            <>
              <button onClick={prevImage} className="absolute left-4 text-white">
                <ChevronLeft size={40} />
              </button>
              <button onClick={nextImage} className="absolute right-4 text-white">
                <ChevronRight size={40} />
              </button>
            </>
          )}

          <div className="relative w-full h-full max-w-5xl max-h-[90vh]">
            <Image
              src={currentImage}
              alt={product.name}
              fill
              className="object-contain"
              unoptimized
            />
          </div>
        </div>
      )}

      <div className="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:flex">
        <div className="md:w-1/2 bg-gray-100">
          <div className="relative h-96 w-full group">
            <Image
              src={currentImage}
              alt={product.name}
              fill
              className="object-cover cursor-zoom-in"
              onClick={() => setShowZoomModal(true)}
              unoptimized
            />

            <button
              onClick={() => setShowZoomModal(true)}
              className="absolute top-2 right-2 bg-white/80 p-2 rounded-full opacity-0 group-hover:opacity-100"
            >
              <Maximize2 size={20} />
            </button>

            {images.length > 1 && (
              <>
                <button
                  onClick={prevImage}
                  className="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 p-2 rounded-full"
                >
                  <ChevronLeft size={20} />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 p-2 rounded-full"
                >
                  <ChevronRight size={20} />
                </button>
              </>
            )}
          </div>

          {images.length > 1 && (
            <div className="flex gap-2 p-4 overflow-x-auto">
              {images.map((url, idx) => (
                <div
                  key={idx}
                  onClick={() => setSelectedImageIndex(idx)}
                  className={`relative w-20 h-20 cursor-pointer border-2 rounded-md ${
                    selectedImageIndex === idx
                      ? 'border-primary'
                      : 'border-transparent'
                  }`}
                >
                  <Image src={url} alt="" fill className="object-cover" unoptimized />
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="md:w-1/2 p-8 flex flex-col">
          <Link href="/" className="text-primary text-sm mb-4">
            ← Voltar para o catálogo
          </Link>

          <h1 className="text-3xl font-bold mb-2">{product.name}</h1>
          <div className="text-3xl font-bold text-primary mb-6">
            {formattedPrice}
          </div>

          <p className="text-gray-500 mb-8 whitespace-pre-line">
            {product.description}
          </p>

          {!isAdmin && purchaseEnabled && (
            <div className="flex gap-4 mb-6">
              <div className="flex border rounded-lg">
                <button onClick={() => setQuantity(q => Math.max(1, q - 1))} className="p-3">
                  <Minus size={16} />
                </button>
                <span className="w-12 flex items-center justify-center font-bold">
                  {quantity}
                </span>
                <button onClick={() => setQuantity(q => q + 1)} className="p-3">
                  <Plus size={16} />
                </button>
              </div>
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {!isAdmin && purchaseEnabled && (
              <>
                <Button onClick={handleAddToCart} variant="outline">
                  <ShoppingCart size={20} /> Adicionar
                </Button>

                <Button onClick={handleBuyNow}>
                  <Zap size={20} /> Comprar Agora
                </Button>
              </>
            )}

            <button
              onClick={handleCustomQuote}
              className="sm:col-span-2 bg-[#25D366] hover:bg-[#1EBE5A] text-white font-bold py-3 rounded-lg"
            >
              Orçamento Personalizado
            </button>
          </div>

          {purchaseEnabled && (
            <div className="mt-6">
              <ShippingCalculator productId={product.id} />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}