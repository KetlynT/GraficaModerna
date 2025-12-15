'use server'

import { apiServer } from '@/lib/apiServer';

export const OrderService = {
  checkout: async (checkoutData) => {
    return await apiServer('/orders', 'POST', checkoutData);
  },

  getMyOrders: async (page = 1, pageSize = 10) => {
    return await apiServer(`/orders?page=${page}&pageSize=${pageSize}`);
  },

  requestRefund: async (orderId, refundData) => {
    return await apiServer(`/orders/${orderId}/request-refund`, 'POST', refundData);
  },
};