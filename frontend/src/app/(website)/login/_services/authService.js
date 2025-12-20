'use server'

import { apiServer } from '@/app/lib/apiServer';
import { cookies } from 'next/headers';

export async function login(credentials, isAdmin = false) {
  const url = isAdmin ? '/admin/auth/login' : '/auth/login';
  const data = await apiServer(url, 'POST', credentials);
  return data;
}

export async function register(data) {
  return await apiServer('/auth/register', 'POST', data);
}

export async function logout() {
  try {
    await apiServer('/auth/logout', 'POST');
    const cookieStore = await cookies();
    cookieStore.delete('jwt');
    cookieStore.delete('refreshToken');
  } catch (e) {
    console.error(e);
  }
}

export async function getProfile() {
  return await apiServer('/auth/profile');
}

export async function updateProfile(data) {
  return await apiServer('/auth/profile', 'PUT', data);
}

export async function checkAuth() {
  try {
    return await apiServer('/auth/check-auth');
  } catch (error) {
    return { isAuthenticated: false, role: null };
  }
}

export async function isAuthenticated() {
    try {
        const data = await apiServer('/auth/check-auth');
        return data?.isAuthenticated || false;
    } catch {
        return false;
    }
}

export async function confirmEmail(userId, token) {
  return await apiServer('/auth/confirm-email', 'POST', { userId, token });
}

export async function forgotPassword(email) {
  return await apiServer('/auth/forgot-password', 'POST', { email });
}

export async function resetPassword(data) {
  return await apiServer('/auth/reset-password', 'POST', data);
}