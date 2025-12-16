export const getProxyImageUrl = (url) => {
  if (!url) return 'https://placehold.co/600x400?text=Sem+Imagem';
  if (url.startsWith('http')) return url;
  
  return url;
};

export const isVideo = (url) => {
  if (!url) return false;
  const cleanUrl = url.split('?')[0]; 
  const extension = cleanUrl.split('.').pop().toLowerCase();
  return ['mp4', 'webm', 'mov', 'ogg'].includes(extension);
};