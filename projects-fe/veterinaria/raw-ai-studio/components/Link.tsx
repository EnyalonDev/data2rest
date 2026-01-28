
'use client';

import React from 'react';

interface LinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
  href: string;
  children: React.ReactNode;
}

const Link: React.FC<LinkProps> = ({ href, children, className, ...props }) => {
  const handleClick = (e: React.MouseEvent<HTMLAnchorElement>) => {
    // Si es un enlace interno (comienza con / o es la ruta actual)
    if (href.startsWith('/') || href.startsWith(window.location.origin)) {
      e.preventDefault();
      
      // Actualizar la URL sin recargar
      window.history.pushState({}, '', href);
      
      // Notificar al "Router" manual
      const navEvent = new PopStateEvent('popstate');
      window.dispatchEvent(navEvent);
      
      // Scroll al inicio
      window.scrollTo(0, 0);
    }
  };

  return (
    <a href={href} onClick={handleClick} className={className} {...props}>
      {children}
    </a>
  );
};

export default Link;
