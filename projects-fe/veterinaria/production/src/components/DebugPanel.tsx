
'use client';

import React, { useEffect, useState } from 'react';
import { ENV_SETTINGS } from '@/constants/content';

const DebugPanel: React.FC = () => {
  const [authError, setAuthError] = useState<any>(null);

  useEffect(() => {
    // Intentar leer errores guardados por la página de callback
    const storedError = localStorage.getItem('last_auth_error');
    if (storedError) {
      try {
        setAuthError(JSON.parse(storedError));
      } catch (e) {
        console.error('Error parsing debug logs', e);
      }
    }
  }, []);

  return (
    <div className="bg-gray-900 text-green-400 p-6 font-mono text-[10px] border-t-4 border-vibrant-main mt-auto shadow-2xl">
      <div className="max-w-7xl mx-auto">
        <h3 className="text-white font-bold mb-3 flex items-center text-xs">
          <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></span>
          SISTEMA DE MONITOREO MUNDO JÁCOME'S V3.0
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div className="bg-white/5 p-2 rounded">
            <p className="text-gray-500 uppercase text-[8px]">Entorno:</p>
            <p className="text-white font-bold">{ENV_SETTINGS.IS_LOCAL ? 'DEVELOPMENT_LOCAL' : 'PRODUCTION'}</p>
          </div>
          <div className="bg-white/5 p-2 rounded">
            <p className="text-gray-500 uppercase text-[8px]">Proxy de Seguridad:</p>
            <p className={ENV_SETTINGS.USE_PROXY ? 'text-green-400' : 'text-red-400'}>
              {ENV_SETTINGS.USE_PROXY ? 'ACTIVO_SEGURO' : 'INACTIVO_RIESGO'}
            </p>
          </div>
          <div className="bg-white/5 p-2 rounded">
            <p className="text-gray-500 uppercase text-[8px]">API Base:</p>
            <p className="text-white truncate">{ENV_SETTINGS.API_BASE_URL}</p>
          </div>
          <div className="bg-white/5 p-2 rounded">
            <p className="text-gray-500 uppercase text-[8px]">Proyecto ID:</p>
            <p className="text-white">{ENV_SETTINGS.PROJECT_ID}</p>
          </div>
        </div>

        {authError && (
          <div className="bg-red-900/40 border border-red-500/50 p-4 rounded-xl mb-4 animate-pulse">
            <div className="flex justify-between items-start mb-2">
              <p className="text-red-400 font-black uppercase text-[9px]">⚠️ ÚLTIMO ERROR DETECTADO (GOOGLE_AUTH):</p>
              <button
                onClick={() => { localStorage.removeItem('last_auth_error'); setAuthError(null); }}
                className="text-white/40 hover:text-white"
              > [LIMPIAR] </button>
            </div>
            <p className="text-white font-bold mb-1 text-[11px]">{authError.error}</p>
            <p className="text-red-300 text-[9px] italic mb-1">Hora: {authError.ts}</p>
            {authError.details && (
              <pre className="mt-2 p-2 bg-black/50 rounded text-[9px] text-gray-400 overflow-x-auto">
                {JSON.stringify(authError.details, null, 2)}
              </pre>
            )}
          </div>
        )}

        <div className="pt-3 border-t border-gray-800 flex justify-between items-center text-[9px] text-gray-500">
          <p>ESTADO: Sistema operando bajo protocolo seguro. Redirecciones controladas con buffer de 800ms.</p>
          <p className="italic">d2r_engine_v15.2</p>
        </div>
      </div>
    </div>
  );
};

export default DebugPanel;
