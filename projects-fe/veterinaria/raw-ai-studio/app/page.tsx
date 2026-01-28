
'use client';

import React, { useEffect } from 'react';
import Hero from '../components/Hero';
import Services from '../components/Services';
import About from '../components/About';
import Facilities from '../components/Facilities';
import Gallery from '../components/Gallery';
import Reviews from '../components/Reviews';
import Contact from '../components/Contact';

export default function HomePage() {
  useEffect(() => {
    // Si la URL tiene un hash al cargar la página principal
    if (window.location.hash) {
      const id = window.location.hash.substring(1);
      const element = document.getElementById(id);
      if (element) {
        setTimeout(() => {
          element.scrollIntoView({ behavior: 'smooth' });
        }, 100);
      }
    }
  }, []);

  return (
    <>
      <Hero />
      <Services />
      <About />
      <Facilities />
      <Gallery />
      <Reviews />
      <Contact />
    </>
  );
}
