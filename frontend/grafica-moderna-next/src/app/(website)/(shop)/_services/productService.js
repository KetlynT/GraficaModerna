'use server'

import { apiServer } from '@/app/lib/apiServer';

export const ProductService = {
  getAll: async (page = 1, pageSize = 8, search = '', sort = '', order = '') => {
    const params = new URLSearchParams({ page, pageSize });
    if (search) params.append('search', search);
    if (sort) params.append('sort', sort);
    if (order) params.append('order', order);

    return await apiServer(`/products?${params.toString()}`);
  },

  getById: async (id) => {
    return await apiServer(`/products/${id}`);
  }
};