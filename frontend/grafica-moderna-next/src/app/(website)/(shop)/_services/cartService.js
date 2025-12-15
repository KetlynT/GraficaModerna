'use server'

import { apiServer } from '@/app/lib/apiServer'; 
import { revalidatePath } from 'next/cache';

export const CartService = {
  getCart: async () => {
    return await apiServer('/cart');
  },

  addItem: async (productId, quantity) => {
    await apiServer('/cart/items', 'POST', { productId, quantity });
    revalidatePath('/carrinho');
  },

  updateQuantity: async (itemId, quantity) => {
    await apiServer(`/cart/items/${itemId}`, 'PATCH', { quantity });
    revalidatePath('/carrinho');
  },

  removeItem: async (itemId) => {
    await apiServer(`/cart/items/${itemId}`, 'DELETE');
    revalidatePath('/carrinho');
  },

  clearCart: async () => {
    await apiServer('/cart', 'DELETE');
    revalidatePath('/carrinho');
  },
};