'use server'

import { apiServer } from '@/app/lib/apiServer';

export async function getPage(slug) {
  try {
    return await apiServer(`/content/pages/${slug}`);
  } catch (error) {
    return null;
  }
}

export async function getAllPages() {
  return await apiServer('/content/pages');
}

export async function getSettings() {
  try {
    return await apiServer('/content/settings');
  } catch (error) {
    console.error("Erro ao carregar configurações", error);
    return {}; 
  }
}