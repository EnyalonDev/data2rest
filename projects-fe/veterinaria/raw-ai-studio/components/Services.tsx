
import React from 'react';
import { UI_STRINGS, SERVICES_DATA } from '../constants/content';

const Services: React.FC = () => {
  return (
    <section id="servicios" className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-20 space-y-4">
          <h2 className="text-vibrant-main font-bold tracking-[0.2em] uppercase text-sm">
            {UI_STRINGS.id_services_title}
          </h2>
          <p className="text-4xl md:text-5xl font-extrabold text-vibrant-dark">
            {UI_STRINGS.id_services_subtitle}
          </p>
          <div className="w-20 h-1.5 bg-vibrant-accent mx-auto rounded-full"></div>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {SERVICES_DATA.map((service) => (
            <div 
              key={service.id}
              className="group p-10 bg-vibrant-light rounded-[32px] border border-transparent hover:border-vibrant-accent transition-all duration-300 hover:shadow-2xl hover:-translate-y-2"
            >
              <div className="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-3xl mb-8 group-hover:scale-110 transition-transform">
                {service.icon}
              </div>
              <h3 className="text-2xl font-bold text-vibrant-dark mb-4">{service.title}</h3>
              <p className="text-vibrant-dark/70 leading-relaxed mb-6">
                {service.description}
              </p>
              <button className="flex items-center text-vibrant-main font-bold text-sm uppercase tracking-wider group-hover:gap-2 transition-all">
                Saber más 
                <svg className="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
              </button>
            </div>
          ))}
        </div>
        
        <div className="mt-20 p-8 rounded-[40px] bg-vibrant-dark text-white relative overflow-hidden">
          <div className="absolute top-0 right-0 w-64 h-64 bg-vibrant-main opacity-20 rounded-full -translate-y-1/2 translate-x-1/2"></div>
          <div className="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
            <div className="max-w-xl text-center md:text-left">
              <h4 className="text-2xl font-bold mb-2">Contamos con instalaciones de primer nivel diseñadas para una visita placentera.</h4>
              <p className="text-white/60">Quirófanos, laboratorios y áreas de hospitalización con tecnología de vanguardia.</p>
            </div>
            <button className="whitespace-nowrap bg-vibrant-main px-10 py-4 rounded-2xl font-bold hover:bg-white hover:text-vibrant-dark transition-all">
              Agendar ahora
            </button>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Services;
