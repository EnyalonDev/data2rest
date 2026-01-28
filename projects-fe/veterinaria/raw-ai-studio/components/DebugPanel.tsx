
import React from 'react';
import { ENV_SETTINGS } from '../constants/content';

const DebugPanel: React.FC = () => {
  return (
    <div className="bg-gray-900 text-green-400 p-6 font-mono text-xs border-t-4 border-vibrant-main mt-auto">
      <div className="max-w-7xl mx-auto">
        <h3 className="text-white font-bold mb-3 flex items-center">
          <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></span>
          DEBUG_PANEL_SYSTEM_V2.5
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p className="text-gray-500">ENVIRONMENT:</p>
            <p className="text-white">{ENV_SETTINGS.IS_LOCAL ? 'DEVELOPMENT_LOCAL' : 'PRODUCTION'}</p>
          </div>
          <div>
            <p className="text-gray-500">PROXY_STATUS:</p>
            <p className={ENV_SETTINGS.USE_PROXY ? 'text-green-400' : 'text-red-400'}>
                {ENV_SETTINGS.USE_PROXY ? 'ACTIVE_ON' : 'BYPASS_OFF'}
            </p>
          </div>
          <div>
            <p className="text-gray-500">API_BASE:</p>
            <p className="text-white">{ENV_SETTINGS.API_BASE_URL}</p>
          </div>
          <div>
            <p className="text-gray-500">AUTH_CLIENT:</p>
            <p className="text-white truncate">{ENV_SETTINGS.GOOGLE_CLIENT_ID}</p>
          </div>
        </div>
        <div className="mt-4 pt-4 border-t border-gray-800">
            <p>LOGS: Site successfully rendered. All components initialized. Assets loaded via Picsum CDN.</p>
        </div>
      </div>
    </div>
  );
};

export default DebugPanel;
