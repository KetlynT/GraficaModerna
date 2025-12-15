'use server'

import { apiServer } from '@/lib/apiServer';
import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

const authService = {
  login: async (credentials, isAdmin = false) => {
    const url = isAdmin ? '/admin/auth/login' : '/auth/login';
    const data = await apiServer(url, 'POST', credentials);
    return data;
  },

  register: async (data) => {
    return await apiServer('/auth/register', 'POST', data);
  },

  logout: async () => {
    try {
      await apiServer('/auth/logout', 'POST');
      const cookieStore = await cookies();
      cookieStore.delete('jwt');
      cookieStore.delete('refreshToken');
    } catch (e) {
      console.error(e);
    }
  },

  getProfile: async () => {
    return await apiServer('/auth/profile');
  },

  updateProfile: async (data) => {
    return await apiServer('/auth/profile', 'PUT', data);
  },

  checkAuth: async () => {
    try {
      return await apiServer('/auth/check-auth');
    } catch (error) {
      return { isAuthenticated: false, role: null };
    }
  },

  isAuthenticated: async () => {
      try {
          const data = await apiServer('/auth/check-auth');
          return data?.isAuthenticated || false;
      } catch {
          return false;
      }
  },

  confirmEmail: async (userId, token) => {
    return await apiServer('/auth/confirm-email', 'POST', { userId, token });
  },

  forgotPassword: async (email) => {
    return await apiServer('/auth/forgot-password', 'POST', { email });
  },

  resetPassword: async (data) => {
    return await apiServer('/auth/reset-password', 'POST', data);
  },
};

export default authService;