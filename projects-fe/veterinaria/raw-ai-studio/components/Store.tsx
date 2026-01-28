
import React, { useState } from 'react';
import { UI_STRINGS, PRODUCTS_DATA } from '../constants/content';

interface StoreProps {
  onBack: () => void;
}

const Store: React.FC<StoreProps> = ({ onBack }) => {
  const [filter, setFilter] = useState('Todos');
  const categories = ['Todos', 'Alimento', 'Salud', 'Juguetes', 'Accesorios'];

  const filteredProducts = filter === 'Todos' 
    ? PRODUCTS_DATA 
    : PRODUCTS_DATA.filter(p => p.category === filter);

  return (
    <div className="bg-vibrant-light pt-32 pb-24 min-h-screen">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-12">
          <div className="space-y-4">
            <button 
              onClick={onBack}
              className="flex items-center text-vibrant-main font-bold text-sm uppercase tracking-wider hover:gap-2 transition-all"
            >
              <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16l-4-4m0 0l4-4m-4 4h18" />
              </svg>
              {UI_STRINGS.id_store_back}
            </button>
            <h1 className="text-4xl md:text-6xl font-black text-vibrant-dark">{UI_STRINGS.id_store_title}</h1>
            <p className="text-xl text-vibrant-dark/60">{UI_STRINGS.id_store_subtitle}</p>
          </div>

          {/* Categories */}
          <div className="flex flex-wrap gap-2">
            {categories.map(cat => (
              <button
                key={cat}
                onClick={() => setFilter(cat)}
                className={`px-6 py-2 rounded-full font-bold text-sm transition-all ${
                  filter === cat 
                  ? 'bg-vibrant-main text-white shadow-lg shadow-vibrant-main/30' 
                  : 'bg-white text-vibrant-dark border border-vibrant-dark/10 hover:bg-vibrant-dark hover:text-white'
                }`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>

        {/* Product Grid */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
          {filteredProducts.map((product) => (
            <div 
              key={product.id}
              className="bg-white rounded-[32px] overflow-hidden border border-vibrant-dark/5 shadow-sm hover:shadow-xl transition-all group flex flex-col h-full"
            >
              <div className="relative aspect-square overflow-hidden bg-gray-100">
                <img 
                  src={product.image} 
                  alt={product.name}
                  className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div className="absolute top-4 left-4">
                  <span className="bg-vibrant-accent text-vibrant-dark px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                    {product.category}
                  </span>
                </div>
              </div>
              
              <div className="p-5 flex flex-col flex-grow">
                <h3 className="font-bold text-vibrant-dark mb-2 leading-tight group-hover:text-vibrant-main transition-colors">
                  {product.name}
                </h3>
                <div className="mt-auto pt-4 flex items-center justify-between">
                  <span className="text-xl font-black text-vibrant-dark">
                    ${product.price.toFixed(2)}
                  </span>
                  <button className="w-10 h-10 bg-vibrant-light rounded-2xl flex items-center justify-center text-vibrant-main hover:bg-vibrant-main hover:text-white transition-all transform active:scale-90">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {filteredProducts.length === 0 && (
          <div className="text-center py-20">
            <p className="text-vibrant-dark/40 text-lg">No se encontraron productos en esta categoría.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Store;
