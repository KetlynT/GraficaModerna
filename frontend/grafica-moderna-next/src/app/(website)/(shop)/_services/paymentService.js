'use server'

import { apiServer } from '@/lib/apiServer';

export const PaymentService = {
  createCheckoutSession: async (orderId) => {
    return await apiServer(`/payments/checkout-session/${orderId}`, 'POST');
  },
  
  getPaymentStatus: async (orderId) => {
    return await apiServer(`/payments/status/${orderId}`);
  }
};