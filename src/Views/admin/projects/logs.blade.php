@extends('layouts.main')

@section('title', 'Logs de Actividad - ' . $project['name'])

@section('content')
    <div class="container mx-auto p-6">
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold text-p-title">{{ $project['name'] }}</h1>
                <p class="text-p-muted">Registro de actividades y auditoría</p>
            </div>
            <div>
                <a href="/admin/projects/{{ $project['id'] }}/logs/export-csv"
                    class="btn-outline px-4 py-2 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="border-b border-white/10 mb-6">
            <nav class="flex gap-4">
                <a href="/admin/projects?edit={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">General</a>
                <a href="/admin/databases?project_id={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">Bases de Datos</a>
                <a href="/admin/api?project_id={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">API</a>
                <a href="#" class="px-4 py-2 border-b-2 border-primary font-semibold text-primary">Logs</a>
                <a href="/admin/projects/{{ $project['id'] }}/external-users" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">Usuarios Web</a>
            </nav>
        </div>

        <!-- Filtros -->
        <div class="glass-card p-4 rounded-lg mb-6">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-p-muted mb-1">Usuario</label>
                    <select name="user_id"
                        class="form-input border-white/10 bg-white/5 text-p-title rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        <option value="" class="text-black">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u['id'] }}" class="text-black" {{ ($filters['user_id'] == $u['id']) ? 'selected' : '' }}>
                                {{ $u['username'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-p-muted mb-1">Acción</label>
                    <select name="action"
                        class="form-input border-white/10 bg-white/5 text-p-title rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        <option value="" class="text-black">Todas</option>
                        @foreach($actions as $a)
                            <option value="{{ $a['action'] }}" class="text-black" {{ ($filters['action'] == $a['action']) ? 'selected' : '' }}>
                                {{ $a['action'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-p-muted mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                        class="form-input border-white/10 bg-white/5 text-p-title rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                </div>
                <div class="flex-grow">
                    <label class="block text-xs font-medium text-p-muted mb-1">Buscar en detalles</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ID, recurso..."
                        class="w-full form-input border-white/10 bg-white/5 text-p-title rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary placeholder-white/20">
                </div>
                <div>
                    <button type="submit"
                        class="btn-primary px-4 py-2 rounded text-sm shadow">Filtrar</button>
                </div>
                @if(array_filter($filters))
                    <div>
                        <a href="/admin/projects/{{ $project['id'] }}/logs"
                            class="text-p-muted text-sm hover:text-white underline">Limpiar</a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Tabla Logs -->
        <div class="glass-card rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-black/20 text-p-muted border-b border-white/5">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha/Hora</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Acción</th>
                        <th class="px-4 py-3 font-medium">Recurso</th>
                        <th class="px-4 py-3 font-medium">Detalles</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($logs as $log)
                                    <?php
                        $details = json_decode($log['details'], true);
                        $resource = $details['resource'] ?? '-';
                        $resourceId = $details['resource_id'] ?? '';

                        $actionColor = 'text-p-title';
                        if (strpos($log['action'], 'delete') !== false)
                            $actionColor = 'text-red-400 font-medium';
                        if (strpos($log['action'], 'create') !== false)
                            $actionColor = 'text-emerald-400 font-medium';
                        if (strpos($log['action'], 'login') !== false)
                            $actionColor = 'text-blue-400';
                        if (strpos($log['action'], 'failed') !== false)
                            $actionColor = 'text-red-500 font-bold';
                                    ?>
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-p-muted">
                                            {{ date('d M H:i:s', strtotime($log['created_at'])) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($log['username'])
                                                <span class="font-medium text-p-title">{{ $log['username'] }}</span>
                                                <span class="block text-xs text-p-muted">{{ $log['email'] }}</span>
                                            @else
                                                <span class="text-p-muted opacity-50">Sistema / Anon</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 {{ $actionColor }}">
                                            {{ $log['action'] }}
                                        </td>
                                        <td class="px-4 py-3 text-p-muted">
                                            {{ $resource }}
                                            @if($resourceId) <span
                                            class="bg-white/10 text-p-title px-1 rounded text-xs ml-1">#{{ $resourceId }}</span> @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(isset($details['details']) && $details['details'])
                                                <code
                                                    class="text-xs bg-black/30 text-p-muted p-1 rounded border border-white/10 overflow-x-auto block max-w-xs scrollbar-thin">{{ json_encode($details['details']) }}</code>
                                            @elseif(isset($details['reason']))
                                                <span class="text-red-400 text-xs">{{ $details['reason'] }}</span>
                                            @else
                                                <span class="text-p-muted opacity-30">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-p-muted text-xs font-mono opacity-70">
                                            {{ $log['ip_address'] }}
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-p-muted">
                                No hay registros de actividad recientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 bg-black/10 border-t border-white/5 text-xs text-p-muted text-center">
                Mostrando últimos 50 registros
            </div>
        </div>
    </div>
@endsection