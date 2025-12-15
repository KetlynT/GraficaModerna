'use server'

import { apiServer } from '@/lib/apiServer';

export const DashboardService = {
  // --- Dashboard & Stats ---
  getStats: async () => {
    return await apiServer('/admin/dashboard/stats');
  },

  // --- Orders ---
  getOrders: async (page = 1, pageSize = 10) => {
    return await apiServer(`/admin/orders?page=${page}&pageSize=${pageSize}`);
  },

  updateOrderStatus: async (id, statusData) => {
    return await apiServer(`/admin/orders/${id}/status`, 'PATCH', statusData);
  },

  // --- Products (Admin Management) ---
  getProducts: async (page = 1, pageSize = 8, search = '', sort = '', order = '') => {
    const params = new URLSearchParams({ page, pageSize });
    if (search) params.append('search', search);
    if (sort) params.append('sort', sort);
    if (order) params.append('order', order);

    return await apiServer(`/admin/products?${params.toString()}`);
  },

  getProductById: async (id) => {
    return await apiServer(`/admin/products/${id}`);
  },

  createProduct: async (productData) => {
    return await apiServer('/admin/products', 'POST', productData);
  },

  updateProduct: async (id, productData) => {
    await apiServer(`/admin/products/${id}`, 'PUT', productData);
  },

  deleteProduct: async (id) => {
    await apiServer(`/admin/products/${id}`, 'DELETE');
  },

  // --- Coupons (Admin Management) ---
  getCoupons: async () => {
    return await apiServer('/admin/coupons');
  },

  createCoupon: async (couponData) => {
    return await apiServer('/admin/coupons', 'POST', couponData);
  },

  deleteCoupon: async (id) => {
    await apiServer(`/admin/coupons/${id}`, 'DELETE');
  },

  // --- Content & Settings ---
  getPage: async (slug) => {
    try {
      return await apiServer(`/admin/content/pages/${slug}`);
    } catch (error) {
      return null;
    }
  },

  getAllPages: async () => {
    return await apiServer('/admin/content/pages');
  },

  createPage: async (data) => {
    return await apiServer('/admin/content/pages', 'POST', data);
  },

  updatePage: async (slug, data) => {
    await apiServer(`/admin/content/pages/${slug}`, 'PUT', data);
  },
  
  saveSettings: async (settingsDict) => {
    await apiServer('/admin/content/settings', 'POST', settingsDict);
  },

  // --- Email Templates ---
  getEmailTemplates: async () => {
    return await apiServer('/admin/email-templates');
  },

  getEmailTemplateById: async (id) => {
    return await apiServer(`/admin/email-templates/${id}`);
  },

  updateEmailTemplate: async (id, data) => {
    return await apiServer(`/admin/email-templates/${id}`, 'PUT', data);
  },

  // --- Upload ---
  uploadImage: async (formData) => {
    return await apiServer('/admin/upload', 'POST', formData, null); 
  }
};