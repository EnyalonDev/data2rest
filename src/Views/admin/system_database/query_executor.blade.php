@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-purple-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-purple-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.query_executor') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            Ejecutor SQL
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            Ejecuta consultas SQL directamente en la base de datos del sistema
        </p>
    </header>

    @if(isset($needsConfirmation) && $needsConfirmation)
        <!-- Dangerous Query Warning -->
        <div class="mb-8 glass-card !p-6 border-red-500/50 bg-red-500/5">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center text-red-500 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                        </path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-black text-red-500 mb-2">⚠️
                        {{ \App\Core\Lang::get('system_database.dangerous_query') }}
                    </h4>
                    <p class="text-[11px] text-p-muted font-medium mb-4">
                        Esta consulta contiene operaciones potencialmente peligrosas (DROP, TRUNCATE, etc.).
                        ¿Estás seguro de que deseas ejecutarla?
                    </p>
                    <div class="flex gap-3">
                        <form method="POST" action="<?= $baseUrl ?>admin/system-database/execute-query" class="inline">
                            <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
                            <input type="hidden" name="query" value="<?= htmlspecialchars($query ?? '') ?>">
                            <input type="hidden" name="confirmed" value="1">
                            <button type="submit" class="btn-primary !bg-red-500 hover:!bg-red-600">
                                Sí, ejecutar consulta
                            </button>
                        </form>
                        <a href="<?= $baseUrl ?>admin/system-database/query-executor" class="btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Query Form -->
    <div class="glass-card mb-8">
        <form method="POST" action="<?= $baseUrl ?>admin/system-database/execute-query">
            <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">

            <div class="mb-6">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">
                    Consulta SQL
                </label>
                <textarea name="query" rows="10" class="form-input !bg-black/40 !border-white/10 font-mono text-sm"
                    placeholder="SELECT * FROM users LIMIT 10;" required><?= htmlspecialchars($query ?? '') ?></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    {{ \App\Core\Lang::get('system_database.execute_query') }}
                </button>
                <button type="button" onclick="document.querySelector('textarea[name=query]').value = ''"
                    class="btn-secondary">
                    Limpiar
                </button>
            </div>
        </form>
    </div>

    @if(isset($error))
        <!-- Error Message -->
        <div class="glass-card !p-6 border-red-500/50 bg-red-500/5 mb-8">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center text-red-500 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-red-500 mb-2">Error en la consulta</h4>
                    <p class="text-[11px] text-p-muted font-mono">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(isset($success) && $success)
        <!-- Success Message -->
        <div class="glass-card !p-6 border-emerald-500/50 bg-emerald-500/5 mb-8">
            <div class="flex items-start gap-4">
                <div
                    class="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center text-emerald-500 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-emerald-500 mb-2">
                        {{ \App\Core\Lang::get('system_database.query_executed') }}
                    </h4>
                    <p class="text-[11px] text-p-muted font-medium">
                        Filas afectadas: <span class="text-emerald-500 font-black">{{ $affectedRows ?? 0 }}</span>
                    </p>
                </div>
            </div>
        </div>

        @if(isset($results) && !empty($results))
            <!-- Results Table -->
            <div class="glass-card !p-0 overflow-hidden">
                <div class="bg-white/5 px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h3 class="text-xs font-black text-p-title uppercase tracking-widest">
                        {{ \App\Core\Lang::get('system_database.query_results') }}
                    </h3>
                    <span class="text-[10px] font-black text-p-muted uppercase">
                        {{ count($results) }} {{ count($results) == 1 ? 'resultado' : 'resultados' }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                @if(!empty($results[0]))
                                    @foreach(array_keys($results[0]) as $column)
                                        <th
                                            class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest whitespace-nowrap">
                                            {{ $column }}
                                        </th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($results as $row)
                                <tr class="hover:bg-white/5 transition-colors">
                                    @foreach($row as $value)
                                        <td class="px-6 py-4 text-xs text-p-muted font-mono whitespace-nowrap">
                                            {{ is_null($value) ? 'NULL' : $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <!-- Quick Examples -->
    <div class="mt-12">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> Ejemplos de Consultas
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass-card !p-6">
                <h4 class="text-xs font-black text-p-title mb-3">Ver usuarios del sistema</h4>
                <code
                    class="block text-[11px] text-blue-400 font-mono bg-black/40 p-3 rounded">SELECT * FROM users LIMIT 10;</code>
            </div>
            <div class="glass-card !p-6">
                <h4 class="text-xs font-black text-p-title mb-3">Contar registros de logs</h4>
                <code
                    class="block text-[11px] text-blue-400 font-mono bg-black/40 p-3 rounded">SELECT COUNT(*) as total FROM logs;</code>
            </div>
            <div class="glass-card !p-6">
                <h4 class="text-xs font-black text-p-title mb-3">Ver configuración del sistema</h4>
                <code
                    class="block text-[11px] text-blue-400 font-mono bg-black/40 p-3 rounded">SELECT * FROM system_settings;</code>
            </div>
            <div class="glass-card !p-6">
                <h4 class="text-xs font-black text-p-title mb-3">Actividad reciente</h4>
                <code
                    class="block text-[11px] text-blue-400 font-mono bg-black/40 p-3 rounded">SELECT * FROM logs ORDER BY created_at DESC LIMIT 20;</code>
            </div>
        </div>
    </div>

    <!-- Warning Note -->
    <div class="mt-12 glass-card !p-6 border-amber-500/20">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-amber-500/10 rounded-lg flex items-center justify-center text-amber-500 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                    </path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-black text-amber-500 mb-2">Advertencia de Seguridad</h4>
                <ul class="text-[11px] text-p-muted space-y-1 font-medium">
                    <li>• Todas las consultas se ejecutan directamente en la base de datos del sistema</li>
                    <li>• Las operaciones destructivas (DROP, TRUNCATE) requieren confirmación adicional</li>
                    <li>• Todas las consultas ejecutadas se registran en los logs del sistema</li>
                    <li>• Crea un backup antes de ejecutar consultas que modifiquen datos críticos</li>
                </ul>
            </div>
        </div>
    </div>
@endsection