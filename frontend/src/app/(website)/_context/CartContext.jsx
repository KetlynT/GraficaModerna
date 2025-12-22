'use client'

import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { useAuth } from '@/app/_context/AuthContext';
import { CartService } from '@/app/(website)/(shop)/_services/cartService';

const CartContext = createContext({});

export const CartProvider = ({ children }) => {
  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(false);
  const { user } = useAuth();

  const fetchCart = useCallback(async () => {
    if (!user || user.role === 'Admin') {
        setCart({ items: [], totalAmount: 0 });
        return;
    }
    
    try {
      setLoading(true);
      const data = await CartService.getCart();
      setCart(data || { items: [], totalAmount: 0 });
    } catch (error) {
      console.error('Erro ao buscar carrinho:', error);
    } finally {
      setLoading(false);
    }
  }, [user]);

  useEffect(() => {
    fetchCart();
  }, [fetchCart]);

  const syncGuestCart = async () => {
      await fetchCart();
  };

  const addToCart = async (product, quantity) => {
    if (!user) {
        toast.error("Faça login para comprar.");
        router.push('/login')
        return;
    }

    try {
      await CartService.addItem(product.id, quantity);
      toast.success('Produto adicionado!');
      await fetchCart();
    } catch (error) {
      const msg = error.message || 'Erro ao adicionar produto';
      toast.error(msg);
    }
  };

  const updateQuantity = async (itemId, quantity) => {
    try {
      await CartService.updateQuantity(itemId, quantity);
      await fetchCart();
    } catch (error) {
      const msg = error.message || 'Erro ao atualizar quantidade';
      toast.error(msg);
    }
  };

  const removeFromCart = async (itemId) => {
    try {
      await CartService.removeItem(itemId);
      toast.success('Item removido!');
      await fetchCart();
    } catch (error) {
      const msg = error.message || 'Erro ao remover item';
      toast.error(msg);
    }
  };

  const clearCart = async () => {
    try {
      await CartService.clearCart();
      setCart({ items: [], totalAmount: 0 });
      toast.success('Carrinho limpo!');
    } catch (error) {
      toast.error('Erro ao limpar carrinho');
    }
  };

  const cartItems = cart?.items || [];
  const cartCount = cartItems.reduce((acc, item) => acc + item.quantity, 0);

  return (
    <CartContext.Provider value={{ 
        cart, 
        cartItems,
        cartCount,
        loading, 
        addToCart, 
        updateQuantity, 
        removeFromCart,
        removeItem: removeFromCart,
        clearCart,
        refreshCart: fetchCart,
        syncGuestCart
    }}>
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => useContext(CartContext);
export default CartContext;