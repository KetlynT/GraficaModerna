'use server'

import { cookies } from 'next/headers';

const API_PREFIX = process.env.API_SEGMENT_KEY;
const BASE_URL = process.env.INTERNAL_API_URL;
const API_URL = `${BASE_URL}/${API_PREFIX}`;

export async function apiServer(endpoint, method = 'GET', body = null, contentType = 'application/json') {
  const cookieStore = await cookies();
  const token = cookieStore.get('jwt')?.value;

  const headers = {};

  if (contentType) {
    headers['Content-Type'] = contentType;
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const options = {
    method,
    headers,
    cache: 'no-store',
  };

  if (body) {
    options.body = contentType === 'application/json' ? JSON.stringify(body) : body;
  }

  try {
    const response = await fetch(`${API_URL}${endpoint}`, options);

    if (endpoint.includes('/auth/login') || endpoint.includes('/auth/register') || endpoint.includes('/auth/refresh-token')) {
      const setCookieHeader = response.headers.getSetCookie();
      if (setCookieHeader && setCookieHeader.length > 0) {
        setCookieHeader.forEach(cookieString => {
          const [nameValue, ...opts] = cookieString.split(';');
          const [name, value] = nameValue.split('=');
          cookieStore.set(name, value, { 
            httpOnly: true, 
            secure: true, 
            sameSite: 'strict',
            path: '/' 
          });
        });
      }
    }

    if (response.status === 204) return null;

    const text = await response.text();
    
    if (!response.ok) {
      let errorMessage = text;
      try {
        const errorJson = JSON.parse(text);
        errorMessage = errorJson.message || JSON.stringify(errorJson);
      } catch {}
      
      throw new Error(errorMessage || `Erro ${response.status}`);
    }

    try {
      return JSON.parse(text);
    } catch {
      return text;
    }

  } catch (error) {
    console.error(`API Server Error [${method} ${endpoint}]:`, error.message);
    throw error;
  }
}