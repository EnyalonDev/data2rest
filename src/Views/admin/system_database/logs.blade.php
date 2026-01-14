@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-purple-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-purple-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.logs') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            Logs del Sistema
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            Registro de todas las operaciones del módulo de administración del sistema
        </p>
    </header>

    <!-- Filters -->
    <section class="mb-12 glass-card border-white/5 bg-white/[0.02]">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </div>
            <h3 class="text-xs font-black text-p-title uppercase tracking-widest">Filtros</h3>
        </div>

        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
            <!-- Date From -->
            <div class="md:col-span-3">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">Fecha
                    Desde</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                    class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs text-p-muted">
            </div>

            <!-- Date To -->
            <div class="md:col-span-3">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">Fecha
                    Hasta</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                    class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs text-p-muted">
            </div>

            <!-- Search -->
            <div class="md:col-span-5">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">Buscar</label>
                <div class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                        placeholder="Buscar en logs..."
                        class="form-input !bg-black/40 !border-white/10 !py-2.5 !pl-9 text-xs">
                    <svg class="absolute left-3 top-2.5 text-p-muted" xmlns="http://www.w3.org/2000/svg" width="14"
                        height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
            </div>

            <!-- Submit -->
            <div class="md:col-span-1">
                <button type="submit" class="btn-primary w-full !h-[38px] !px-0 flex items-center justify-center"
                    title="Filtrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
            </div>
        </form>
    </section>

    <!-- Actions -->
    <div class="mb-8 flex gap-4">
        <a href="<?= $baseUrl ?>admin/system-database/logs/export" class="btn-secondary flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            {{ \App\Core\Lang::get('system_database.export_results') }}
        </a>
    </div>

    <!-- Logs Table -->
    <div class="glass-card !p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Acción</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Usuario</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Detalles</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($logs as $log)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                @php
                                    $icon = '🔧';
                                    $color = 'text-purple-500';
                                    if (strpos($log['action'], 'BACKUP') !== false) {
                                        $icon = '💾';
                                        $color = 'text-emerald-500';
                                    } elseif (strpos($log['action'], 'QUERY') !== false) {
                                        $icon = '⚡';
                                        $color = 'text-blue-500';
                                    } elseif (strpos($log['action'], 'OPTIMIZED') !== false) {
                                        $icon = '⚙️';
                                        $color = 'text-amber-500';
                                    } elseif (strpos($log['action'], 'CLEANED') !== false) {
                                        $icon = '🧹';
                                        $color = 'text-red-500';
                                    }
                                @endphp
                                <span class="text-lg">{{ $icon }}</span>
                                <span class="text-[10px] font-black uppercase tracking-widest {{ $color }}">
                                    {{ str_replace('_', ' ', $log['action']) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-p-title">{{ $log['username'] ?? 'System' }}</span>
                                <span
                                    class="text-[9px] text-p-muted font-black uppercase tracking-tighter">{{ $log['ip_address'] ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="max-w-md truncate text-[11px] text-p-muted font-medium hover:text-white transition-colors cursor-help"
                                title="{{ $log['details'] }}">
                                {{ $log['details'] }}
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <span class="text-[10px] font-black text-p-muted uppercase italic">
                                {{ date('M d, H:i', strtotime($log['created_at'])) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                                No se encontraron logs del sistema
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Clear Logs Form -->
    <div class="mt-12 glass-card !p-6 border-red-500/20">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-500/10 rounded-lg flex items-center justify-center text-red-500 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-black text-red-500 mb-2">Limpiar Logs Antiguos</h4>
                <p class="text-[11px] text-p-muted font-medium mb-4">
                    Eliminar logs del sistema más antiguos que el número de días especificado.
                </p>
                <form method="POST" action="<?= $baseUrl ?>admin/system-database/logs/clear" class="flex gap-3"
                    onsubmit="return confirm('¿Eliminar logs antiguos del sistema?');">
                    <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
                    <input type="number" name="days" value="90" min="1" max="365"
                        class="form-input !bg-black/40 !border-white/10 !py-2 text-xs w-32">
                    <button type="submit" class="btn-primary !bg-red-500 hover:!bg-red-600">
                        Limpiar Logs
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection