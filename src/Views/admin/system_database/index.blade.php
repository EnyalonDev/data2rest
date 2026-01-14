@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-red-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-red-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.title') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            {{ \App\Core\Lang::get('system_database.dashboard') }}
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            Administración completa de la base de datos del sistema. Solo accesible para Super Admin.
        </p>
    </header>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-red-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $dbSize }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('system_database.database_size') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $totalTables }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('system_database.total_tables') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $totalRecords }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('system_database.total_records') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-purple-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $diskUsedPercent }}%</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('system_database.disk_space') }}</span>
        </div>
    </div>

    <!-- Quick Actions -->
    <section class="mb-12 glass-card border-white/5 bg-white/[0.02]">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-red-500/10 rounded-lg flex items-center justify-center text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M12.89 1.45l8 4A2 2 0 0 1 22 7.24v9.53a2 2 0 0 1-1.11 1.79l-8 4a2 2 0 0 1-1.79 0l-8-4a2 2 0 0 1-1.1-1.8V7.24a2 2 0 0 1 1.11-1.79l8-4a2 2 0 0 1 1.78 0z">
                    </path>
                    <polyline points="2.32 6.16 12 11 21.68 6.16"></polyline>
                    <line x1="12" y1="22.76" x2="12" y2="11"></line>
                </svg>
            </div>
            <h3 class="text-xs font-black text-p-title uppercase tracking-widest">Acciones Rápidas</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- View Tables -->
            <a href="<?= $baseUrl ?>admin/system-database/tables"
                class="glass-card !p-6 hover:border-blue-500/50 transition-all group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-p-title mb-1">{{ \App\Core\Lang::get('system_database.tables') }}
                        </h4>
                        <p class="text-[10px] text-p-muted font-medium">Ver estructura del sistema</p>
                    </div>
                </div>
            </a>

            <!-- SQL Executor -->
            <a href="<?= $baseUrl ?>admin/system-database/query-executor"
                class="glass-card !p-6 hover:border-purple-500/50 transition-all group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-p-title mb-1">
                            {{ \App\Core\Lang::get('system_database.query_executor') }}
                        </h4>
                        <p class="text-[10px] text-p-muted font-medium">Ejecutar consultas SQL</p>
                    </div>
                </div>
            </a>

            <!-- Backups -->
            <a href="<?= $baseUrl ?>admin/system-database/backups"
                class="glass-card !p-6 hover:border-emerald-500/50 transition-all group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500 group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-p-title mb-1">
                            {{ \App\Core\Lang::get('system_database.backups') }}
                        </h4>
                        <p class="text-[10px] text-p-muted font-medium">Gestionar copias de seguridad</p>
                    </div>
                </div>
            </a>

            <!-- API Explorer -->
            <a href="<?= $baseUrl ?>admin/system-database/api-explorer"
                class="glass-card !p-6 hover:border-primary/50 transition-all group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-p-title mb-1">API Explorer</h4>
                        <p class="text-[10px] text-p-muted font-medium">Probar endpoints del sistema</p>
                    </div>
                </div>
            </a>
        </div>
    </section>

    <!-- Last Backup & System Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Last Backup -->
        <div>
            <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('system_database.last_backup') }}
            </h2>
            <div class="glass-card !p-6">
                @if($lastBackup)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Archivo</span>
                            <span class="text-xs font-black text-p-title">{{ $lastBackup['filename'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Tamaño</span>
                            <span class="text-xs font-black text-p-title">{{ $lastBackup['size'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Fecha</span>
                            <span class="text-xs font-black text-p-title italic">{{ $lastBackup['date'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Tipo</span>
                            <span
                                class="px-2 py-0.5 bg-{{ $lastBackup['type'] == 'manual' ? 'blue' : 'emerald' }}-500/20 text-{{ $lastBackup['type'] == 'manual' ? 'blue' : 'emerald' }}-500 rounded text-[9px] font-black uppercase tracking-widest border border-{{ $lastBackup['type'] == 'manual' ? 'blue' : 'emerald' }}-500/30">
                                {{ \App\Core\Lang::get('system_database.' . $lastBackup['type']) }}
                            </span>
                        </div>
                    </div>
                @else
                    <p class="text-[10px] font-black text-p-muted uppercase text-center py-8">
                        {{ \App\Core\Lang::get('system_database.no_backups') }}
                    </p>
                @endif
            </div>
        </div>

        <!-- System Operations -->
        <div>
            <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                <span class="w-8 h-[1px] bg-slate-800"></span> Operaciones del Sistema
            </h2>
            <div class="glass-card !p-6 space-y-4">
                <!-- Optimize Database -->
                <form method="POST" action="<?= $baseUrl ?>admin/system-database/optimize"
                    onsubmit="return confirm('¿Optimizar la base de datos del sistema?');">
                    <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
                    <button type="submit"
                        class="w-full glass-card !p-4 hover:border-blue-500/50 transition-all group text-left">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center text-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path
                                            d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m18.2-5.2l-4.2 4.2m0 6l4.2 4.2">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-p-title">
                                        {{ \App\Core\Lang::get('system_database.optimize') }}
                                    </h4>
                                    <p class="text-[9px] text-p-muted font-medium">VACUUM + ANALYZE</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-p-muted group-hover:text-blue-500 transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </div>
                    </button>
                </form>

                <!-- Clean Old Data -->
                <form method="POST" action="<?= $baseUrl ?>admin/system-database/clean"
                    onsubmit="return confirm('¿Limpiar datos antiguos según configuración de retención?');">
                    <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
                    <button type="submit"
                        class="w-full glass-card !p-4 hover:border-amber-500/50 transition-all group text-left">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-amber-500/10 rounded-lg flex items-center justify-center text-amber-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-p-title">
                                        {{ \App\Core\Lang::get('system_database.clean_old_data') }}
                                    </h4>
                                    <p class="text-[9px] text-p-muted font-medium">Logs, auditoría, papelera</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-p-muted group-hover:text-amber-500 transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </div>
                    </button>
                </form>

                <!-- View Logs -->
                <a href="<?= $baseUrl ?>admin/system-database/logs"
                    class="block w-full glass-card !p-4 hover:border-purple-500/50 transition-all group">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-purple-500/10 rounded-lg flex items-center justify-center text-purple-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-p-title">
                                    {{ \App\Core\Lang::get('system_database.logs') }}
                                </h4>
                                <p class="text-[9px] text-p-muted font-medium">Ver registro de operaciones</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-p-muted group-hover:text-purple-500 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Disk Space Info -->
    <div class="mt-12">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> Espacio en Disco
        </h2>
        <div class="glass-card !p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Usado</span>
                <span class="text-xs font-black text-p-title">{{ $diskUsedPercent }}%</span>
            </div>
            <div class="w-full bg-white/5 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full transition-all"
                    style="width: {{ $diskUsedPercent }}%"></div>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[9px] text-p-muted font-medium">Libre: {{ $diskFree }}</span>
                <span class="text-[9px] text-p-muted font-medium">Total: {{ $diskTotal }}</span>
            </div>
        </div>
    </div>
@endsection