
import React from 'react';
import { UI_STRINGS } from '@/constants/content';

const Hero: React.FC = () => {
  return (
    <section className="relative min-h-[90vh] flex items-center pt-20 overflow-hidden bg-vibrant-light">
      {/* Background Shapes */}
      <div className="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-vibrant-accent/20 rounded-full blur-3xl pointer-events-none"></div>
      <div className="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[500px] h-[500px] bg-vibrant-main/10 rounded-full blur-3xl pointer-events-none"></div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 grid md:grid-cols-2 gap-12 items-center">
        <div className="space-y-8 animate-fade-in-up">
          <div className="inline-flex items-center px-4 py-1.5 rounded-full bg-vibrant-main/10 border border-vibrant-main/20 text-vibrant-main font-bold text-xs uppercase tracking-widest">
            <span className="relative flex h-2 w-2 mr-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-vibrant-main opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-vibrant-main"></span>
            </span>
            Atención desde las 8:30 AM
          </div>

          <h1 className="text-5xl lg:text-7xl font-extrabold text-vibrant-dark leading-[1.1]">
            {UI_STRINGS.id_hero_title}
          </h1>

          <p className="text-lg md:text-xl text-vibrant-dark/70 leading-relaxed max-w-lg">
            {UI_STRINGS.id_hero_subtitle}
          </p>

          <div className="flex flex-col sm:flex-row gap-4">
            <button className="bg-vibrant-main text-white px-8 py-4 rounded-2xl font-bold text-lg hover:bg-vibrant-dark transition-all transform hover:-translate-y-1 shadow-xl shadow-vibrant-main/30">
              {UI_STRINGS.id_hero_cta_primary}
            </button>
            <button className="bg-white text-vibrant-dark border-2 border-vibrant-dark/10 px-8 py-4 rounded-2xl font-bold text-lg hover:bg-vibrant-dark hover:text-white transition-all transform hover:-translate-y-1">
              {UI_STRINGS.id_hero_cta_secondary}
            </button>
          </div>

          <div className="flex items-center space-x-4 pt-4">
            <div className="flex -space-x-3">
              {[1, 2, 3, 4].map((i) => (
                <img 
                  key={i} 
                  src={`https://picsum.photos/seed/user${i}/100/100`} 
                  alt="user" 
                  className="w-10 h-10 rounded-full border-2 border-white shadow-sm"
                />
              ))}
            </div>
            <div className="text-sm">
              <div className="flex text-yellow-500 mb-0.5">
                {[...Array(5)].map((_, i) => (
                  <svg key={i} className="w-4 h-4 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                ))}
              </div>
              <p className="text-vibrant-dark/60 font-medium">{UI_STRINGS.id_hero_rating}</p>
            </div>
          </div>
        </div>

        <div className="relative md:block hidden">
          <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] bg-vibrant-accent opacity-10 rounded-full blur-[100px]"></div>
          <div className="relative rounded-[40px] overflow-hidden border-[12px] border-white shadow-2xl rotate-2 hover:rotate-0 transition-transform duration-700">
            <img 
              src="https://picsum.photos/seed/vetclinic/800/1000" 
              alt="Mundo Jacome Clinic" 
              className="w-full h-full object-cover"
            />
            <div className="absolute bottom-6 left-6 right-6 bg-white/90 backdrop-blur px-6 py-4 rounded-2xl border border-white/50">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-vibrant-dark font-bold">Atención Médica</p>
                  <p className="text-vibrant-dark/60 text-xs">Mundo Jácome's</p>
                </div>
                <div className="bg-green-500 w-3 h-3 rounded-full animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;
