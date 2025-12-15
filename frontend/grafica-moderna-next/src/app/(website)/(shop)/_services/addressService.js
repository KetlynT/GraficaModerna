'use server'

import { apiServer } from '@/lib/apiServer';

export const AddressService = {
  getAll: async () => {
    return await apiServer('/addresses');
  },

  getById: async (id) => {
    return await apiServer(`/addresses/${id}`);
  },

  create: async (addressData) => {
    return await apiServer('/addresses', 'POST', addressData);
  },

  update: async (id, addressData) => {
    await apiServer(`/addresses/${id}`, 'PUT', addressData);
  },

  delete: async (id) => {
    await apiServer(`/addresses/${id}`, 'DELETE');
  },
};