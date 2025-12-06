import axios from 'axios';

// CORREÇÃO: Porta alterada de 5000 para 7255 (HTTPS) conforme seu launchSettings.json
// Se tiver problemas de certificado, tente usar 'http://localhost:5150/api'
const api = axios.create({
  baseURL: 'https://localhost:7255/api', 
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  try {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers = config.headers || {};
      config.headers['Authorization'] = `Bearer ${token}`;
    }
  } catch (e) {
    // ignore
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      console.warn('Sessão expirada ou não autenticada.');
    }
    return Promise.reject(error);
  }
);

export default api;