'use server'

import { apiServer } from '@/app/lib/apiServer';

export const ShippingService = {
  calculate: async (cep, items) => {
    const payload = {
      destinationCep: cep,
      items: items.map(item => ({
        productId: item.productId,
        quantity: item.quantity
      }))
    };
    
    return await apiServer('/shipping/calculate', 'POST', payload);
  },

  calculateForProduct: async (productId, cep) => {
    const cleanCep = cep.replace(/\D/g, '');
    return await apiServer(`/shipping/product/${productId}/${cleanCep}`);
  }
};