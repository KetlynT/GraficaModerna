import api from './api';

const TOKEN_KEY = 'access_token';

const authService = {
  // Login: backend should return { token, role, ... }
  login: async (credentials) => {
    const response = await api.post('/auth/login', credentials);
    const data = response.data || {};
    if (data.token) {
      try { localStorage.setItem(TOKEN_KEY, data.token); } catch {}
    }
    return data;
  },

  // Registro: backend returns token too
  register: async (data) => {
    const response = await api.post('/auth/register', data);
    const result = response.data || {};
    if (result.token) {
      try { localStorage.setItem(TOKEN_KEY, result.token); } catch {}
    }
    return result;
  },

  // Logout: notify backend to blacklist token and clear local storage
  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch (e) {
      // ignore backend error
    }
    try { localStorage.removeItem(TOKEN_KEY); } catch {}
  },

  // Perfil: Busca dados do utilizador logado.
  getProfile: async () => {
    const response = await api.get('/auth/profile');
    return response.data;
  },

  updateProfile: async (data) => {
    const response = await api.put('/auth/profile', data);
    return response.data;
  },

  checkAuth: async () => {
    const token = (() => { try { return localStorage.getItem(TOKEN_KEY); } catch { return null; } })();
    if (!token) return { isAuthenticated: false, role: null };

    try {
      const response = await api.get('/auth/check-auth');
      return response.data; 
    } catch (error) {
      try { localStorage.removeItem(TOKEN_KEY); } catch {}
      return { isAuthenticated: false, role: null };
    }
  },

  // CORREÇÃO: Método adicionado para o CartContext usar
  isAuthenticated: () => {
    try {
      return !!localStorage.getItem(TOKEN_KEY);
    } catch {
      return false;
    }
  }
};

export default authService;