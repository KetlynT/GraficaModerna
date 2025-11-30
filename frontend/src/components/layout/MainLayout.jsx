import React from 'react';
import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Footer } from './Footer';

export const MainLayout = () => {
  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      {/* Componente Header Isolado */}
      <Header />

      {/* Conteúdo Dinâmico das Páginas */}
      <main className="flex-grow">
        <Outlet />
      </main>

      {/* Componente Footer Isolado */}
      <Footer />
    </div>
  );
};