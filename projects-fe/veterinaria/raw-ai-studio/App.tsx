
'use client';

import React, { useState, useEffect } from 'react';
import RootLayout from './app/layout';
import HomePage from './app/page';
import TiendaPage from './app/tienda/page';

const App: React.FC = () => {
  // Inicializar estado basado en el pathname actual
  const [pathname, setPathname] = useState<string>(
    typeof window !== 'undefined' ? window.location.pathname : '/'
  );

  useEffect(() => {
    const handlePopState = () => {
      setPathname(window.location.pathname);
    };

    // Escuchar cambios en el historial (pushState/popstate)
    window.addEventListener('popstate', handlePopState);
    
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  // Renderizador condicional basado en la ruta
  const renderPage = () => {
    switch (pathname) {
      case '/tienda':
        return <TiendaPage />;
      case '/':
      default:
        return <HomePage />;
    }
  };

  return (
    <RootLayout>
      {renderPage()}
    </RootLayout>
  );
};

export default App;
