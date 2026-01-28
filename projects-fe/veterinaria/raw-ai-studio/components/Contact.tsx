
import React from 'react';
import { UI_STRINGS } from '../constants/content';

const Contact: React.FC = () => {
  return (
    <section id="contacto" className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-start">
          <div className="space-y-12">
            <div className="space-y-4">
                <h2 className="text-vibrant-main font-bold tracking-[0.2em] uppercase text-sm">Visítanos</h2>
                <p className="text-4xl md:text-5xl font-extrabold text-vibrant-dark">Estamos en Las Vegas de Táriba</p>
            </div>

            <div className="grid sm:grid-cols-2 gap-8">
                <div className="space-y-2">
                    <p className="font-bold text-vibrant-dark uppercase tracking-widest text-xs opacity-60">Dirección</p>
                    <p className="text-lg text-vibrant-dark font-medium leading-relaxed">
                        {UI_STRINGS.id_contact_address}
                    </p>
                </div>
                <div className="space-y-2">
                    <p className="font-bold text-vibrant-dark uppercase tracking-widest text-xs opacity-60">Llamar</p>
                    <p className="text-lg text-vibrant-dark font-bold">
                        {UI_STRINGS.id_contact_phone}
                    </p>
                </div>
                <div className="space-y-2">
                    <p className="font-bold text-vibrant-dark uppercase tracking-widest text-xs opacity-60">Horario</p>
                    <p className="text-vibrant-dark font-medium">
                        {UI_STRINGS.id_contact_reception}<br/>
                        {UI_STRINGS.id_contact_hours}
                    </p>
                </div>
            </div>

            <div className="flex flex-col sm:flex-row gap-4">
                <button className="bg-vibrant-main text-white px-8 py-4 rounded-2xl font-bold hover:bg-vibrant-dark transition-all flex items-center justify-center">
                    <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    Ver en Google Maps
                </button>
                <button className="bg-vibrant-light text-vibrant-dark border border-vibrant-dark/10 px-8 py-4 rounded-2xl font-bold hover:bg-vibrant-dark hover:text-white transition-all">
                    Enviar Mensaje
                </button>
            </div>
          </div>

          <div className="relative h-[500px] rounded-[48px] overflow-hidden shadow-2xl border-8 border-vibrant-light">
            {/* Simulation of a Map */}
            <div className="absolute inset-0 bg-[#E5E7EB] flex items-center justify-center">
                <div className="text-center space-y-4">
                    <div className="w-20 h-20 bg-vibrant-main text-white rounded-full mx-auto flex items-center justify-center animate-bounce shadow-xl">
                        <svg className="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    </div>
                    <p className="font-bold text-vibrant-dark">Ubicación Mundo Jácome's</p>
                </div>
            </div>
            {/* Overlay Grid Pattern */}
            <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(#3D2616 1px, transparent 0)', backgroundSize: '40px 40px' }}></div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Contact;
