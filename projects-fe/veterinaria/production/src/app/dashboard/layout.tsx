
'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import DebugPanel from '@/components/DebugPanel';

export default function DashboardLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const router = useRouter();
    const { isAuthenticated, user, logout } = useAuthStore();
    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    useEffect(() => {
        if (isHydrated && !isAuthenticated) {
            console.log('Dashboard redirecting: not authenticated');
            router.push('/login');
        }
    }, [isHydrated, isAuthenticated, router]);

    // Prevent flash of unauthenticated content
    if (!isHydrated || !isAuthenticated) {
        return (
            <div className="min-h-screen bg-vibrant-light flex items-center justify-center">
                <div className="w-10 h-10 border-4 border-vibrant-main border-t-transparent rounded-full animate-spin"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-vibrant-light">
            <nav className="bg-white border-b border-vibrant-dark/5 p-4 flex justify-between items-center px-8 shadow-sm">
                <div className="font-extrabold text-vibrant-main text-xl tracking-tight leading-none flex flex-col">
                    <span>MUNDO JÁCOME'S</span>
                    <span className="text-[8px] uppercase tracking-[0.4em] text-vibrant-dark/60">DASHBOARD</span>
                </div>

                <div className="flex items-center gap-6">
                    <div className="flex flex-col items-end">
                        <span className="text-sm font-bold text-vibrant-dark">{user?.name || 'Veterinario'}</span>
                        <span className="text-[10px] text-vibrant-dark/40 font-medium">Panel de Gestión</span>
                    </div>
                    <button
                        onClick={logout}
                        className="bg-vibrant-dark/5 hover:bg-red-50 text-vibrant-dark/40 hover:text-red-500 p-2.5 rounded-xl transition-all border border-transparent hover:border-red-100"
                        title="Cerrar Sesión"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </button>
                </div>
            </nav>

            <main className="p-8 max-w-7xl mx-auto">
                {children}
            </main>
            <div className="fixed bottom-0 left-0 right-0 z-50">
                <DebugPanel />
            </div>
        </div>
    );
}
