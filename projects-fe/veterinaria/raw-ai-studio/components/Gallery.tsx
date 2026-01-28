
import React from 'react';
import { GALLERY_DATA } from '../constants/content';

const Gallery: React.FC = () => {
  return (
    <section id="galeria" className="py-24 bg-white overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-20 space-y-4">
          <h2 className="text-vibrant-main font-bold tracking-[0.2em] uppercase text-sm">Momentos Jácome</h2>
          <p className="text-4xl md:text-5xl font-extrabold text-vibrant-dark">Historias de Recuperación</p>
          <p className="text-lg text-vibrant-dark/60">Cada paciente que cruza nuestra puerta se convierte en parte de nuestra familia.</p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {GALLERY_DATA.map((img) => (
            <div 
              key={img.id}
              className="group relative rounded-[32px] overflow-hidden aspect-[4/5] cursor-pointer"
            >
              <img 
                src={img.src} 
                alt={img.title} 
                className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-vibrant-dark/80 via-vibrant-dark/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-8">
                <p className="text-white font-bold text-xl translate-y-4 group-hover:translate-y-0 transition-transform">
                  {img.title}
                </p>
              </div>
            </div>
          ))}
        </div>

        <div className="mt-16 text-center">
            <button className="bg-vibrant-light text-vibrant-dark border-2 border-vibrant-dark/10 px-10 py-4 rounded-2xl font-bold hover:bg-vibrant-main hover:text-white transition-all">
                Ver Galería en Instagram
            </button>
        </div>
      </div>
    </section>
  );
};

export default Gallery;
