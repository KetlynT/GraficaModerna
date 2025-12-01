import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { ShippingService } from '../services/shippingService';
import { Truck, Box } from 'lucide-react';

export const ProductDetails = () => {
  const { id } = useParams();
  const [product, setProduct] = useState(null);
  const [whatsappNumber, setWhatsappNumber] = useState('');
  const [loading, setLoading] = useState(true);

  // Estados da Calculadora de Frete
  const [cep, setCep] = useState('');
  const [shippingOptions, setShippingOptions] = useState(null);
  const [calcLoading, setCalcLoading] = useState(false);
  const [calcError, setCalcError] = useState('');

  useEffect(() => {
    const loadData = async () => {
      try {
        const [prod, settings] = await Promise.all([
             ProductService.getById(id),
             ContentService.getSettings()
        ]);
        setProduct(prod);
        if (settings && settings.whatsapp_number) {
            setWhatsappNumber(settings.whatsapp_number);
        }
      } catch (error) {
        console.error("Erro ao carregar dados", error);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [id]);

  const handleCalculateShipping = async (e) => {
    e.preventDefault();
    if (cep.length < 8) {
        setCalcError("Digite um CEP válido.");
        return;
    }
    
    setCalcLoading(true);
    setCalcError('');
    setShippingOptions(null);

    try {
        const options = await ShippingService.calculateForProduct(product.id, cep);
        setShippingOptions(options);
    } catch (error) {
        setCalcError("Erro ao calcular frete. Verifique o CEP.");
    } finally {
        setCalcLoading(false);
    }
  };

  if (loading) return <div className="text-center py-20">Carregando...</div>;
  if (!product) return <div className="text-center py-20">Produto não encontrado.</div>;

  const handleQuote = () => {
    const message = `Olá! Vi o produto *${product.name}* no site e gostaria de mais detalhes.`;
    const number = whatsappNumber || '5511999999999'; 
    window.open(`https://wa.me/${number}?text=${encodeURIComponent(message)}`, '_blank');
  };

  const formattedPrice = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(product.price);

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
      <div className="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:flex">
        
        {/* Coluna Esquerda: Imagem */}
        <div className="md:w-1/2 h-96 md:h-auto bg-gray-100 relative">
          <img 
            src={product.imageUrl || 'https://via.placeholder.com/400'} 
            alt={product.name} 
            className="w-full h-full object-cover"
          />
        </div>

        {/* Coluna Direita: Detalhes e Frete */}
        <div className="md:w-1/2 p-8 flex flex-col">
          <div className="mb-auto">
            <Link to="/" className="text-blue-500 hover:underline text-sm mb-4 block">← Voltar para o catálogo</Link>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">{product.name}</h1>
            <div className="text-3xl font-bold text-blue-600 mb-6">{formattedPrice}</div>
            
            <p className="text-gray-500 mb-6 leading-relaxed whitespace-pre-line border-b pb-6">
                {product.description}
            </p>

            {/* Calculadora de Frete */}
            <div className="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-6">
                <h3 className="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <Truck size={18} className="text-blue-600"/> Calcular Frete e Prazo
                </h3>
                <form onSubmit={handleCalculateShipping} className="flex gap-2 mb-4">
                    <input 
                        type="text" 
                        placeholder="Digite seu CEP" 
                        maxLength="9"
                        className="flex-1 border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        value={cep}
                        onChange={(e) => setCep(e.target.value)}
                    />
                    <button 
                        type="submit" 
                        disabled={calcLoading}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold transition-colors disabled:opacity-50"
                    >
                        {calcLoading ? '...' : 'Calcular'}
                    </button>
                </form>

                {calcError && <div className="text-red-500 text-sm mb-2">{calcError}</div>}

                {shippingOptions && (
                    <div className="space-y-2 animate-in fade-in slide-in-from-top-2">
                        {shippingOptions.map((opt, idx) => (
                            <div key={idx} className="flex justify-between items-center bg-white p-3 rounded border border-gray-200 text-sm shadow-sm">
                                <div className="flex flex-col">
                                    <span className="font-bold text-gray-800">{opt.name}</span>
                                    <span className="text-xs text-gray-500">Entrega em até {opt.deliveryDays} dias úteis</span>
                                </div>
                                <span className="font-bold text-green-600">
                                    {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(opt.price)}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
          </div>

          <button 
            onClick={handleQuote}
            className="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-lg hover:shadow-green-500/30 mt-4"
          >
            <Box size={20}/> Solicitar Orçamento no WhatsApp
          </button>
        </div>
      </div>
    </div>
  );
};