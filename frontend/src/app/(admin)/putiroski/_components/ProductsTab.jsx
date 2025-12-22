import { useEffect, useState, useCallback } from 'react';
import toast from 'react-hot-toast';
import Image from 'next/image';
import { Search, ArrowUp, ArrowDown, ArrowUpDown, Edit, Trash2, Box, X, Upload, ChevronLeft, ChevronRight, Image as ImageIcon } from 'lucide-react';
import { Button } from '@/app/_components/ui/Button';
import { DashboardService } from '@/app/(admin)/putiroski/_services/dashboardService';

const ProductsTab = () => {
  const [products, setProducts] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const pageSize = 10;
  
  const [sortConfig, setSortConfig] = useState({ key: 'createdAt', direction: 'desc' });
  const [searchTerm, setSearchTerm] = useState('');

  const [name, setName] = useState('');
  const [desc, setDesc] = useState('');
  const [price, setPrice] = useState('');
  const [stock, setStock] = useState('');
  
  const [images, setImages] = useState([]); 
  const [isUploading, setIsUploading] = useState(false);

  const [weight, setWeight] = useState('');
  const [width, setWidth] = useState('');
  const [height, setHeight] = useState('');
  const [length, setLength] = useState('');

  const [loading, setLoading] = useState(false);

  const loadProducts = useCallback(async () => {
    try {
        setLoading(true);
        const data = await DashboardService.getProducts(currentPage, pageSize, searchTerm, sortConfig.key, sortConfig.direction);

        setProducts(data.items);
        const total = Math.ceil(data.totalCount / pageSize); 
        setTotalPages(total || 1); 
    } catch (e) { 
        toast.error("Erro ao carregar produtos"); 
    } finally {
        setLoading(false);
    }
  }, [currentPage, pageSize, searchTerm, sortConfig]);

  useEffect(() => { 
      loadProducts(); 
  }, [loadProducts]);

  const handleSort = (key) => {
      let direction = 'asc';
      if (sortConfig.key === key && sortConfig.direction === 'asc') {
          direction = 'desc';
      }
      setSortConfig({ key, direction });
  };

  const SortIcon = ({ column }) => {
      if (sortConfig.key !== column) return <ArrowUpDown size={14} className="text-gray-300 ml-1 inline" />;
      return sortConfig.direction === 'asc' 
          ? <ArrowUp size={14} className="text-blue-600 ml-1 inline" /> 
          : <ArrowDown size={14} className="text-blue-600 ml-1 inline" />;
  };

  const handleImageSelect = async (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    setIsUploading(true);
    try {
        const uploadPromises = files.map(file => DashboardService.uploadImage(file));
        const uploadedUrls = await Promise.all(uploadPromises);
        
        setImages(prev => [...prev, ...uploadedUrls]);
        toast.success(`${files.length} imagem(ns) adicionada(s)`);
    } catch (error) {
        console.error(error);
        toast.error("Erro ao fazer upload das imagens");
    } finally {
        setIsUploading(false);
        e.target.value = '';
    }
  };

  const removeImage = (index) => {
    setImages(prev => prev.filter((_, i) => i !== index));
  };

  const moveImage = (index, direction) => {
    const newImages = [...images];
    if (direction === 'left' && index > 0) {
        [newImages[index - 1], newImages[index]] = [newImages[index], newImages[index - 1]];
    } else if (direction === 'right' && index < newImages.length - 1) {
        [newImages[index + 1], newImages[index]] = [newImages[index], newImages[index + 1]];
    }
    setImages(newImages);
  };

  const handleSave = async (e) => {
      e.preventDefault();
      setLoading(true);
      try {
          const formattedPrice = parseFloat(price.toString().replace(',', '.'));

        if (isNaN(formattedPrice)) {
            toast.error("Preço inválido");
            setLoading(false);
            return;
        }

          const data = { 
              name, 
              description: desc, 
              price: parseFloat(formattedPrice), 
              imageUrls: images,
              stockQuantity: parseInt(stock),
              weight: parseFloat(weight),
              width: parseInt(width),
              height: parseInt(height),
              length: parseInt(length)
          };
          
          if(editingProduct) await DashboardService.updateProduct(editingProduct.id, data);
          else await DashboardService.createProduct(data);

          setIsModalOpen(false);
          loadProducts();
          toast.success("Salvo com sucesso!");
      } catch (e) { 
          toast.error("Erro ao salvar. Verifique os campos."); 
          console.error(e);
      } finally {
          setLoading(false);
      }
  };

  const handleDelete = async (id) => {
      if(!window.confirm("Tem certeza que deseja excluir este produto?")) return;
      try {
          await DashboardService.deleteProduct(id);
          loadProducts();
          toast.success("Excluído!");
      } catch(e) { toast.error("Erro ao excluir"); }
  };

  const openModal = (p = null) => {
      setEditingProduct(p);
      setName(p?.name || '');
      setDesc(p?.description || '');
      setPrice(p?.price ? p.price.toString().replace('.', ',') : '');
      setStock(p?.stockQuantity || '');
      
      setImages(p?.imageUrls || []);

      setWeight(p?.weight || '');
      setWidth(p?.width || '');
      setHeight(p?.height || '');
      setLength(p?.length || '');
      setIsModalOpen(true);
  };

  const handlePriceChange = (e) => {
    let val = e.target.value;
    val = val.replace(/[^0-9.,]/g, '');
    const parts = val.split(/[,.]/);
    if (parts.length > 2) return; 
    setPrice(val);
  };

  return (
      <div>
          <div className="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
              <h2 className="text-xl font-bold text-gray-800">Catálogo de Produtos</h2>
              <div className="flex gap-2 w-full md:w-auto">
                  <div className="relative grow">
                      <Search className="absolute left-3 top-2.5 text-gray-400" size={18} />
                      <input 
                          className="pl-10 pr-4 py-2 border rounded-lg w-full md:w-64 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                          placeholder="Buscar por nome..."
                          value={searchTerm}
                          onChange={e => setSearchTerm(e.target.value)}
                      />
                  </div>
                  <Button onClick={() => openModal()} size="sm">+ Novo</Button>
              </div>
          </div>

          <div className="bg-white rounded-lg shadow border overflow-hidden">
              <table className="w-full text-left">
                  <thead className="bg-gray-50 border-b text-gray-600 text-sm uppercase font-bold">
                      <tr>
                          <th className="p-4 cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('name')}>
                              Produto <SortIcon column="name" />
                          </th>
                          <th className="p-4 text-center cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('stockQuantity')}>
                              Estoque <SortIcon column="stockQuantity" />
                          </th>
                          <th className="p-4 cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('price')}>
                              Preço <SortIcon column="price" />
                          </th>
                          <th className="p-4 text-right">Ações</th>
                      </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                      {products.map(p => (
                          <tr key={p.id} className="hover:bg-gray-50 transition-colors">
                              <td className="p-4 align-middle">
                                  <div className="flex items-center gap-3">
                                      <div className="w-10 h-10 rounded bg-gray-100 overflow-hidden relative border shrink-0">
                                          {p.imageUrls && p.imageUrls.length > 0 ? (
                                              <Image src={p.imageUrls[0]} width={40} height={40} alt="" className="w-full h-full object-cover" />
                                            ) : (
                                              <ImageIcon className="text-gray-300 w-full h-full p-2" />
                                          )}
                                      </div>
                                      <div>
                                          <div className="font-bold text-gray-800">{p.name}</div>
                                          <div className="text-xs text-gray-500 truncate max-w-xs">{p.description}</div>
                                      </div>
                                  </div>
                              </td>
                              <td className="p-4 text-center align-middle">
                                  <span className={`px-2 py-1 rounded text-xs font-bold ${p.stockQuantity < 10 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'}`}>
                                    {p.stockQuantity} un
                                  </span>
                              </td>
                              <td className="p-4 align-middle font-mono text-blue-600">R$ {p.price.toFixed(2)}</td>
                              <td className="p-4 text-right align-middle">
                                  <div className="flex justify-end gap-2">
                                    <button onClick={() => openModal(p)} className="text-blue-600 hover:bg-blue-50 p-2 rounded"><Edit size={18}/></button>
                                    <button onClick={() => handleDelete(p.id)} className="text-red-600 hover:bg-red-50 p-2 rounded"><Trash2 size={18}/></button>
                                  </div>
                              </td>
                          </tr>
                      ))}
                  </tbody>
              </table>
            <div className="p-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                <span className="text-sm text-gray-500">
                    Página <strong>{currentPage}</strong> de <strong>{totalPages}</strong>
                </span>
                <div className="flex gap-2">
                    <button 
                        onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                        disabled={currentPage === 1}
                        className="px-3 py-1 border rounded hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium text-gray-600"
                    >
                        Anterior
                    </button>
                    <button 
                        onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                        disabled={currentPage === totalPages}
                        className="px-3 py-1 border rounded hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium text-gray-600"
                    >
                        Próxima
                    </button>
                </div>
            </div>
          </div>

          {isModalOpen && (
              <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto py-10">
                  <form onSubmit={handleSave} className="bg-white p-8 rounded-xl w-full max-w-3xl space-y-5 shadow-2xl animate-in fade-in zoom-in duration-200 my-auto">
                      <div className="flex justify-between items-center border-b pb-2">
                          <h3 className="font-bold text-2xl text-gray-800">{editingProduct ? 'Editar Produto' : 'Novo Produto'}</h3>
                          <button type="button" onClick={()=>setIsModalOpen(false)} className="text-gray-400 hover:text-gray-600"><X size={24} /></button>
                      </div>
                      
                      <div className="grid md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                            <input className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" value={name} onChange={e=>setName(e.target.value)} required />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold text-gray-700 mb-1">Descrição</label>
                            <textarea className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none h-24 resize-none" value={desc} onChange={e=>setDesc(e.target.value)} />
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">Preço (R$)</label>
                            <input 
                                className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" 
                                type="text" 
                                placeholder="0,00"
                                value={price} 
                                onChange={handlePriceChange} 
                                required 
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">Estoque</label>
                            <input className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" type="number" value={stock} onChange={e=>setStock(e.target.value)} required />
                        </div>

                        <div className="md:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label className="block text-sm font-bold text-gray-700 mb-2">Galeria de Imagens</label>
                            
                            {images.length > 0 && (
                                <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-4">
                                    {images.map((url, index) => (
                                        <div key={index} className="relative aspect-square group bg-white rounded-lg border overflow-hidden shadow-sm">
                                            <Image src={url} width={100} height={100} alt={`Img ${index}`} className="w-full h-full object-cover" />
                                            
                                            <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                                <button 
                                                    type="button" 
                                                    onClick={() => removeImage(index)} 
                                                    className="p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600"
                                                    title="Remover"
                                                >
                                                    <Trash2 size={14} />
                                                </button>
                                                <div className="flex gap-2">
                                                    {index > 0 && (
                                                        <button 
                                                            type="button" 
                                                            onClick={() => moveImage(index, 'left')} 
                                                            className="p-1 bg-white text-gray-800 rounded-full hover:bg-gray-100"
                                                            title="Mover para esquerda (Capa)"
                                                        >
                                                            <ChevronLeft size={14} />
                                                        </button>
                                                    )}
                                                    {index < images.length - 1 && (
                                                        <button 
                                                            type="button" 
                                                            onClick={() => moveImage(index, 'right')} 
                                                            className="p-1 bg-white text-gray-800 rounded-full hover:bg-gray-100"
                                                            title="Mover para direita"
                                                        >
                                                            <ChevronRight size={14} />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            {index === 0 && (
                                                <span className="absolute bottom-0 left-0 right-0 bg-blue-600 text-white text-[10px] font-bold text-center py-0.5">
                                                    CAPA
                                                </span>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="relative">
                                <input 
                                    type="file" 
                                    multiple 
                                    accept="image/*"
                                    onChange={handleImageSelect} 
                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    disabled={isUploading}
                                />
                                <div className={`border-2 border-dashed border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center text-gray-500 transition-colors ${isUploading ? 'bg-gray-100' : 'hover:bg-blue-50 hover:border-blue-400 bg-white'}`}>
                                    {isUploading ? (
                                        <>
                                            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mb-2"></div>
                                            <span className="text-sm">Enviando imagens...</span>
                                        </>
                                    ) : (
                                        <>
                                            <Upload size={24} className="mb-2 text-gray-400" />
                                            <span className="text-sm font-medium">Clique ou arraste imagens aqui</span>
                                            <span className="text-xs text-gray-400 mt-1">Suporta múltiplos arquivos (JPG, PNG)</span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                      </div>

                      <div className="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-2">
                        <h4 className="font-bold text-sm text-gray-700 mb-3 flex items-center gap-2"><Box size={16}/> Dados para Frete</h4>
                        <div className="grid grid-cols-4 gap-4">
                            <div><label className="block text-xs font-bold text-gray-600 mb-1">Peso (kg)</label><input type="number" step="0.001" className="w-full border p-2 rounded text-sm" value={weight} onChange={e=>setWeight(e.target.value)} required /></div>
                            <div><label className="block text-xs font-bold text-gray-600 mb-1">Largura</label><input type="number" className="w-full border p-2 rounded text-sm" value={width} onChange={e=>setWidth(e.target.value)} required /></div>
                            <div><label className="block text-xs font-bold text-gray-600 mb-1">Altura</label><input type="number" className="w-full border p-2 rounded text-sm" value={height} onChange={e=>setHeight(e.target.value)} required /></div>
                            <div><label className="block text-xs font-bold text-gray-600 mb-1">Comp.</label><input type="number" className="w-full border p-2 rounded text-sm" value={length} onChange={e=>setLength(e.target.value)} required /></div>
                        </div>
                      </div>

                      <div className="flex justify-end gap-3 pt-4 border-t">
                          <Button type="button" variant="ghost" onClick={()=>setIsModalOpen(false)}>Cancelar</Button>
                          <Button type="submit" isLoading={loading}>Salvar Produto</Button>
                      </div>
                  </form>
              </div>
          )}
      </div>
  );
};

export default ProductsTab;