
import React from 'react';

const Reviews: React.FC = () => {
  const testimonials = [
    { name: "Sra. Carmen", text: "La mejor atención que ha recibido mi perrita. Los diagnósticos son muy precisos." },
    { name: "Juan Pérez", text: "Increíble el servicio de exportación. Mi gato llegó a España sin problemas." },
    { name: "Dra. Lucía", text: "Confío plenamente en el equipo médico para cirugías complejas." }
  ];

  return (
    <section id="opiniones" className="py-24 bg-vibrant-dark text-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div className="inline-flex items-center px-6 py-2 rounded-full bg-white/10 border border-white/20 text-vibrant-accent font-bold text-sm uppercase mb-12">
            Confianza Comprobada
        </div>
        
        <h2 className="text-4xl md:text-6xl font-black mb-16">La Voz de Nuestros Clientes</h2>

        <div className="grid md:grid-cols-3 gap-8">
            {testimonials.map((t, i) => (
                <div key={i} className="p-10 bg-white/5 rounded-[40px] border border-white/10 text-left space-y-6 hover:bg-white/10 transition-colors">
                    <div className="flex text-vibrant-accent">
                        {[...Array(5)].map((_, j) => (
                            <svg key={j} className="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        ))}
                    </div>
                    <p className="text-xl italic text-white/80">"{t.text}"</p>
                    <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 rounded-full bg-vibrant-main/20 flex items-center justify-center font-bold text-vibrant-main">
                            {t.name.charAt(0)}
                        </div>
                        <p className="font-bold">{t.name}</p>
                    </div>
                </div>
            ))}
        </div>

        <div className="mt-20 flex flex-col items-center space-y-6">
            <div className="flex items-center space-x-6">
                <span className="text-7xl font-black text-vibrant-main">5.0</span>
                <div className="text-left">
                    <p className="text-2xl font-bold uppercase tracking-widest">Estrellas</p>
                    <p className="text-white/60">en Google Maps</p>
                </div>
            </div>
            <button className="bg-white text-vibrant-dark px-10 py-4 rounded-2xl font-bold hover:bg-vibrant-accent transition-all">
                Dejar una opinión
            </button>
        </div>
      </div>
    </section>
  );
};

export default Reviews;
