import axios from 'axios';
import toast from 'react-hot-toast';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://localhost:7255/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

const retryRequest = async (fn, retries = 3, delay = 1000) => {
  try {
    return await fn();
  } catch (err) {
    if (retries <= 1) throw err;
    await new Promise(resolve => setTimeout(resolve, delay));
    return retryRequest(fn, retries - 1, delay * 2);
  }
};

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (originalRequest.url && originalRequest.url.includes('/auth/check-auth')) {
      return Promise.reject(error);
    }

    if (error.response?.status === 401 && !originalRequest._retry) {
      
      if (originalRequest.url.includes('/login') || originalRequest.url.includes('/refresh-token')) {
        return Promise.reject(error);
      }

      if (isRefreshing) {
        return new Promise(function(resolve, reject) {
          failedQueue.push({ resolve, reject });
        })
        .then(() => {
          return api(originalRequest);
        })
        .catch(err => Promise.reject(err));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        await retryRequest(() => api.post('/auth/refresh-token'), 3, 1000);

        processQueue(null);
        return api(originalRequest);

      } catch (refreshError) {
        processQueue(refreshError, null);
        
        if (typeof window !== 'undefined') {
           window.location.href = '/login';
        }
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'];
      const message = retryAfter 
        ? `Muitas solicitações. Por favor, aguarde ${retryAfter} segundos.` 
        : 'Muitas tentativas consecutivas. Aguarde um momento antes de tentar novamente.';
      toast.error(message);
      return Promise.reject(error);
    }

    if (!error.response) {
      console.error('Erro de conexão:', error);
    }

    return Promise.reject(error);
  }
);

export default api;