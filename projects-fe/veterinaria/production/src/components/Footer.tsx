
import React from 'react';
import { UI_STRINGS } from '@/constants/content';

const Footer: React.FC = () => {
  return (
    <footer className="bg-vibrant-light border-t border-vibrant-dark/5 pt-20 pb-10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
          <div className="space-y-6">
            <div>
              <p className="font-extrabold text-2xl text-vibrant-main tracking-tight leading-none">
                {UI_STRINGS.id_app_name}
              </p>
              <p className="text-xs font-semibold uppercase tracking-widest text-vibrant-dark/70">
                {UI_STRINGS.id_app_tagline}
              </p>
            </div>
            <p className="text-vibrant-dark/60 leading-relaxed">
                Centro médico veterinario líder en Táchira con más de 15 años de trayectoria. Comprometidos con el bienestar integral de tus mascotas.
            </p>
            <div className="flex space-x-4">
                {[1,2,3].map(i => (
                    <div key={i} className="w-10 h-10 rounded-xl bg-vibrant-dark text-white flex items-center justify-center hover:bg-vibrant-main transition-colors cursor-pointer">
                        <span className="sr-only">Social Link {i}</span>
                        {i === 1 && "f"}
                        {i === 2 && "i"}
                        {i === 3 && "w"}
                    </div>
                ))}
            </div>
          </div>

          <div>
            <h4 className="font-bold text-vibrant-dark mb-6 uppercase tracking-widest text-xs">Enlaces</h4>
            <ul className="space-y-4 text-vibrant-dark/60">
              <li><a href="#servicios" className="hover:text-vibrant-main transition-colors">Servicios</a></li>
              <li><a href="#nosotros" className="hover:text-vibrant-main transition-colors">Nosotros</a></li>
              <li><a href="#galeria" className="hover:text-vibrant-main transition-colors">Galería</a></li>
              <li><a href="#opiniones" className="hover:text-vibrant-main transition-colors">Opiniones</a></li>
              <li><a href="#contacto" className="hover:text-vibrant-main transition-colors">Contacto</a></li>
            </ul>
          </div>

          <div>
            <h4 className="font-bold text-vibrant-dark mb-6 uppercase tracking-widest text-xs">Contacto</h4>
            <ul className="space-y-4 text-vibrant-dark/60">
              <li className="flex items-start">
                  <span className="mr-2">📍</span>
                  {UI_STRINGS.id_contact_address}
              </li>
              <li className="flex items-center">
                  <span className="mr-2">📞</span>
                  {UI_STRINGS.id_contact_phone}
              </li>
            </ul>
          </div>

          <div>
            <h4 className="font-bold text-vibrant-dark mb-6 uppercase tracking-widest text-xs">Horarios</h4>
            <ul className="space-y-4 text-vibrant-dark/60">
              <li>{UI_STRINGS.id_contact_hours}</li>
              <li className="text-vibrant-main font-bold">{UI_STRINGS.id_contact_reception}</li>
            </ul>
          </div>
        </div>

        <div className="pt-10 border-t border-vibrant-dark/5 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-vibrant-dark/40 font-medium">
          <p>{UI_STRINGS.id_footer_rights}</p>
          <p>{UI_STRINGS.id_footer_dev}</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
