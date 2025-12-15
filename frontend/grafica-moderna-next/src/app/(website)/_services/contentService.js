'use server'

import { apiServer } from '@/lib/apiServer';

export const ContentService = {
  getPage: async (slug) => {
    try {
      return await apiServer(`/content/pages/${slug}`);
    } catch (error) {
      return null;
    }
  },

  getAllPages: async () => {
    return await apiServer('/content/pages');
  },

  getSettings: async () => {
    try {
      return await apiServer('/content/settings');
    } catch (error) {
      console.error("Erro ao carregar configurações", error);
      return {}; 
    }
  },
};