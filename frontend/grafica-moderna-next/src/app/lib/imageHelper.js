export const getProxyImageUrl = (originalUrl) => {
  if (!originalUrl) return '/placeholder.png';

  if (originalUrl.startsWith('/') || originalUrl.startsWith('blob:')) {
    return originalUrl;
  }

  try {
    const urlObj = new URL(originalUrl);
    const path = urlObj.pathname;
    
    return `/api/image-proxy?path=${encodeURIComponent(path)}`;
  } catch (e) {
    return originalUrl;
  }
};