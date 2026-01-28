
'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import { ENV_SETTINGS, UI_STRINGS } from '@/constants/content';
import { ShieldPlus, Mail, Lock, User, AlertCircle, ArrowRight } from 'lucide-react';
import api from '@/lib/api';
import Link from 'next/link';
import DebugPanel from '@/components/DebugPanel';

export default function RegisterPage() {
    const router = useRouter();
    const setAuth = useAuthStore((state) => state.setAuth);

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [isHydrated, setIsHydrated] = useState(false);
    const { isAuthenticated } = useAuthStore();

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    useEffect(() => {
        if (isHydrated && isAuthenticated) {
            router.push('/dashboard');
        }
    }, [isHydrated, isAuthenticated, router]);

    const handleRegister = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setError(null);

        try {
            const response = await api.post(`${ENV_SETTINGS.AUTH_GATE_URL}/register`, {
                name,
                email,
                password
            });

            const { token, user } = response.data.data;
            setAuth(user, token);
            router.push('/dashboard');
        } catch (err: any) {
            setError(err.response?.data?.error || 'Error al crear la cuenta. Inténtalo de nuevo.');
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
                            <ShieldPlus size={32} strokeWidth={2.5} className="text-white" />
                        </div>

                        <h1 className="text-2xl font-extrabold mb-1 tracking-tight">
                            Únete a Mundo Jácome's
                        </h1>
                        <p className="text-white/60 font-medium uppercase tracking-[0.2em] text-[9px]">
                            Comienza a gestionar tu clínica
                        </p>
                    </div>

                    <div className="p-8 space-y-6 bg-white/50">
                        <form onSubmit={handleRegister} className="space-y-4">
                            {error && (
                                <div className="bg-red-50 text-red-600 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 border border-red-100 animate-shake">
                                    <AlertCircle size={18} />
                                    {error}
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold text-vibrant-dark/40 uppercase tracking-widest ml-1">Nombre Completo</label>
                                <div className="relative group">
                                    <User className="absolute left-4 top-1/2 -translate-y-1/2 text-vibrant-dark/20 group-focus-within:text-vibrant-main transition-colors" size={20} />
                                    <input
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        placeholder="Dr. Juan Pérez"
                                        className="w-full bg-vibrant-dark/5 border-2 border-transparent focus:border-vibrant-main/20 focus:bg-white p-4 pl-12 rounded-2xl outline-none transition-all text-sm font-semibold text-vibrant-dark placeholder:text-vibrant-dark/30"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold text-vibrant-dark/40 uppercase tracking-widest ml-1">Correo Electrónico</label>
                                <div className="relative group">
                                    <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-vibrant-dark/20 group-focus-within:text-vibrant-main transition-colors" size={20} />
                                    <input
                                        type="email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        placeholder="correo@ejemplo.com"
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
                                        placeholder="Mínimo 6 caracteres"
                                        className="w-full bg-vibrant-dark/5 border-2 border-transparent focus:border-vibrant-main/20 focus:bg-white p-4 pl-12 rounded-2xl outline-none transition-all text-sm font-semibold text-vibrant-dark placeholder:text-vibrant-dark/30"
                                        required
                                        minLength={6}
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={isLoading}
                                className="w-full bg-vibrant-main hover:bg-vibrant-dark text-white p-4 rounded-2xl font-bold text-sm shadow-xl shadow-vibrant-main/20 transition-all transform hover:-translate-y-1 active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                {isLoading ? 'Creando cuenta...' : 'Crear mi Cuenta'}
                                {!isLoading && <ArrowRight size={18} />}
                            </button>
                        </form>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-vibrant-dark/5"></div>
                            </div>
                            <div className="relative flex justify-center text-[9px] uppercase tracking-widest text-vibrant-dark/20 font-black">
                                <span className="bg-white px-4 italic">¿Ya tienes cuenta?</span>
                            </div>
                        </div>

                        <Link
                            href="/login"
                            className="block w-full text-center bg-vibrant-light hover:bg-white text-vibrant-dark border-2 border-vibrant-dark/5 p-4 rounded-2xl font-bold text-sm transition-all shadow-sm hover:shadow-md active:scale-95"
                        >
                            Iniciar Sesión
                        </Link>
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
