import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:5150/api', // Confirme se sua API roda nesta porta
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor de Requisição (Já usado no ProductService, mas bom garantir o token aqui se quiser centralizar)
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor de Resposta (Segurança: Auto Logout em caso de token inválido)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Token expirado ou inválido
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;