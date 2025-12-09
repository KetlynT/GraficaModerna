import React, { useEffect, useState } from 'react';
import { ProductService } from '../services/productService';
import { ContentService } from '../services/contentService';
import { CartService } from '../services/cartService';
import { DashboardService } from '../services/dashboardService';
import { CouponService } from '../services/couponService';
import { Button } from '../components/ui/Button';
import toast from 'react-hot-toast';
import { 
  LogOut, Edit, Trash2, Package, Settings, FileText, 
  Box, Truck, BarChart2, AlertTriangle, DollarSign, 
  ShoppingBag, Tag, Search, Eye, X, RefreshCcw, ArrowUp, ArrowDown, ArrowUpDown 
} from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { useAuth } from '../context/AuthContext';

export const AdminDashboard = () => {
  const [logoUrl, setLogoUrl] = useState('');
  const { user, loading: authLoading, logout } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview'); 
  const [isAuthorized, setIsAuthorized] = useState(false);
  
  useEffect(() => {
    if (authLoading) return;

    if (!user || user.role !== 'Admin') {
      toast.error("Acesso não autorizado.");
      navigate('/');
      return;
    }

    setIsAuthorized(true);

    const loadSettings = async () => {
          try {
            const settings = await ContentService.getSettings();
            if (settings && settings.site_logo) {
                setLogoUrl(settings.site_logo);
            }
          } catch (error) {
            console.error("Erro ao carregar configurações", error);
          }
        };
    loadSettings();
  }, [user, authLoading, navigate]);

  if (authLoading || !isAuthorized) {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
            <div className="animate-pulse flex flex-col items-center">
                <div className="h-12 w-12 bg-gray-300 rounded-full mb-4"></div>
                <div className="h-4 w-48 bg-gray-300 rounded"></div>
            </div>
        </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      <nav className="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-30 shadow-sm">
        <div className="flex items-center gap-2">
            {logoUrl ? (
                <img src={logoUrl} alt="Logo" className="h-10 w-auto object-contain" />
            ) : (
                <div className="h-10 w-24 flex items-center justify-center text-xs font-medium text-gray-600 border rounded bg-gray-50">
                    LOGO
                </div>
            )}
           <h1 className="text-xl font-bold text-gray-800 ml-2">Painel Restrito</h1>
        </div>
        <div className="flex items-center gap-4">
            <a href="/" className="text-sm text-blue-600 hover:underline font-medium" target="_blank" rel="noopener noreferrer">Ver Loja</a>
            <button 
                onClick={logout} 
                className="flex items-center gap-2 text-gray-500 hover:text-red-600 transition-colors font-medium text-sm bg-gray-100 hover:bg-red-50 px-4 py-2 rounded-lg"
            >
                <LogOut size={18} /> Sair
            </button>
        </div>
      </nav>

      <div className="max-w-7xl mx-auto p-4 lg:p-8">
        <div className="flex gap-2 mb-8 border-b border-gray-200 overflow-x-auto pb-1 no-scrollbar">
            <TabButton active={activeTab === 'overview'} onClick={() => setActiveTab('overview')} icon={<BarChart2 size={18} />}>
                Visão Geral
            </TabButton>
            <TabButton active={activeTab === 'orders'} onClick={() => setActiveTab('orders')} icon={<Truck size={18} />}>
                Pedidos
            </TabButton>
            <TabButton active={activeTab === 'products'} onClick={() => setActiveTab('products')} icon={<Package size={18} />}>
                Produtos
            </TabButton>
            <TabButton active={activeTab === 'coupons'} onClick={() => setActiveTab('coupons')} icon={<Tag size={18} />}>
                Cupons
            </TabButton>
            <TabButton active={activeTab === 'settings'} onClick={() => setActiveTab('settings')} icon={<Settings size={18} />}>
                Configurações
            </TabButton>
            <TabButton active={activeTab === 'pages'} onClick={() => setActiveTab('pages')} icon={<FileText size={18} />}>
                Páginas
            </TabButton>
        </div>

        <div className="animate-in fade-in duration-300">
            {activeTab === 'overview' && <OverviewTab />}
            {activeTab === 'products' && <ProductsTab />}
            {activeTab === 'orders' && <OrdersTab />}
            {activeTab === 'coupons' && <CouponsTab />}
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

const OrderStatusBadge = ({ status }) => {
    const styles = {
        'Pendente': 'bg-yellow-100 text-yellow-800',
        'Pago': 'bg-indigo-100 text-indigo-800',
        'Enviado': 'bg-blue-100 text-blue-800',
        'Entregue': 'bg-green-100 text-green-800',
        'Cancelado': 'bg-red-100 text-red-800',
        'Reembolso Solicitado': 'bg-purple-100 text-purple-800',
        'Aguardando Devolução': 'bg-orange-100 text-orange-800',
        'Reembolsado': 'bg-gray-800 text-white'
    };
    return (
        <span className={`px-2 py-1 rounded text-xs font-bold ${styles[status] || 'bg-gray-100'}`}>
            {status}
        </span>
    );
};

