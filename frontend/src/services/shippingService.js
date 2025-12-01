import api from './api';

export const ShippingService = {
  // Calcula frete para um único produto (usado na página de detalhes)
  calculateForProduct: async (productId, cep) => {
    try {
      // Remove caracteres não numéricos do CEP para evitar erros
      const cleanCep = cep.replace(/\D/g, '');
      const response = await api.get(`/shipping/product/${productId}/${cleanCep}`);
      return response.data;
    } catch (error) {
      console.error("Erro ao calcular frete:", error);
      throw error;
    }
  },

  // Calcula frete para múltiplos itens (para uso futuro no Carrinho)
  calculate: async (destinationCep, items) => {
    try {
      const cleanCep = destinationCep.replace(/\D/g, '');
      const response = await api.post('/shipping/calculate', { 
        destinationCep: cleanCep, 
        items 
      });
      return response.data;
    } catch (error) {
      console.error("Erro ao calcular frete:", error);
      throw error;
    }
  }
};