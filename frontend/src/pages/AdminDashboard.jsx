import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { AuthService } from '../services/authService';
import { Button } from '../components/ui/Button';
import toast from 'react-hot-toast';
import { 
  LogOut, Edit, Trash2, Package, Settings, FileText, Layout, Image as ImageIcon, Box
} from 'lucide-react';

import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

export const AdminDashboard = () => {
  const [activeTab, setActiveTab] = useState('products'); 

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      <nav className="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-30 shadow-sm">
        <div className="flex items-center gap-2">
           <div className="bg-blue-600 p-2 rounded-lg text-white font-bold">GM</div>
           <h1 className="text-xl font-bold text-gray-800">Painel Restrito</h1>
        </div>
        <div className="flex items-center gap-4">
            <a href="/" className="text-sm text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">Ver Site</a>
            <button 
            onClick={AuthService.logout} 
            className="flex items-center gap-2 text-gray-500 hover:text-red-600 transition-colors font-medium text-sm"
            >
            <LogOut size={18} /> Sair
            </button>
        </div>
      </nav>

      <div className="max-w-7xl mx-auto p-6 lg:p-10">
        <div className="flex gap-4 mb-8 border-b border-gray-200 pb-1 overflow-x-auto">
            <TabButton active={activeTab === 'products'} onClick={() => setActiveTab('products')} icon={<Package size={18} />}>
                Produtos
            </TabButton>
            <TabButton active={activeTab === 'settings'} onClick={() => setActiveTab('settings')} icon={<Settings size={18} />}>
                Configurações do Site
            </TabButton>
            <TabButton active={activeTab === 'pages'} onClick={() => setActiveTab('pages')} icon={<FileText size={18} />}>
                Páginas de Conteúdo
            </TabButton>
        </div>

        <div className="animate-in fade-in duration-300">
            {activeTab === 'products' && <ProductsTab />}
            {activeTab === 'settings' && <SettingsTab />}
            {activeTab === 'pages' && <PagesTab />}
        </div>
      </div>
    </div>
  );
};

const TabButton = ({ active, onClick, children, icon }) => (
    <button 
        onClick={onClick}
        className={`flex items-center gap-2 px-6 py-3 font-medium transition-all rounded-t-lg whitespace-nowrap ${
            active 
            ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' 
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
        }`}
    >
        {icon} {children}
    </button>
);

const InputGroup = ({ label, name, value, onChange, type = "text", placeholder }) => (
    <div>
        <label className="block text-sm font-semibold text-gray-700 mb-1">{label}</label>
        <input 
            type={type} name={name} value={value || ''} onChange={onChange} placeholder={placeholder}
            className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors"
        />
    </div>
);

// --- ABA 1: PRODUTOS (ATUALIZADA) ---
const ProductsTab = () => {
  const [products, setProducts] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  
  // Estados do Formulário
  const [name, setName] = useState('');
  const [desc, setDesc] = useState('');
  const [price, setPrice] = useState('');
  const [imageFile, setImageFile] = useState(null);
  
  // Novos Estados para Frete
  const [weight, setWeight] = useState('');
  const [width, setWidth] = useState('');
  const [height, setHeight] = useState('');
  const [length, setLength] = useState('');

  const [loading, setLoading] = useState(false);

  useEffect(() => { loadProducts(); }, []);

  const loadProducts = async () => {
      try {
          const data = await ProductService.getAll(1, 100);
          setProducts(data.items);
      } catch (e) { toast.error("Erro ao carregar produtos"); }
  };

  const handleSave = async (e) => {
      e.preventDefault();
      setLoading(true);
      try {
          let imageUrl = editingProduct?.imageUrl || '';
          if(imageFile) imageUrl = await ProductService.uploadImage(imageFile);

          const data = { 
              name, 
              description: desc, 
              price: parseFloat(price), 
              imageUrl,
              // Convertendo para números para evitar erro no backend
              weight: parseFloat(weight),
              width: parseInt(width),
              height: parseInt(height),
              length: parseInt(length)
          };
          
          if(editingProduct) await ProductService.update(editingProduct.id, data);
          else await ProductService.create(data);

          setIsModalOpen(false);
          setImageFile(null);
          loadProducts();
          toast.success("Salvo com sucesso!");
      } catch (e) { 
          toast.error("Erro ao salvar. Verifique se todos os campos numéricos estão corretos."); 
          console.error(e);
      } finally {
          setLoading(false);
      }
  };

  const handleDelete = async (id) => {
      if(!window.confirm("Tem certeza que deseja excluir este produto?")) return;
      try {
          await ProductService.delete(id);
          loadProducts();
          toast.success("Excluído!");
      } catch(e) { toast.error("Erro ao excluir"); }
  };

  const openModal = (p = null) => {
      setEditingProduct(p);
      setName(p?.name || '');
      setDesc(p?.description || '');
      setPrice(p?.price || '');
      
      // Preenchendo campos de frete
      setWeight(p?.weight || '');
      setWidth(p?.width || '');
      setHeight(p?.height || '');
      setLength(p?.length || '');

      setImageFile(null);
      setIsModalOpen(true);
  };

  return (
      <div>
          <div className="flex justify-between mb-4">
              <h2 className="text-xl font-bold text-gray-800">Catálogo de Produtos</h2>
              <Button onClick={() => openModal()}>+ Novo Produto</Button>
          </div>
          <div className="bg-white rounded-lg shadow border overflow-hidden">
              <table className="w-full text-left">
                  <thead className="bg-gray-50 border-b text-gray-600 text-sm uppercase">
                      <tr>
                          <th className="p-4 font-bold">Produto</th>
                          <th className="p-4 font-bold">Dados Logísticos</th>
                          <th className="p-4 font-bold">Preço</th>
                          <th className="p-4 text-right font-bold">Ações</th>
                      </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                      {products.map(p => (
                          <tr key={p.id} className="hover:bg-gray-50 transition-colors">
                              <td className="p-4">
                                  <div className="font-bold text-gray-800">{p.name}</div>
                                  <div className="text-xs text-gray-500 truncate max-w-xs">{p.description}</div>
                              </td>
                              <td className="p-4 text-xs text-gray-500">
                                  <div>Peso: {p.weight} kg</div>
                                  <div>Dim: {p.width}x{p.height}x{p.length} cm</div>
                              </td>
                              <td className="p-4 font-mono text-blue-600">R$ {p.price.toFixed(2)}</td>
                              <td className="p-4 text-right gap-2 flex justify-end">
                                  <button onClick={() => openModal(p)} className="text-blue-600 hover:bg-blue-50 p-2 rounded"><Edit size={18}/></button>
                                  <button onClick={() => handleDelete(p.id)} className="text-red-600 hover:bg-red-50 p-2 rounded"><Trash2 size={18}/></button>
                              </td>
                          </tr>
                      ))}
                  </tbody>
              </table>
          </div>

          {isModalOpen && (
              <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto py-10">
                  <form onSubmit={handleSave} className="bg-white p-8 rounded-xl w-full max-w-2xl space-y-5 shadow-2xl animate-in fade-in zoom-in duration-200 my-auto">
                      <h3 className="font-bold text-2xl text-gray-800 border-b pb-2">{editingProduct ? 'Editar Produto' : 'Novo Produto'}</h3>
                      
                      <div className="grid md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                            <input className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Cartão de Visita" value={name} onChange={e=>setName(e.target.value)} required />
                        </div>
                        
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold text-gray-700 mb-1">Descrição</label>
                            <textarea className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none h-24 resize-none" placeholder="Detalhes do produto..." value={desc} onChange={e=>setDesc(e.target.value)} />
                        </div>
                        
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">Preço (R$)</label>
                            <input className="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" type="number" step="0.01" placeholder="0.00" value={price} onChange={e=>setPrice(e.target.value)} required />
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">Imagem</label>
                            <input type="file" onChange={e=>setImageFile(e.target.files[0])} className="text-sm w-full file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        </div>
                      </div>

                      {/* Seção de Frete */}
                      <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 className="font-bold text-sm text-gray-700 mb-3 flex items-center gap-2"><Box size={16}/> Dados para Cálculo de Frete</h4>
                        <div className="grid grid-cols-4 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1">Peso (kg)</label>
                                <input type="number" step="0.001" className="w-full border p-2 rounded text-sm" placeholder="0.500" value={weight} onChange={e=>setWeight(e.target.value)} required />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1">Largura (cm)</label>
                                <input type="number" className="w-full border p-2 rounded text-sm" placeholder="20" value={width} onChange={e=>setWidth(e.target.value)} required />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1">Altura (cm)</label>
                                <input type="number" className="w-full border p-2 rounded text-sm" placeholder="10" value={height} onChange={e=>setHeight(e.target.value)} required />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1">Comp. (cm)</label>
                                <input type="number" className="w-full border p-2 rounded text-sm" placeholder="30" value={length} onChange={e=>setLength(e.target.value)} required />
                            </div>
                        </div>
                      </div>

                      <div className="flex justify-end gap-3 pt-4">
                          <Button type="button" variant="ghost" onClick={()=>setIsModalOpen(false)}>Cancelar</Button>
                          <Button type="submit" isLoading={loading}>Salvar</Button>
                      </div>
                  </form>
              </div>
          )}
      </div>
  );
};

// --- ABA 2: CONFIGURAÇÕES GERAIS ---
const SettingsTab = () => {
    const [formData, setFormData] = useState({});
    const [heroImageFile, setHeroImageFile] = useState(null);
    const [logoFile, setLogoFile] = useState(null); 
    const [loading, setLoading] = useState(false);

    useEffect(() => { load(); }, []);

    const load = async () => {
        const data = await ContentService.getSettings();
        setFormData(data);
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            let updatedData = { ...formData };
            if (heroImageFile) updatedData.hero_bg_url = await ProductService.uploadImage(heroImageFile);
            if (logoFile) updatedData.site_logo = await ProductService.uploadImage(logoFile);

            await ContentService.saveSettings(updatedData);
            toast.success("Configurações atualizadas!");
        } catch (e) {
            toast.error("Erro ao salvar configurações.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSave} className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 max-w-4xl mx-auto space-y-8">
            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><ImageIcon className="text-blue-600"/> Identidade Visual</h2>
                <div className="grid md:grid-cols-2 gap-4">
                    <div className="border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                        <label className="block text-sm font-bold mb-2 text-gray-700">Logo do Site</label>
                        <div className="flex flex-col items-center gap-2">
                            {formData.site_logo && <img src={formData.site_logo} alt="Logo" className="h-12 object-contain"/>}
                            <input type="file" onChange={e => setLogoFile(e.target.files[0])} className="text-xs" />
                        </div>
                    </div>
                    <div className="border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                        <label className="block text-sm font-bold mb-2 text-gray-700">Imagem de Fundo (Topo)</label>
                        <input type="file" onChange={e => setHeroImageFile(e.target.files[0])} className="text-xs" />
                    </div>
                </div>
            </div>

            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><Layout className="text-blue-600"/> Textos da Home</h2>
                <div className="grid gap-4">
                    <InputGroup label="Badge" name="hero_badge" value={formData.hero_badge} onChange={handleChange} />
                    <InputGroup label="Título" name="hero_title" value={formData.hero_title} onChange={handleChange} />
                    <InputGroup label="Subtítulo" name="hero_subtitle" value={formData.hero_subtitle} onChange={handleChange} />
                </div>
            </div>

            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><Settings className="text-blue-600"/> Configurações de Envio & Contato</h2>
                <div className="grid md:grid-cols-2 gap-4">
                    <InputGroup label="WhatsApp (Números)" name="whatsapp_number" value={formData.whatsapp_number} onChange={handleChange} />
                    <InputGroup label="WhatsApp (Visível)" name="whatsapp_display" value={formData.whatsapp_display} onChange={handleChange} />
                    <InputGroup label="CEP de Origem (Para cálculo de frete)" name="sender_cep" value={formData.sender_cep} onChange={handleChange} placeholder="00000-000" />
                    <InputGroup label="Email" name="contact_email" value={formData.contact_email} onChange={handleChange} />
                </div>
            </div>

            <Button type="submit" className="w-full" isLoading={loading}>Salvar Configurações</Button>
        </form>
    );
};

// --- ABA 3: PÁGINAS ---
const PagesTab = () => {
    const [pages, setPages] = useState([]);
    const [selectedPage, setSelectedPage] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => { ContentService.getAllPages().then(setPages); }, []);

    const handleEdit = async (page) => {
        const fullPage = await ContentService.getPage(page.slug);
        setSelectedPage(fullPage);
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            await ContentService.updatePage(selectedPage.id, selectedPage);
            toast.success("Página salva!");
            ContentService.getAllPages().then(setPages);
        } catch(e) {
            toast.error("Erro ao salvar página.");
        } finally {
            setLoading(false);
        }
    };

    const modules = { toolbar: [ [{ 'header': [1, 2, 3, false] }], ['bold', 'italic', 'underline'], [{'list': 'ordered'}, {'list': 'bullet'}], ['link', 'clean'] ] };

    return (
        <div className="grid md:grid-cols-3 gap-8">
            <div className="bg-white rounded-lg shadow border p-4 h-fit">
                <h3 className="font-bold border-b pb-2 mb-2 text-gray-700">Selecione uma Página</h3>
                {pages.map(p => (
                    <div key={p.id} onClick={() => handleEdit(p)} className={`p-3 cursor-pointer border-b last:border-0 rounded transition-colors ${selectedPage?.id === p.id ? 'bg-blue-50 text-blue-700 border-blue-200' : 'hover:bg-gray-50'}`}>
                        <div className="font-medium">{p.title}</div>
                        <small className="text-gray-400 block text-xs">/{p.slug}</small>
                    </div>
                ))}
            </div>
            
            <div className="md:col-span-2 bg-white rounded-lg shadow border p-6">
                {selectedPage ? (
                    <form onSubmit={handleSave} className="space-y-6">
                        <InputGroup label="Título da Página" name="title" value={selectedPage.title} onChange={e => setSelectedPage({...selectedPage, title: e.target.value})} />
                        <div>
                            <label className="block text-sm font-bold mb-2 text-gray-700">Conteúdo</label>
                            <ReactQuill theme="snow" value={selectedPage.content} onChange={(value) => setSelectedPage({...selectedPage, content: value})} modules={modules} className="h-64 mb-12" />
                        </div>
                        <div className="pt-4"><Button type="submit" isLoading={loading}>Salvar Alterações</Button></div>
                    </form>
                ) : (
                    <div className="text-center text-gray-400 py-20">Selecione uma página para editar.</div>
                )}
            </div>
        </div>
    );
};