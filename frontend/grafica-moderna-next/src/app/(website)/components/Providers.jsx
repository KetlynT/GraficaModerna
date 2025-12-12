'use client';

import { AuthProvider } from '@/app/(website)/context/AuthContext';
import { CartProvider } from '@/app/(website)/context/CartContext';
import { Toaster } from 'react-hot-toast';

export function Providers({ children }) {
  return (
    <AuthProvider>
      <CartProvider>
        {children}
        <Toaster position="top-right" containerStyle={{ top: 80 }} />
      </CartProvider>
    </AuthProvider>
  );
}