@extends('layouts.main')

@section('title', 'Logs de Actividad - ' . $project['name'])

@section('content')
    <div class="container mx-auto p-6">
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold">{{ $project['name'] }}</h1>
                <p class="text-gray-600">Registro de actividades y auditoría</p>
            </div>
            <div>
                <a href="/admin/projects/{{ $project['id'] }}/logs/export-csv"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 border border-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="border-b mb-6">
            <nav class="flex gap-4">
                <a href="/admin/projects?edit={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">General</a>
                <a href="/admin/databases?project_id={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">Bases de
                    Datos</a>
                <a href="/admin/api?project_id={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">API</a>
                <a href="#" class="px-4 py-2 border-b-2 border-blue-600 font-semibold text-blue-600">Logs</a>
                <a href="/admin/projects/{{ $project['id'] }}/external-users" class="px-4 py-2 hover:text-blue-600">Usuarios
                    Web</a>
            </nav>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Usuario</label>
                    <select name="user_id"
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u['id'] }}" {{ ($filters['user_id'] == $u['id']) ? 'selected' : '' }}>
                                {{ $u['username'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Acción</label>
                    <select name="action"
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        @foreach($actions as $a)
                            <option value="{{ $a['action'] }}" {{ ($filters['action'] == $a['action']) ? 'selected' : '' }}>
                                {{ $a['action'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex-grow">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Buscar en detalles</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ID, recurso..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Flitrar</button>
                </div>
                @if(array_filter($filters))
                    <div>
                        <a href="/admin/projects/{{ $project['id'] }}/logs"
                            class="text-gray-500 text-sm hover:underline">Limpiar</a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Tabla Logs -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 border-b">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha/Hora</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Acción</th>
                        <th class="px-4 py-3 font-medium">Recurso</th>
                        <th class="px-4 py-3 font-medium">Detalles</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                                    <?php
                        $details = json_decode($log['details'], true);
                        $resource = $details['resource'] ?? '-';
                        $resourceId = $details['resource_id'] ?? '';

                        $actionColor = 'text-gray-800';
                        if (strpos($log['action'], 'delete') !== false)
                            $actionColor = 'text-red-600 font-medium';
                        if (strpos($log['action'], 'create') !== false)
                            $actionColor = 'text-green-600 font-medium';
                        if (strpos($log['action'], 'login') !== false)
                            $actionColor = 'text-blue-600';
                        if (strpos($log['action'], 'failed') !== false)
                            $actionColor = 'text-red-600 font-bold';
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ date('d M H:i:s', strtotime($log['created_at'])) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($log['username'])
                                                <span class="font-medium text-gray-900">{{ $log['username'] }}</span>
                                                <span class="block text-xs text-gray-500">{{ $log['email'] }}</span>
                                            @else
                                                <span class="text-gray-400">Sistema / Anon</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 {{ $actionColor }}">
                                            {{ $log['action'] }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $resource }}
                                            @if($resourceId) <span
                                            class="bg-gray-100 text-gray-600 px-1 rounded text-xs">#{{ $resourceId }}</span> @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(isset($details['details']) && $details['details'])
                                                <code
                                                    class="text-xs bg-gray-50 p-1 rounded border overflow-x-auto block max-w-xs">{{ json_encode($details['details']) }}</code>
                                            @elseif(isset($details['reason']))
                                                <span class="text-red-500 text-xs">{{ $details['reason'] }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 text-xs font-mono">
                                            {{ $log['ip_address'] }}
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay registros de actividad recientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 bg-gray-50 border-t text-xs text-gray-500 text-center">
                Mostrando últimos 50 registros
            </div>
        </div>
    </div>
@endsection