
'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import { ENV_SETTINGS, UI_STRINGS } from '@/constants/content';
import { ShieldCheck, Mail, Lock, AlertCircle, ArrowRight } from 'lucide-react';
import api from '@/lib/api';
import Link from 'next/link';
import DebugPanel from '@/components/DebugPanel';

export default function LoginPage() {
    const router = useRouter();
    const { isAuthenticated, setAuth } = useAuthStore();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    useEffect(() => {
        if (isHydrated && isAuthenticated) {
            router.push('/dashboard');
        }
    }, [isHydrated, isAuthenticated, router]);

    const handleGoogleLogin = () => {
        const redirectUri = encodeURIComponent(`${window.location.origin}/auth/callback`);
        const authUrl = `${ENV_SETTINGS.AUTH_GATE_URL}/google?redirect_uri=${redirectUri}`;
        window.location.href = authUrl;
    };

    const handleTraditionalLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setError(null);

        try {
            const response = await api.post(`${ENV_SETTINGS.AUTH_GATE_URL}/login`, {
                email,
                password
            });

            const { token, user } = response.data.data;
            setAuth(user, token);
            router.push('/dashboard');
        } catch (err: any) {
            setError(err.response?.data?.error || 'Credenciales incorrectas');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-vibrant-light flex items-center justify-center p-4">
            {/* Background Ornaments */}
            <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-vibrant-accent/10 rounded-full blur-[100px] pointer-events-none"></div>
            <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-vibrant-main/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div className="max-w-md w-full relative">
                <div className="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/50 backdrop-blur-sm">
                    <div className="bg-vibrant-main p-8 text-white text-center relative overflow-hidden">
                        <div className="absolute inset-0 opacity-10 pointer-events-none">
                            <div className="absolute top-0 left-0 w-16 h-16 border-2 border-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                            <div className="absolute bottom-0 right-0 w-24 h-24 border-4 border-white rounded-full translate-x-1/4 translate-y-1/4"></div>
                        </div>

                        <div className="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl rotate-3">
                            <ShieldCheck size={32} strokeWidth={2.5} />
                        </div>

                        <h1 className="text-2xl font-extrabold mb-1 tracking-tight">
                            {UI_STRINGS.id_app_name}
                        </h1>
                        <p className="text-white/80 font-medium uppercase tracking-[0.2em] text-[9px]">
                            Acceso al Panel Médico
                        </p>
                    </div>

                    <div className="p-8 space-y-6 bg-white/50">
                        <form onSubmit={handleTraditionalLogin} className="space-y-4">
                            {error && (
                                <div className="bg-red-50 text-red-600 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-shake border border-red-100">
                                    <AlertCircle size={18} />
                                    {error}
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold text-vibrant-dark/40 uppercase tracking-widest ml-1">Correo Electrónico</label>
                                <div className="relative group">
                                    <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-vibrant-dark/20 group-focus-within:text-vibrant-main transition-colors" size={20} />
                                    <input
                                        type="email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        placeholder="doctor@mundojacome.com"
                                        className="w-full bg-vibrant-dark/5 border-2 border-transparent focus:border-vibrant-main/20 focus:bg-white p-4 pl-12 rounded-2xl outline-none transition-all text-sm font-semibold text-vibrant-dark placeholder:text-vibrant-dark/30"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold text-vibrant-dark/40 uppercase tracking-widest ml-1">Contraseña</label>
                                <div className="relative group">
                                    <Lock className="absolute left-4 top-1/2 -translate-y-1/2 text-vibrant-dark/20 group-focus-within:text-vibrant-main transition-colors" size={20} />
                                    <input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        placeholder="••••••••"
                                        className="w-full bg-vibrant-dark/5 border-2 border-transparent focus:border-vibrant-main/20 focus:bg-white p-4 pl-12 rounded-2xl outline-none transition-all text-sm font-semibold text-vibrant-dark placeholder:text-vibrant-dark/30"
                                        required
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={isLoading}
                                className="w-full bg-vibrant-main hover:bg-vibrant-dark text-white p-4 rounded-2xl font-bold text-sm shadow-xl shadow-vibrant-main/20 hover:shadow-vibrant-dark/30 transition-all transform hover:-translate-y-1 active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                {isLoading ? 'Entrando...' : 'Entrar al Panel'}
                                {!isLoading && <ArrowRight size={18} />}
                            </button>
                        </form>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-vibrant-dark/5"></div>
                            </div>
                            <div className="relative flex justify-center text-[9px] uppercase tracking-widest text-vibrant-dark/20 font-black">
                                <span className="bg-white px-4 italic">O continúa con</span>
                            </div>
                        </div>

                        <button
                            onClick={handleGoogleLogin}
                            className="group relative w-full flex items-center justify-center gap-4 bg-white hover:bg-gray-50 text-vibrant-dark border-2 border-vibrant-dark/5 p-4 rounded-2xl font-bold text-sm transition-all shadow-sm hover:shadow-md active:scale-95"
                        >
                            <svg viewBox="0 0 24 24" className="w-5 h-5" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" />
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335" />
                            </svg>
                            Google Workspace
                        </button>

                        <p className="text-center text-[11px] text-vibrant-dark/40 font-medium">
                            ¿Eres nuevo? {' '}
                            <Link href="/register" className="text-vibrant-main font-bold hover:underline">
                                Crea tu cuenta aquí
                            </Link>
                        </p>
                    </div>
                </div>

                <div className="mt-8 text-center text-[10px] font-bold text-vibrant-dark/30 uppercase tracking-[0.3em]">
                    {UI_STRINGS.id_footer_dev}
                </div>
            </div>
            <div className="fixed bottom-0 left-0 right-0 z-50">
                <DebugPanel />
            </div>
        </div>
    );
}
