import api from './api';

export const ContentService = {
  getPage: async (slug) => {
    try {
      const response = await api.get(`/content/pages/${slug}`);
      return response.data;
    } catch (error) {
      return null;
    }
  },

  getSettings: async () => {
    try {
      const response = await api.get('/content/settings');
      return response.data;
    } catch (error) {
      console.error("Erro ao carregar configurações", error);
      return {};
    }
  }
};