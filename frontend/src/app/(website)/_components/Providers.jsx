'use client';

import { AuthProvider } from '@/app/_context/AuthContext';
import { CartProvider } from '@/app/(website)/_context/CartContext';
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