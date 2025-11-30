import api from './api';

export const ProductService = {
  // Retorna Promise com array de produtos
  getAll: async () => {
    try {
      const response = await api.get('/products');
      return response.data;
    } catch (error) {
      console.error("Erro ao buscar produtos:", error);
      throw error;
    }
  },

  create: async (productData) => {
    const response = await api.post('/products', productData);
    return response.data;
  }
};