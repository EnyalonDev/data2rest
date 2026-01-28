
import React from 'react';
import { UI_STRINGS } from '@/constants/content';

const About: React.FC = () => {
  return (
    <section id="nosotros" className="py-24 bg-vibrant-light">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-20 items-center">
          <div className="relative">
            <div className="absolute -top-10 -left-10 w-40 h-40 bg-vibrant-accent/30 rounded-full blur-3xl"></div>
            <div className="relative grid grid-cols-2 gap-4">
              <div className="space-y-4 pt-12">
                <div className="rounded-[32px] overflow-hidden border-4 border-white shadow-xl">
                  <img src="https://picsum.photos/seed/about1/400/500" alt="Clinic 1" className="w-full h-full object-cover" />
                </div>
                <div className="bg-vibrant-main p-8 rounded-[32px] text-white text-center shadow-xl">
                  <p className="text-5xl font-black mb-1">{UI_STRINGS.id_stats_years}</p>
                  <p className="text-xs font-bold uppercase tracking-widest opacity-80">{UI_STRINGS.id_stats_label}</p>
                </div>
              </div>
              <div className="space-y-4">
                <div className="rounded-[32px] overflow-hidden border-4 border-white shadow-xl aspect-square">
                  <img src="https://picsum.photos/seed/about2/400/400" alt="Pet 1" className="w-full h-full object-cover" />
                </div>
                <div className="rounded-[32px] overflow-hidden border-4 border-white shadow-xl">
                  <img src="https://picsum.photos/seed/about3/400/500" alt="Doc 1" className="w-full h-full object-cover" />
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-8">
            <div className="space-y-4">
              <h2 className="text-vibrant-main font-bold tracking-[0.2em] uppercase text-sm">
                {UI_STRINGS.id_about_title}
              </h2>
              <p className="text-4xl md:text-5xl font-extrabold text-vibrant-dark">
                {UI_STRINGS.id_about_subtitle}
              </p>
            </div>

            <div className="space-y-6 text-lg text-vibrant-dark/70 leading-relaxed">
              <p>{UI_STRINGS.id_about_desc_1}</p>
              <p>{UI_STRINGS.id_about_desc_2}</p>
            </div>

            <div className="grid grid-cols-2 gap-6">
              <div className="flex items-center space-x-3 bg-white p-4 rounded-2xl shadow-sm border border-vibrant-dark/5">
                <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span className="font-bold text-vibrant-dark text-sm">Certificación INSAI</span>
              </div>
              <div className="flex items-center space-x-3 bg-white p-4 rounded-2xl shadow-sm border border-vibrant-dark/5">
                <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span className="font-bold text-vibrant-dark text-sm">Resultados el mismo día</span>
              </div>
            </div>

            <div className="pt-6">
              <button className="bg-vibrant-dark text-white px-10 py-4 rounded-2xl font-bold hover:bg-vibrant-main transition-all flex items-center">
                Ver Instalaciones
                <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default About;
