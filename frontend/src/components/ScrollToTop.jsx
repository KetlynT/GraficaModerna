import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

const ScrollToTop = () => {
  const { pathname } = useLocation();

  useEffect(() => {
    // Rola a janela para o topo (x=0, y=0) sempre que o caminho (pathname) mudar
    window.scrollTo(0, 0);
  }, [pathname]);

  return null; // Este componente não renderiza nada visualmente
};

export default ScrollToTop;