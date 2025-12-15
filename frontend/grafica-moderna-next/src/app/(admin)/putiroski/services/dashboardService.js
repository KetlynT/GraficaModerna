import api from '@/app/(website)/services/api';

export const DashboardService = {
  // --- Dashboard & Stats ---
  getStats: async () => {
    const response = await api.get('/admin/dashboard/stats');
    return response.data;
  },

  // --- Orders ---
  getOrders: async (page = 1, pageSize = 10) => {
    const response = await api.get(`/admin/orders?page=${page}&pageSize=${pageSize}`);
    return response.data;
  },

  updateOrderStatus: async (id, statusData) => {
    const response = await api.patch(`/admin/orders/${id}/status`, statusData);
    return response.data;
  },

  // --- Products (Admin Management) ---
  // Nota: Usa endpoint específico de admin que lista inclusive inativos
  getProducts: async (page = 1, pageSize = 8, search = '', sort = '', order = '') => {
    const params = new URLSearchParams({ page, pageSize });
    if (search) params.append('search', search);
    if (sort) params.append('sort', sort);
    if (order) params.append('order', order);

    const response = await api.get(`/admin/products?${params.toString()}`);
    return response.data;
  },

  getProductById: async (id) => {
    const response = await api.get(`/admin/products/${id}`);
    return response.data;
  },

  createProduct: async (productData) => {
    const response = await api.post('/admin/products', productData);
    return response.data;
  },

  updateProduct: async (id, productData) => {
    await api.put(`/admin/products/${id}`, productData);
  },

  deleteProduct: async (id) => {
    await api.delete(`/admin/products/${id}`);
  },

  // --- Coupons (Admin Management) ---
  getCoupons: async () => {
    const response = await api.get('/admin/coupons');
    return response.data;
  },

  createCoupon: async (couponData) => {
    const response = await api.post('/admin/coupons', couponData);
    return response.data;
  },

  deleteCoupon: async (id) => {
    await api.delete(`/admin/coupons/${id}`);
  },

  // --- Content & Settings ---
  createPage: async (data) => {
    const response = await api.post('/admin/content/pages', data);
    return response.data;
  },

  updatePage: async (slug, data) => {
    await api.put(`/admin/content/pages/${slug}`, data);
  },
  
  saveSettings: async (settingsDict) => {
    await api.post('/admin/content/settings', settingsDict);
  },

  // --- Email Templates ---
  getEmailTemplates: async () => {
    const response = await api.get('/admin/email-templates');
    return response.data;
  },

  getEmailTemplateById: async (id) => {
    const response = await api.get(`/admin/email-templates/${id}`);
    return response.data;
  },

  updateEmailTemplate: async (id, data) => {
    const response = await api.put(`/admin/email-templates/${id}`, data);
    return response.data;
  },

  // --- Upload ---
  uploadImage: async (file) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await api.post('/admin/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data.url;
  }
};