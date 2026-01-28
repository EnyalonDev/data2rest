
'use client';

import React, { useState, useEffect } from 'react';
import Navbar from '@/components/Navbar';
import Hero from '@/components/Hero';
import Services from '@/components/Services';
import About from '@/components/About';
import Facilities from '@/components/Facilities';
import Gallery from '@/components/Gallery';
import Reviews from '@/components/Reviews';
import Contact from '@/components/Contact';
import Footer from '@/components/Footer';
import ChatBot from '@/components/ChatBot';
import DebugPanel from '@/components/DebugPanel';
import { ENV_SETTINGS } from '@/constants/content';

export default function Home() {
  const [isScrolled, setIsScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <div className="min-h-screen flex flex-col bg-vibrant-light">
      <Navbar isScrolled={isScrolled} />

      <main className="flex-grow">
        <Hero />
        <Services />
        <About />
        <Facilities />
        <Gallery />
        <Reviews />
        <Contact />
      </main>

      <Footer />
      <ChatBot />

      {ENV_SETTINGS.SHOW_DEBUG_PANEL && <DebugPanel />}
    </div>
  );
}
