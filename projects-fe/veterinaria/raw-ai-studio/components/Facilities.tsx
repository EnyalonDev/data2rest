
import React from 'react';

const Facilities: React.FC = () => {
  return (
    <section className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="bg-vibrant-accent/10 rounded-[48px] p-12 lg:p-20 relative overflow-hidden">
          <div className="absolute bottom-0 right-0 opacity-10 pointer-events-none">
            <svg width="400" height="400" viewBox="0 0 200 200">
                <circle cx="100" cy="100" r="80" fill="currentColor" className="text-vibrant-main" />
            </svg>
          </div>
          
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div className="space-y-8">
              <div className="space-y-4">
                <h2 className="text-vibrant-main font-bold tracking-[0.2em] uppercase text-sm">Comodidad Total</h2>
                <p className="text-4xl md:text-5xl font-extrabold text-vibrant-dark">Tu Comodidad es Nuestra Prioridad</p>
                <p className="text-lg text-vibrant-dark/70 leading-relaxed">
                  Disponemos de estacionamiento privado y garaje gratuito para que tu visita sea sin estrés y totalmente segura.
                </p>
              </div>

              <div className="space-y-4">
                {[
                  "Estacionamiento Privado",
                  "Garaje Gratuito",
                  "Acceso Seguro"
                ].map((item, idx) => (
                  <div key={idx} className="flex items-center space-x-4">
                    <div className="flex-shrink-0 w-8 h-8 rounded-full bg-vibrant-main text-white flex items-center justify-center font-bold text-sm">
                      {idx + 1}
                    </div>
                    <span className="text-xl font-bold text-vibrant-dark">{item}</span>
                  </div>
                ))}
              </div>

              <button className="bg-vibrant-dark text-white px-8 py-4 rounded-2xl font-bold hover:bg-vibrant-main transition-all">
                Cómo llegar
              </button>
            </div>

            <div className="relative">
              <div className="rounded-[40px] overflow-hidden shadow-2xl">
                <img src="https://picsum.photos/seed/parking/800/600" alt="Parking" className="w-full h-full object-cover" />
              </div>
              <div className="absolute -bottom-8 -left-8 bg-vibrant-main p-8 rounded-[32px] text-white shadow-xl hidden sm:block">
                <div className="text-4xl font-black mb-1">0%</div>
                <div className="text-xs font-bold uppercase tracking-widest">Costo de Estacionamiento</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Facilities;
