import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { AuthService } from '../services/authService';
import { Button } from '../components/ui/Button'; // Assumindo que você criou no Passo 1
import toast from 'react-hot-toast';
import { 
  LogOut, 
  Plus, 
  Edit, 
  Trash2, 
  Package, 
  Search, 
  Image as ImageIcon 
} from 'lucide-react';

export const AdminDashboard = () => {
  const [products, setProducts] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [loading, setLoading] = useState(true);

  // Estados do Formulário
  const [name, setName] = useState('');
  const [desc, setDesc] = useState('');
  const [price, setPrice] = useState('');
  const [imageFile, setImageFile] = useState(null);
  const [imageUrl, setImageUrl] = useState('');

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    try {
      const data = await ProductService.getAll();
      setProducts(data);
    } catch (error) {
      toast.error("Erro ao carregar produtos.");
      console.error("Erro ao carregar", error);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    
    // Promessa para feedback visual de carregamento/sucesso/erro
    const savePromise = (async () => {
      let finalImageUrl = imageUrl;

      // Se tiver arquivo selecionado, faz upload primeiro
      if (imageFile) {
        finalImageUrl = await ProductService.uploadImage(imageFile);
      }

      const productData = {
        name,
        description: desc,
        price: parseFloat(price),
        imageUrl: finalImageUrl
      };

      if (editingProduct) {
        await ProductService.update(editingProduct.id, productData);
      } else {
        await ProductService.create(productData);
      }

      closeModal();
      await loadProducts();
    })();

    toast.promise(savePromise, {
      loading: 'Salvando...',
      success: 'Produto salvo com sucesso!',
      error: 'Erro ao salvar produto.',
    });
  };

  const handleDelete = async (id) => {
    // Usando toast customizado para confirmação ao invés de window.confirm
    toast((t) => (
      <div className="flex flex-col gap-2">
        <span className="font-medium">Tem certeza que deseja excluir?</span>
        <div className="flex gap-2">
          <button
            onClick={() => {
              toast.dismiss(t.id);
              performDelete(id);
            }}
            className="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600"
          >
            Sim, excluir
          </button>
          <button
            onClick={() => toast.dismiss(t.id)}
            className="bg-gray-200 text-gray-800 px-3 py-1 rounded text-sm hover:bg-gray-300"
          >
            Cancelar
          </button>
        </div>
      </div>
    ), { duration: 5000 });
  };

  const performDelete = async (id) => {
    const deletePromise = (async () => {
      await ProductService.delete(id);
      await loadProducts();
    })();

    toast.promise(deletePromise, {
      loading: 'Excluindo...',
      success: 'Produto excluído!',
      error: 'Erro ao excluir.',
    });
  };

  const openModal = (product = null) => {
    if (product) {
      setEditingProduct(product);
      setName(product.name);
      setDesc(product.description);
      setPrice(product.price);
      setImageUrl(product.imageUrl);
    } else {
      setEditingProduct(null);
      setName('');
      setDesc('');
      setPrice('');
      setImageUrl('');
    }
    setImageFile(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingProduct(null);
  };

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      {/* Navbar Admin */}
      <nav className="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <div className="flex items-center gap-2">
           <div className="bg-blue-600 p-2 rounded-lg">
             <Package className="text-white w-5 h-5" />
           </div>
           <h1 className="text-xl font-bold text-gray-800">Painel Administrativo</h1>
        </div>
        <button 
          onClick={AuthService.logout} 
          className="flex items-center gap-2 text-gray-500 hover:text-red-600 transition-colors font-medium text-sm"
        >
          <LogOut size={18} />
          Sair
        </button>
      </nav>

      <div className="max-w-7xl mx-auto p-6 lg:p-10">
        <div className="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">Gerenciar Produtos</h2>
            <p className="text-gray-500 text-sm mt-1">Adicione, edite ou remova itens do catálogo.</p>
          </div>
          <Button 
            onClick={() => openModal()}
            className="rounded-full shadow-blue-500/20"
          >
            <Plus size={20} />
            Novo Produto
          </Button>
        </div>

        {/* Tabela de Produtos */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          {loading ? (
             <div className="p-10 text-center text-gray-500">Carregando painel...</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-100">
                <thead className="bg-gray-50/50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Produto</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preço</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {products.map((prod) => (
                    <tr key={prod.id} className="hover:bg-blue-50/30 transition-colors">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="h-12 w-12 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                            <img 
                              className="h-full w-full object-cover" 
                              src={prod.imageUrl || 'https://via.placeholder.com/100'} 
                              alt={prod.name} 
                            />
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-bold text-gray-900">{prod.name}</div>
                            <div className="text-xs text-gray-500 truncate max-w-[200px]">{prod.description}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                        {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(prod.price)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex justify-end gap-2">
                          <button 
                            onClick={() => openModal(prod)} 
                            className="p-2 text-blue-600 hover:bg-blue-100 rounded-full transition-colors"
                            title="Editar"
                          >
                            <Edit size={18} />
                          </button>
                          <button 
                            onClick={() => handleDelete(prod.id)} 
                            className="p-2 text-red-500 hover:bg-red-100 rounded-full transition-colors"
                            title="Excluir"
                          >
                            <Trash2 size={18} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Modal de Criar/Editar */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl p-8 max-w-lg w-full shadow-2xl transform transition-all scale-100">
            <h3 className="text-xl font-bold mb-6 text-gray-800 flex items-center gap-2">
              {editingProduct ? <Edit size={24} className="text-blue-600" /> : <Plus size={24} className="text-blue-600" />}
              {editingProduct ? 'Editar Produto' : 'Novo Produto'}
            </h3>
            
            <form onSubmit={handleSave} className="space-y-5">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">Nome do Produto</label>
                <input 
                  type="text" 
                  className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                  placeholder="Ex: Banner Promocional"
                  value={name} 
                  onChange={e => setName(e.target.value)} 
                  required 
                />
              </div>
              
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">Descrição</label>
                <textarea 
                  className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all h-24 resize-none"
                  placeholder="Detalhes do produto..."
                  value={desc} 
                  onChange={e => setDesc(e.target.value)} 
                />
              </div>
              
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">Preço (R$)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                  placeholder="0.00"
                  value={price} 
                  onChange={e => setPrice(e.target.value)} 
                  required 
                />
              </div>
              
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Imagem do Produto</label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:bg-gray-50 transition-colors text-center cursor-pointer relative">
                    <input 
                      type="file" 
                      className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                      onChange={e => setImageFile(e.target.files[0])} 
                      accept="image/*"
                    />
                    <div className="flex flex-col items-center justify-center text-gray-500">
                        <ImageIcon size={32} className="mb-2 text-gray-400" />
                        <span className="text-sm">{imageFile ? imageFile.name : "Clique para upload ou arraste aqui"}</span>
                    </div>
                </div>
                {imageUrl && !imageFile && (
                  <div className="mt-2 text-xs text-gray-500 flex items-center gap-1">
                    <span className="w-2 h-2 bg-green-500 rounded-full"></span> Imagem atual carregada
                  </div>
                )}
              </div>

              <div className="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <Button 
                  type="button" 
                  variant="ghost" 
                  onClick={closeModal}
                >
                  Cancelar
                </Button>
                <Button type="submit">
                  Salvar Produto
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};