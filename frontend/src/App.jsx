import React, { useEffect, Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';

import { MainLayout } from './components/layout/MainLayout';
import { AuthService } from './services/authService';
import { ContentService } from './services/contentService';
import ScrollToTop from './components/ScrollToTop';

// Lazy Loading das páginas para melhor performance inicial
const Home = lazy(() => import('./pages/Home').then(module => ({ default: module.Home })));
const Login = lazy(() => import('./pages/Login').then(module => ({ default: module.Login })));
const AdminDashboard = lazy(() => import('./pages/AdminDashboard').then(module => ({ default: module.AdminDashboard })));
const ProductDetails = lazy(() => import('./pages/ProductDetails').then(module => ({ default: module.ProductDetails })));
const GenericPage = lazy(() => import('./pages/GenericPage').then(module => ({ default: module.GenericPage })));
const Contact = lazy(() => import('./pages/Contact').then(module => ({ default: module.Contact })));

// Componente de Carregamento para as transições
const PageLoader = () => (
  <div className="min-h-screen flex items-center justify-center bg-gray-50">
    <div className="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
  </div>
);

const PrivateRoute = ({ children }) => {
  const isAuth = AuthService.isAuthenticated();
  return isAuth ? children : <Navigate to="/login" replace />;
};

const PublicOnlyRoute = ({ children }) => {
  const isAuth = AuthService.isAuthenticated();
  return isAuth ? <Navigate to="/painel-restrito-gerencial" replace /> : children;
};

function App() {
  
  // Efeito para carregar o FAVICON do banco de dados
  useEffect(() => {
    const updateFavicon = async () => {
      try {
        const settings = await ContentService.getSettings();
        if (settings && settings.site_logo) {
          let link = document.querySelector("link[rel~='icon']");
          if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.getElementsByTagName('head')[0].appendChild(link);
          }
          link.href = settings.site_logo;
        }
      } catch (error) {
        console.error("Erro ao atualizar favicon:", error);
      }
    };

    updateFavicon();
  }, []);

  return (
    <BrowserRouter>
      <ScrollToTop />
      
      <Toaster position="top-right" />
      
      <Suspense fallback={<PageLoader />}>
        <Routes>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Home />} />
            <Route path="/contato" element={<Contact />} />
            <Route path="/produto/:id" element={<ProductDetails />} />
            <Route path="/pagina/:slug" element={<GenericPage />} />
          </Route>

          {/* Rota secreta para login administrativo */}
          <Route 
            path="/portal-acesso-secreto-gm" 
            element={
              <PublicOnlyRoute>
                <Login />
              </PublicOnlyRoute>
            } 
          />
          
          {/* Redireciona tentativas de acesso ao /login padrão para a Home */}
          <Route path="/login" element={<Navigate to="/" replace />} />
          
          <Route 
            path="/painel-restrito-gerencial" 
            element={
              <PrivateRoute>
                <AdminDashboard />
              </PrivateRoute>
            } 
          />

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}

export default App;