import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { AuthService } from '../services/authService';
import { Button } from '../components/ui/Button';
import toast from 'react-hot-toast';
import { 
  LogOut, Edit, Trash2, Package, Settings, FileText, Layout
} from 'lucide-react';

export const AdminDashboard = () => {
  const [activeTab, setActiveTab] = useState('products'); 

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      <nav className="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-30 shadow-sm">
        <div className="flex items-center gap-2">
           <div className="bg-blue-600 p-2 rounded-lg text-white font-bold">GM</div>
           <h1 className="text-xl font-bold text-gray-800">Painel Administrativo</h1>
        </div>
        <button 
          onClick={AuthService.logout} 
          className="flex items-center gap-2 text-gray-500 hover:text-red-600 transition-colors font-medium text-sm"
        >
          <LogOut size={18} /> Sair
        </button>
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

// --- COMPONENTES AUXILIARES ---
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

const InputGroup = ({ label, name, value, onChange }) => (
    <div>
        <label className="block text-sm font-semibold text-gray-700 mb-1">{label}</label>
        <input 
            type="text" name={name} value={value || ''} onChange={onChange}
            className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors"
        />
    </div>
);

// --- ABA 1: PRODUTOS (Resumida para focar nas mudanças, lógica igual anterior) ---
const ProductsTab = () => {
  // ... (Manter código da aba de produtos original, garantindo que use ProductService atualizado)
  // Como o usuário pediu códigos inteiros, vou reimplementar a lógica básica para garantir funcionamento
  const [products, setProducts] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  
  // Form states
  const [name, setName] = useState('');
  const [desc, setDesc] = useState('');
  const [price, setPrice] = useState('');
  const [imageFile, setImageFile] = useState(null);

  useEffect(() => { loadProducts(); }, []);

  const loadProducts = async () => {
      try {
          const data = await ProductService.getAll();
          setProducts(data);
      } catch (e) { toast.error("Erro ao carregar"); }
  };

  const handleSave = async (e) => {
      e.preventDefault();
      try {
          let imageUrl = editingProduct?.imageUrl || '';
          if(imageFile) imageUrl = await ProductService.uploadImage(imageFile);

          const data = { name, description: desc, price: parseFloat(price), imageUrl };
          
          if(editingProduct) await ProductService.update(editingProduct.id, data);
          else await ProductService.create(data);

          setIsModalOpen(false);
          setImageFile(null);
          loadProducts();
          toast.success("Salvo com sucesso!");
      } catch (e) { toast.error("Erro ao salvar"); }
  };

  const handleDelete = async (id) => {
      if(!window.confirm("Excluir produto?")) return;
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
      setIsModalOpen(true);
  };

  return (
      <div>
          <div className="flex justify-between mb-4">
              <h2 className="text-xl font-bold">Catálogo</h2>
              <Button onClick={() => openModal()}>Novo Produto</Button>
          </div>
          <div className="bg-white rounded shadow overflow-hidden">
              <table className="w-full text-left">
                  <thead className="bg-gray-50 border-b">
                      <tr><th className="p-4">Nome</th><th className="p-4">Preço</th><th className="p-4 text-right">Ações</th></tr>
                  </thead>
                  <tbody>
                      {products.map(p => (
                          <tr key={p.id} className="border-b last:border-0">
                              <td className="p-4">{p.name}</td>
                              <td className="p-4">R$ {p.price}</td>
                              <td className="p-4 text-right gap-2 flex justify-end">
                                  <button onClick={() => openModal(p)} className="text-blue-600 p-2"><Edit size={16}/></button>
                                  <button onClick={() => handleDelete(p.id)} className="text-red-600 p-2"><Trash2 size={16}/></button>
                              </td>
                          </tr>
                      ))}
                  </tbody>
              </table>
          </div>

          {isModalOpen && (
              <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                  <form onSubmit={handleSave} className="bg-white p-6 rounded-lg w-96 space-y-4">
                      <h3 className="font-bold text-lg">{editingProduct ? 'Editar' : 'Novo'}</h3>
                      <input className="w-full border p-2 rounded" placeholder="Nome" value={name} onChange={e=>setName(e.target.value)} required />
                      <textarea className="w-full border p-2 rounded" placeholder="Descrição" value={desc} onChange={e=>setDesc(e.target.value)} />
                      <input className="w-full border p-2 rounded" type="number" step="0.01" placeholder="Preço" value={price} onChange={e=>setPrice(e.target.value)} required />
                      <input type="file" onChange={e=>setImageFile(e.target.files[0])} className="text-sm" />
                      <div className="flex justify-end gap-2">
                          <Button type="button" variant="ghost" onClick={()=>setIsModalOpen(false)}>Cancelar</Button>
                          <Button type="submit">Salvar</Button>
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
        let updatedData = { ...formData };
        if (heroImageFile) {
            updatedData.hero_bg_url = await ProductService.uploadImage(heroImageFile);
        }
        await ContentService.saveSettings(updatedData);
        toast.success("Configurações atualizadas!");
    };

    return (
        <form onSubmit={handleSave} className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 max-w-4xl mx-auto space-y-8">
            {/* Seção Hero */}
            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><Layout className="text-blue-600"/> Home Page (Topo)</h2>
                <div className="grid gap-4">
                    <div className="border border-dashed p-4 rounded text-center">
                        <label className="block text-sm font-bold mb-2">Imagem de Fundo</label>
                        <input type="file" onChange={e => setHeroImageFile(e.target.files[0])} className="mx-auto text-sm" />
                    </div>
                    <InputGroup label="Badge (Texto Pequeno)" name="hero_badge" value={formData.hero_badge} onChange={handleChange} />
                    <InputGroup label="Título Principal" name="hero_title" value={formData.hero_title} onChange={handleChange} />
                    <InputGroup label="Subtítulo" name="hero_subtitle" value={formData.hero_subtitle} onChange={handleChange} />
                </div>
            </div>

            {/* Seção Catálogo (NOVA) */}
            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><Package className="text-blue-600"/> Home Page (Catálogo)</h2>
                <div className="grid md:grid-cols-2 gap-4">
                    <InputGroup label="Título da Seção" name="home_products_title" value={formData.home_products_title} onChange={handleChange} />
                    <InputGroup label="Subtítulo da Seção" name="home_products_subtitle" value={formData.home_products_subtitle} onChange={handleChange} />
                </div>
            </div>

            {/* Seção Contato */}
            <div>
                <h2 className="text-xl font-bold mb-4 flex items-center gap-2 pb-2 border-b"><Settings className="text-blue-600"/> Contato</h2>
                <div className="grid md:grid-cols-2 gap-4">
                    <InputGroup label="WhatsApp (Apenas nº)" name="whatsapp_number" value={formData.whatsapp_number} onChange={handleChange} />
                    <InputGroup label="WhatsApp (Visível)" name="whatsapp_display" value={formData.whatsapp_display} onChange={handleChange} />
                    <InputGroup label="E-mail" name="contact_email" value={formData.contact_email} onChange={handleChange} />
                    <InputGroup label="Endereço" name="address" value={formData.address} onChange={handleChange} />
                </div>
            </div>

            <Button type="submit" className="w-full"><Hb size={18}/> Salvar Tudo</Button>
        </form>
    );
};

// --- ABA 3: PÁGINAS DE TEXTO ---
const PagesTab = () => {
    const [pages, setPages] = useState([]);
    const [selectedPage, setSelectedPage] = useState(null);

    useEffect(() => { ContentService.getAllPages().then(setPages); }, []);

    const handleEdit = async (page) => {
        const fullPage = await ContentService.getPage(page.slug);
        setSelectedPage(fullPage);
    };

    const handleSave = async (e) => {
        e.preventDefault();
        await ContentService.updatePage(selectedPage.id, selectedPage);
        toast.success("Página salva!");
        // Refresh list if titles changed
        ContentService.getAllPages().then(setPages);
    };

    return (
        <div className="grid md:grid-cols-3 gap-8">
            <div className="bg-white rounded-lg shadow border p-4">
                <h3 className="font-bold border-b pb-2 mb-2">Páginas</h3>
                {pages.map(p => (
                    <div key={p.id} onClick={() => handleEdit(p)} className="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-0">
                        {p.title} <small className="text-gray-400 block">{p.slug}</small>
                    </div>
                ))}
            </div>
            
            <div className="md:col-span-2 bg-white rounded-lg shadow border p-6">
                {selectedPage ? (
                    <form onSubmit={handleSave} className="space-y-4">
                        <h3 className="font-bold text-lg border-b pb-2">Editando: {selectedPage.slug}</h3>
                        <InputGroup label="Título da Página" name="title" value={selectedPage.title} onChange={e => setSelectedPage({...selectedPage, title: e.target.value})} />
                        <div>
                            <label className="block text-sm font-bold mb-1">Conteúdo (HTML Seguro)</label>
                            <textarea 
                                className="w-full border p-2 rounded h-64 font-mono text-sm" 
                                value={selectedPage.content}
                                onChange={e => setSelectedPage({...selectedPage, content: e.target.value})}
                            />
                        </div>
                        <Button type="submit">Salvar Alterações</Button>
                    </form>
                ) : (
                    <div className="text-center text-gray-400 py-10">Selecione uma página para editar</div>
                )}
            </div>
        </div>
    );
};