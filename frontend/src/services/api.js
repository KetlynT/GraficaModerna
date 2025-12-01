import axios from 'axios';

// Usa variável de ambiente do Vite (VITE_API_URL) ou fallback para localhost
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:5150/api', 
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor de Requisição: Injeta o Token automaticamente
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor de Resposta: Logout automático se token for inválido (401)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Evita loop infinito se a falha for no próprio login
      if (!window.location.pathname.includes('/login')) {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;