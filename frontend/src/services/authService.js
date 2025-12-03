import api from './api';

export const AuthService = {
  login: async (email, password) => {
    try {
      const response = await api.post('/auth/login', { email, password });
      if (response.data) {
        localStorage.setItem('user', JSON.stringify(response.data));
      }
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // CORREÇÃO: Adicionado phoneNumber nos parâmetros e no payload
  register: async (fullName, email, password, phoneNumber) => {
    try {
      const response = await api.post('/auth/register', { 
        fullName, 
        email, 
        password, 
        phoneNumber 
      });
      
      if (response.data) {
        localStorage.setItem('user', JSON.stringify(response.data));
      }
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  logout: async () => {
    try {
        await api.post('/auth/logout');
    } finally {
        localStorage.removeItem('user');
        window.location.href = '/login';
    }
  },

  isAuthenticated: () => {
    const user = localStorage.getItem('user');
    return !!user;
  },

  getUser: () => JSON.parse(localStorage.getItem('user') || '{}')
};