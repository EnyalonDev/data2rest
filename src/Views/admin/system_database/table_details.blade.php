@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-blue-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-blue-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.tables') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            {{ $tableName }}
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            {{ $recordCount }} registros en total
        </p>
    </header>

    <!-- Table Structure -->
    <div class="mb-12">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> Estructura de la Tabla
        </h2>
        <div class="glass-card !p-0 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-white/10">
                        <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Columna</th>
                        <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Tipo</th>
                        <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Nulo</th>
                        <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Default</th>
                        <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">PK</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($columns as $column)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-xs font-black text-p-title">{{ $column['name'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-0.5 bg-blue-500/20 text-blue-500 rounded text-[9px] font-black uppercase tracking-widest border border-blue-500/30">
                                    {{ $column['type'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-black text-p-muted">
                                    {{ $column['notnull'] ? 'NO' : 'YES' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-mono text-p-muted">
                                    {{ $column['dflt_value'] ?? 'NULL' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($column['pk'])
                                    <span
                                        class="px-2 py-0.5 bg-emerald-500/20 text-emerald-500 rounded text-[9px] font-black uppercase tracking-widest border border-emerald-500/30">
                                        YES
                                    </span>
                                @else
                                    <span class="text-[10px] font-black text-p-muted">NO</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Indexes -->
    @if(!empty($indexes))
        <div class="mb-12">
            <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                <span class="w-8 h-[1px] bg-slate-800"></span> Índices
            </h2>
            <div class="glass-card !p-6">
                <div class="space-y-3">
                    @foreach($indexes as $index)
                        <div class="flex items-center justify-between bg-white/5 p-4 rounded-xl border border-white/5">
                            <span class="text-xs font-black text-p-title">{{ $index['name'] }}</span>
                            <span
                                class="px-2 py-0.5 bg-{{ $index['unique'] ? 'purple' : 'blue' }}-500/20 text-{{ $index['unique'] ? 'purple' : 'blue' }}-500 rounded text-[9px] font-black uppercase tracking-widest border border-{{ $index['unique'] ? 'purple' : 'blue' }}-500/30">
                                {{ $index['unique'] ? 'UNIQUE' : 'INDEX' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Sample Data -->
    @if(!empty($sampleData))
        <div class="mb-12">
            <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                <span class="w-8 h-[1px] bg-slate-800"></span> Datos de Muestra (Primeros 10 registros)
            </h2>
            <div class="glass-card !p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                @foreach(array_keys($sampleData[0]) as $column)
                                    <th
                                        class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest whitespace-nowrap">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($sampleData as $row)
                                <tr class="hover:bg-white/5 transition-colors">
                                    @foreach($row as $value)
                                        <td class="px-6 py-4 text-xs text-p-muted font-mono whitespace-nowrap">
                                            {{ is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Back Button -->
    <div class="text-center">
        <a href="<?= $baseUrl ?>admin/system-database/tables" class="btn-secondary inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Volver a Tablas
        </a>
    </div>
@endsection