import api from '@/app/(website)/services/api';

export const DashboardService = {
  getStats: async () => {
    const response = await api.get('/dashboard/stats');
    return response.data;
  },

  getOrders: async (page = 1, pageSize = 10) => {
    const response = await api.get(`/admin/orders?page=${page}&pageSize=${pageSize}`);
    return response.data;
  },

  updateOrderStatus: async (id, statusData) => {
    const response = await api.patch(`/admin/orders/${id}/status`, statusData);
    return response.data;
  },

  updatePage: async (slug, data) => {
    await api.put(`/admin/content/pages/${slug}`, data);
  },

  createPage: async (data) => {
    const response = await api.post('/admin/content', data);
    return response.data;
  },
  
  saveSettings: async (settingsDict) => {
    await api.post('/admin/content/settings', settingsDict);
  },

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

  create: async (productData) => {
    const response = await api.post('/admin/products', productData);
    return response.data;
  },

  update: async (id, productData) => {
    await api.put(`/admin/products/${id}`, productData);
  },

  delete: async (id) => {
    await api.delete(`/admin/products/${id}`);
  },

  uploadImage: async (file) => {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await api.post('/admin/upload', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    });
    return response.data.url || response.data; 
  },
    getAll: async () => {
    const response = await api.get('/admin/coupons');
    return response.data;
  },

  create: async (couponData) => {
    const response = await api.post('/admin/coupons', couponData);
    return response.data;
  },

  delete: async (id) => {
    await api.delete(`/admin/coupons/${id}`);
  }
};