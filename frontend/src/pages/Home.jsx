import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ProductCard } from '../components/ProductCard';

export const Home = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  // Busca dados na montagem do componente
  useEffect(() => {
    const fetchCatalog = async () => {
      try {
        const data = await ProductService.getAll();
        setProducts(data);
      } catch (error) {
        alert("Erro ao carregar catálogo. Verifique a API.");
      } finally {
        setLoading(false);
      }
    };
    fetchCatalog();
  }, []);

  // Lógica de Redirecionamento (Separada da View)
  const handleQuoteRedirect = (product) => {
    const phoneNumber = "5511999999999"; // Coloque o número real aqui
    const message = `Olá! Gostaria de um orçamento para: *${product.name}* (Ref: ${product.id}). Preço visto no site: R$ ${product.price}`;
    const url = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header Simples */}
      <header className="bg-white shadow-sm sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
          <h1 className="text-2xl font-extrabold text-gray-900 tracking-tight">
            Gráfica <span className="text-blue-600">A Moderna</span>
          </h1>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="mb-8">
          <h2 className="text-3xl font-bold text-gray-900">Catálogo de Produtos</h2>
          <p className="mt-2 text-gray-500">Qualidade e rapidez para o seu negócio.</p>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {products.map((prod) => (
              <ProductCard 
                key={prod.id} 
                product={prod} 
                onQuote={handleQuoteRedirect} 
              />
            ))}
          </div>
        )}
      </main>
    </div>
  );
};