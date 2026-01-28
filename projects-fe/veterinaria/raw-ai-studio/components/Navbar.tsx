
'use client';

import React, { useState } from 'react';
import { UI_STRINGS } from '../constants/content';
import Link from './Link';

interface NavbarProps {
  isScrolled: boolean;
}

const Navbar: React.FC<NavbarProps> = ({ isScrolled }) => {
  const [isOpen, setIsOpen] = useState(false);

  const navLinks = [
    { name: UI_STRINGS.id_nav_services, href: "/#servicios", type: 'anchor' },
    { name: UI_STRINGS.id_nav_about, href: "/#nosotros", type: 'anchor' },
    { name: UI_STRINGS.id_nav_gallery, href: "/#galeria", type: 'anchor' },
    { name: UI_STRINGS.id_nav_reviews, href: "/#opiniones", type: 'anchor' },
    { name: UI_STRINGS.id_nav_contact, href: "/#contacto", type: 'anchor' },
    { name: UI_STRINGS.id_nav_store, href: "/tienda", type: 'page' },
  ];

  const handleAnchorClick = (e: React.MouseEvent, href: string) => {
    if (href.startsWith('/#')) {
      const id = href.split('#')[1];
      const element = document.getElementById(id);
      
      if (window.location.pathname !== '/') {
        // Si no estamos en la home, dejamos que Link maneje la navegación a /
        // El scroll al ID ocurrirá después de la carga si implementamos un useEffect en el home
      } else if (element) {
        e.preventDefault();
        element.scrollIntoView({ behavior: 'smooth' });
        setIsOpen(false);
      }
    }
  };

  return (
    <nav className={`fixed w-full z-50 transition-all duration-300 ${
      isScrolled ? 'bg-white shadow-md py-3' : 'bg-transparent py-5'
    }`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <Link 
            href="/"
            className="flex-shrink-0 flex flex-col items-start leading-tight cursor-pointer"
          >
            <span className={`font-extrabold text-2xl tracking-tight transition-colors ${
              isScrolled ? 'text-vibrant-main' : 'text-vibrant-main'
            }`}>
              {UI_STRINGS.id_app_name}
            </span>
            <span className={`text-xs font-semibold uppercase tracking-widest ${
              isScrolled ? 'text-vibrant-dark/70' : 'text-vibrant-dark/80'
            }`}>
              {UI_STRINGS.id_app_tagline}
            </span>
          </Link>

          <div className="hidden md:flex items-center space-x-8">
            {navLinks.map((link) => (
              <Link
                key={link.name}
                href={link.href}
                onClick={(e) => handleAnchorClick(e, link.href)}
                className="text-vibrant-dark font-medium hover:text-vibrant-main transition-colors text-sm"
              >
                {link.name}
              </Link>
            ))}
            <button className="bg-vibrant-main text-white px-6 py-2.5 rounded-full font-bold text-sm hover:bg-vibrant-dark transition-all transform hover:scale-105 shadow-lg shadow-vibrant-main/20">
              {UI_STRINGS.id_cta_appointment}
            </button>
          </div>

          <div className="md:hidden">
            <button 
              onClick={() => setIsOpen(!isOpen)}
              className="text-vibrant-dark p-2"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {isOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div className={`md:hidden absolute w-full bg-white shadow-xl transition-all duration-300 overflow-hidden ${
        isOpen ? 'max-h-screen pb-10' : 'max-h-0'
      }`}>
        <div className="px-4 pt-2 pb-6 space-y-1">
          {navLinks.map((link) => (
            <Link
              key={link.name}
              href={link.href}
              onClick={(e) => {
                handleAnchorClick(e, link.href);
                setIsOpen(false);
              }}
              className="block px-3 py-4 text-base font-medium text-vibrant-dark hover:text-vibrant-main"
            >
              {link.name}
            </Link>
          ))}
          <div className="pt-4">
            <button className="w-full bg-vibrant-main text-white px-6 py-3 rounded-xl font-bold">
              {UI_STRINGS.id_cta_appointment}
            </button>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
