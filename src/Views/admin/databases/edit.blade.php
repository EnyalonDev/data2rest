@extends('layouts.main')

@section('title', 'Configurar Visibilidad - ' . $database['name'])

@section('content')
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
            {{ $database['name'] }}
        </h1>
        <p class="text-p-muted font-medium tracking-tight">Selecciona las tablas que deseas ocultar para usuarios sin permisos de administrador.</p>
    </header>

    <div class="max-w-4xl">
        <form action="{{ $baseUrl }}admin/databases/config/save" method="POST" class="glass-card">
            {!! $csrf_field !!}
            <input type="hidden" name="id" value="{{ $database['id'] }}">

            <div class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                    <h2 class="text-xl font-bold text-p-title uppercase italic tracking-tighter flex items-center gap-3">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Gestión de Visibilidad
                    </h2>
                    <span class="text-[10px] font-black text-p-muted uppercase tracking-widest bg-white/5 px-3 py-1 rounded-full">
                        {{ count($tables) }} Tablas Totales
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($tables as $table)
                        <label class="flex items-center justify-between p-4 bg-p-bg dark:bg-white/5 rounded-2xl border border-transparent hover:border-primary/20 transition-all cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-p-title block">{{ $table }}</span>
                                    @if(in_array($table, $config['hidden_tables'] ?? []))
                                        <span class="text-[9px] text-red-400 font-black uppercase tracking-widest">Oculta</span>
                                    @else
                                        <span class="text-[9px] text-emerald-400 font-black uppercase tracking-widest">Visible</span>
                                    @endif
                                </div>
                            </div>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="hidden_tables[]" value="{{ $table }}" class="sr-only peer" {{ in_array($table, $config['hidden_tables'] ?? []) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="pt-6 border-t border-white/5 flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="btn-primary !py-4 px-12 font-black uppercase tracking-widest text-xs flex-1 sm:flex-none">
                        Guardar Configuración
                    </button>
                    <a href="{{ $baseUrl }}admin/databases" class="btn-primary !bg-slate-800 !text-slate-300 !py-4 px-12 font-black uppercase tracking-widest text-xs flex-1 sm:flex-none text-center">
                        Cancelar
                    </a>
                </div>

                <div class="bg-primary/5 border border-primary/20 p-4 rounded-2xl">
                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Nota Informativa
                    </p>
                    <p class="text-xs text-p-muted mt-2">
                        Las tablas marcadas como "Ocultas" no aparecerán en la lista de tablas para los usuarios del proyecto. Sin embargo, los Administradores de Sistema siempre tendrán acceso total a todas las tablas.
                    </p>
                </div>
            </div>
        </form>
    </div>
@endsection
