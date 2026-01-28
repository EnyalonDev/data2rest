
'use client';

import { useEffect, useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import api from '@/lib/api';
import { useAuthStore } from '@/stores/authStore';
import { ENV_SETTINGS } from '@/constants/content';

function CallbackContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const setAuth = useAuthStore((state) => state.setAuth);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const code = searchParams.get('code');

        if (code) {
            const verifyCode = async () => {
                try {
                    const redirectUri = `${window.location.origin}/auth/callback`;
                    console.log('Iniciando verificación de código...', { code, redirectUri });

                    const response = await api.post(`${ENV_SETTINGS.AUTH_GATE_URL}/google/callback`, {
                        code,
                        redirect_uri: redirectUri
                    });

                    console.log('Respuesta del backend:', response.data);
                    const { token, user } = response.data.data;

                    // Guardamos en el store
                    setAuth(user, token);

                    // Esperamos un momento para asegurar persistencia en localStorage
                    setTimeout(() => {
                        console.log('Redirigiendo al Dashboard...');
                        router.push('/dashboard');
                    }, 800);
                } catch (err: any) {
                    console.error('Error en callback:', err);
                    const errorMsg = err.response?.data?.error || err.message || 'Error desconocido';
                    setError(errorMsg);

                    // Guardamos error en localStorage para el DebugPanel
                    if (typeof window !== 'undefined') {
                        localStorage.setItem('last_auth_error', JSON.stringify({
                            ts: new Date().toISOString(),
                            error: errorMsg,
                            details: err.response?.data
                        }));
                    }
                }
            };

            verifyCode();
        } else {
            setError('No se recibió el código de autorización de Google.');
        }
    }, [searchParams, setAuth, router]);

    if (error) {
        return (
            <div className="flex flex-col items-center justify-center min-h-screen bg-vibrant-dark/5 p-4">
                <div className="bg-white p-8 rounded-[2rem] shadow-2xl max-w-md w-full text-center border-t-8 border-red-500">
                    <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span className="text-4xl">⚠️</span>
                    </div>
                    <h1 className="text-2xl font-black text-vibrant-dark mb-4 uppercase tracking-tight">¡Error de Acceso!</h1>
                    <div className="bg-red-50 p-4 rounded-xl mb-6 border border-red-100 italic text-sm text-red-600">
                        {error}
                    </div>
                    <button
                        onClick={() => router.push('/login')}
                        className="w-full bg-vibrant-main text-white px-8 py-4 rounded-2xl font-bold hover:bg-vibrant-dark transition-all shadow-xl shadow-vibrant-main/20"
                    >
                        Volver al Login
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center justify-center min-h-screen bg-vibrant-light p-4 overflow-hidden">
            <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-vibrant-accent/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-vibrant-main/5 rounded-full blur-[100px] pointer-events-none"></div>

            <div className="relative text-center">
                <div className="w-24 h-24 border-8 border-vibrant-main border-t-transparent rounded-full animate-spin mx-auto mb-8 shadow-2xl shadow-vibrant-main/10"></div>
                <h1 className="text-3xl font-black text-vibrant-dark tracking-tighter mb-2 italic">AUTENTICANDO...</h1>
                <p className="text-vibrant-dark/40 font-bold uppercase tracking-[0.3em] text-[10px]">Verificando credenciales con Mundo Jácome's</p>
            </div>
        </div>
    );
}

export default function CallbackPage() {
    return (
        <Suspense fallback={<div>Cargando...</div>}>
            <CallbackContent />
        </Suspense>
    );
}
