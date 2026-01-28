
'use client';

import React from 'react';
import Store from '../../components/Store';
import Link from '../../components/Link';

export default function TiendaPage() {
  const handleBack = () => {
    // La navegación ahora es manejada por Link o window.history
    window.history.back();
  };

  return <Store onBack={handleBack} />;
}
