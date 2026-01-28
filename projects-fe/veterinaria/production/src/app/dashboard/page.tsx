
'use client';

import { useAuthStore } from '@/stores/authStore';
import { Calendar, Users, Package, Activity, Plus } from 'lucide-react';

export default function DashboardPage() {
    const { user } = useAuthStore();

    const stats = [
        { label: 'Pacientes Hoy', value: '12', icon: Activity, color: 'bg-blue-500' },
        { label: 'Citas Pendientes', value: '5', icon: Calendar, color: 'bg-vibrant-main' },
        { label: 'Clientes Nuevos', value: '3', icon: Users, color: 'bg-purple-500' },
        { label: 'Stock Bajo', value: '2', icon: Package, color: 'bg-red-500' },
    ];

    return (
        <div className="space-y-10 animate-fade-in">
            <header className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 className="text-4xl font-extrabold text-vibrant-dark tracking-tight leading-none mb-3">
                        ¡Hola, {user?.name?.split(' ')[0] || 'Doc'}! 👋
                    </h1>
                    <p className="text-vibrant-dark/60 font-medium">
                        Esto es lo que está pasando en Mundo Jácome's hoy.
                    </p>
                </div>

                <button className="flex items-center gap-2 bg-vibrant-main text-white px-6 py-3.5 rounded-2xl font-bold shadow-xl shadow-vibrant-main/30 hover:bg-vibrant-dark transition-all transform hover:-translate-y-1 active:scale-95">
                    <Plus size={20} strokeWidth={3} />
                    Nueva Consulta
                </button>
            </header>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {stats.map((stat, idx) => (
                    <div key={idx} className="bg-white p-6 rounded-[2rem] border border-vibrant-dark/5 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex items-start justify-between mb-4">
                            <div className={`${stat.color} p-3 rounded-2xl text-white shadow-lg`}>
                                <stat.icon size={24} />
                            </div>
                            <span className="text-3xl font-black text-vibrant-dark">{stat.value}</span>
                        </div>
                        <p className="text-vibrant-dark/40 font-bold uppercase tracking-widest text-[10px]">
                            {stat.label}
                        </p>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
                <div className="bg-white p-8 rounded-[2.5rem] border border-vibrant-dark/5 shadow-sm min-h-[300px]">
                    <h3 className="text-xl font-extrabold text-vibrant-dark mb-6 flex items-center gap-3">
                        <div className="w-2 h-6 bg-vibrant-accent rounded-full"></div>
                        Próximas Citas
                    </h3>
                    <div className="flex flex-col items-center justify-center h-48 text-vibrant-dark/20 text-center">
                        <Calendar size={48} className="mb-4 opacity-10" />
                        <p className="font-bold text-sm">No hay más citas programadas para hoy</p>
                    </div>
                </div>

                <div className="bg-vibrant-dark p-8 rounded-[2.5rem] shadow-2xl min-h-[300px] text-white overflow-hidden relative">
                    <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                    <h3 className="text-xl font-extrabold mb-6 flex items-center gap-3">
                        <div className="w-2 h-6 bg-vibrant-main rounded-full"></div>
                        Estado del Sistema
                    </h3>
                    <ul className="space-y-4 relative z-10">
                        <li className="flex items-center gap-4 text-sm font-medium bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div className="w-2 h-2 bg-green-500 rounded-full shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                            API Conectada (d2r.nestorovallos.com)
                        </li>
                        <li className="flex items-center gap-4 text-sm font-medium bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div className="w-2 h-2 bg-green-500 rounded-full shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                            Google Auth Activo (ID Proyect: 2)
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
