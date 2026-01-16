@extends('layouts.main')

@section('title', \App\Core\Lang::get('tables.title') . ' - ' . $database['name'])

@section('content')
    <header class="mb-12 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
        <div class="flex flex-col gap-6 w-full">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-4xl md:text-5xl font-black text-p-title italic tracking-tighter uppercase leading-none">
                        {{ \App\Core\Lang::get('tables.title') }}
                    </h1>
                    @php
                        $dbType = strtolower($db_type ?? 'sqlite');
                        $badgeClass = match ($dbType) {
                            'mysql' => 'text-orange-500 bg-orange-500/10 border-orange-500/20',
                            'pgsql', 'postgresql' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                            default => 'text-slate-500 bg-slate-500/10 border-slate-500/20'
                        };
                        $label = match ($dbType) {
                            'mysql' => 'MySQL',
                            'pgsql', 'postgresql' => 'PG',
                            default => 'SQLite'
                        };
                    @endphp
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border {{ $badgeClass }}">
                        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                        <span class="text-[9px] font-black uppercase tracking-widest">{{ $label }}</span>
                    </span>
                </div>
                <p class="text-p-muted font-medium tracking-tight mt-2 text-sm md:text-base">
                    {!! str_replace(':name', '<b class="text-p-title">' . $database['name'] . '</b>', \App\Core\Lang::get('tables.subtitle')) !!}
                </p>
            </div>
            <div class="flex items-center gap-3 overflow-x-auto pb-2 no-scrollbar -mx-4 px-4 md:mx-0 md:px-0">
                <a href="{{ $baseUrl }}admin/databases" class="btn-primary !bg-slate-800 !text-slate-300 shrink-0">
                    &larr; <span class="hidden sm:inline">{{ \App\Core\Lang::get('common.back') }}</span>
                </a>
                @if(\App\Core\Auth::hasPermission('module:databases.edit_table'))
                    <a href="{{ $baseUrl }}admin/databases/sync?id={{ $database['id'] }}"
                        class="inline-flex items-center gap-2 group text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/5 px-4 py-2 rounded-lg border border-emerald-500/20 hover:bg-emerald-500/10 transition-all shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        {{ \App\Core\Lang::get('tables.sync') }}
                    </a>
                @endif
                @if(\App\Core\Auth::hasPermission('module:databases.create_db'))
                    <a href="{{ $baseUrl }}admin/databases/export?id={{ $database['id'] }}"
                        class="inline-flex items-center gap-2 group text-[10px] font-black text-amber-400 uppercase tracking-widest bg-amber-500/5 px-4 py-2 rounded-lg border border-amber-500/20 hover:bg-amber-500/10 transition-all font-mono shrink-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        SQL
                    </a>
                @endif
                <a href="{{ $baseUrl }}admin/api/docs?db_id={{ $database['id'] }}"
                    class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all shrink-0 whitespace-nowrap">API
                    &rarr;</a>
            </div>
        </div>
        @if(\App\Core\Auth::hasPermission('module:databases.create_table'))
            <div class="glass-card !p-0 border-primary/20 bg-primary/5 w-full lg:w-[450px] shrink-0 overflow-hidden">
                <!-- Tab Headers -->
                <div class="flex border-b border-primary/10">
                    <button type="button" onclick="switchCreateTableTab('simple')" id="create-tab-simple"
                        class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest transition-all bg-primary/10 text-primary">
                        {{ \App\Core\Lang::get('tables.simple_mode') }}
                    </button>
                    <button type="button" onclick="switchCreateTableTab('sql')" id="create-tab-sql"
                        class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest transition-all text-p-muted hover:bg-white/5">
                        {{ \App\Core\Lang::get('tables.sql_mode') }}
                    </button>
                </div>

                <div class="p-5">
                    <!-- Simple Mode Form -->
                    <div id="create-form-simple">
                        <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-4">
                            {{ \App\Core\Lang::get('tables.init_table') }}
                        </h2>
                        <form action="{{ $baseUrl }}admin/databases/table/create" method="POST" class="flex gap-3">
                            {!! $csrf_field !!}
                            <input type="hidden" name="db_id" value="{{ $database['id'] }}">
                            <input type="text" name="table_name"
                                placeholder="{{ \App\Core\Lang::get('tables.table_placeholder') }}" required
                                class="form-input !py-3 !px-4 text-sm flex-1">
                            <button type="submit"
                                class="btn-primary !py-3 px-6 font-black uppercase tracking-widest text-[10px] shrink-0">
                                {{ \App\Core\Lang::get('tables.create') }}
                            </button>
                        </form>
                    </div>

                    <!-- SQL Mode Form -->
                    <div id="create-form-sql" class="hidden">
                        <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-4">
                            {{ \App\Core\Lang::get('tables.create_sql') }}
                        </h2>
                        <form action="{{ $baseUrl }}admin/databases/table/create-sql" method="POST" class="space-y-4">
                            {!! $csrf_field !!}
                            <input type="hidden" name="db_id" value="{{ $database['id'] }}">
                            <div class="space-y-2">
                                <textarea name="sql_code" rows="4" required
                                    class="form-input font-mono text-xs resize-y !bg-black/40" placeholder="CREATE TABLE usuarios (
                                                nombre TEXT,
                                                email TEXT UNIQUE
                                            )"></textarea>
                                <p class="text-[9px] text-p-muted italic italic leading-tight">
                                    {{ \App\Core\Lang::get('tables.sql_help') }}
                                </p>
                            </div>
                            <button type="submit"
                                class="btn-primary w-full !py-3 font-black uppercase tracking-widest text-[10px]">
                                {{ \App\Core\Lang::get('tables.create_sql') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($tables as $tableName => $count)
            <div class="glass-card group hover:scale-[1.02] hover:shadow-2xl hover:shadow-primary/5">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center group-hover:text-primary transition-colors text-p-muted">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-primary/10 text-primary px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-primary/20">
                            {{ $count }} {{ \App\Core\Lang::get('dashboard.stats.records') }}
                        </div>
                        <div class="flex gap-1 group-hover:opacity-100 opacity-20 transition-opacity">
                            @if(\App\Core\Auth::hasPermission('module:databases.edit_table'))
                                <a href="{{ $baseUrl }}admin/databases/fields?db_id={{ $database['id'] }}&table={{ $tableName }}"
                                    class="p-2 text-p-muted hover:text-primary" title="{{ \App\Core\Lang::get('common.edit') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                    </svg>
                                </a>
                            @endif
                            @if(\App\Core\Auth::hasPermission('module:databases.drop_table'))
                                <button onclick="confirmDeleteTable('{{ addslashes($tableName) }}')"
                                    class="p-2 text-p-muted hover:text-red-500" title="{{ \App\Core\Lang::get('common.delete') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-p-title mb-6 uppercase tracking-tight">
                    {{ $tableName }}
                </h3>
                <div class="flex flex-col gap-3">
                    <a href="{{ $baseUrl }}admin/crud/list?db_id={{ $database['id'] }}&table={{ $tableName }}"
                        class="btn-primary text-center font-bold italic tracking-wider !py-3">{{ \App\Core\Lang::get('tables.enter') }}</a>

                    <!-- Import/Export Section -->
                    <div class="flex gap-2">
                        <!-- Export Dropdown -->
                        @if(\App\Core\Auth::hasPermission('module:databases.export_data'))
                            <div class="relative group/export flex-1">
                                <button onclick="toggleExportMenu('{{ $tableName }}')"
                                    class="w-full px-3 py-2 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-500/20 flex items-center justify-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Exportar
                                </button>
                                <div id="export-menu-{{ $tableName }}"
                                    class="hidden absolute bottom-full mb-2 left-0 w-full bg-bg-glass dark:bg-slate-800 border border-glass-border rounded-xl shadow-2xl overflow-hidden z-50">
                                    <a href="{{ $baseUrl }}admin/databases/table/export-sql?db_id={{ $database['id'] }}&table={{ $tableName }}"
                                        class="block px-4 py-3 text-[10px] font-black uppercase tracking-widest text-p-muted hover:bg-primary/10 hover:text-primary transition-all border-b border-glass-border">
                                        📄 SQL
                                    </a>
                                    <a href="{{ $baseUrl }}admin/databases/table/export-excel?db_id={{ $database['id'] }}&table={{ $tableName }}"
                                        class="block px-4 py-3 text-[10px] font-black uppercase tracking-widest text-p-muted hover:bg-primary/10 hover:text-primary transition-all border-b border-glass-border">
                                        📊 Excel
                                    </a>
                                    <a href="{{ $baseUrl }}admin/databases/table/export-csv?db_id={{ $database['id'] }}&table={{ $tableName }}"
                                        class="block px-4 py-3 text-[10px] font-black uppercase tracking-widest text-p-muted hover:bg-primary/10 hover:text-primary transition-all">
                                        📋 CSV
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Import Button -->
                        @if(\App\Core\Auth::hasPermission('module:databases.import_data'))
                            <button onclick="openImportModal('{{ $tableName }}', '{{ $database['id'] }}')"
                                class="flex-1 px-3 py-2 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all border border-amber-500/20 flex items-center justify-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Importar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDeleteTable(table) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('tables.delete_confirm_title')) !!}',
                message: '{!! addslashes(\App\Core\Lang::get('tables.delete_confirm_msg')) !!}'.replace(':table', table),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('tables.delete_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/databases/table/delete?db_id={{ $database['id'] }}&table=${table}`;
                }
            });
        }

        function toggleExportMenu(tableName) {
            const menu = document.getElementById(`export-menu-${tableName}`);
            // Close all other menus
            document.querySelectorAll('[id^="export-menu-"]').forEach(m => {
                if (m.id !== `export-menu-${tableName}`) {
                    m.classList.add('hidden');
                }
            });
            menu.classList.toggle('hidden');
        }

        // Close export menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[onclick^="toggleExportMenu"]') && !e.target.closest('[id^="export-menu-"]')) {
                document.querySelectorAll('[id^="export-menu-"]').forEach(m => m.classList.add('hidden'));
            }
        });

        function openImportModal(tableName, dbId) {
            const modalHTML = `
                                                    <div id="import-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" onclick="if(event.target === this) closeImportModal()">
                                                        <div class="glass-card max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                                                            <div class="flex items-center justify-between mb-6">
                                                                <h2 class="text-2xl font-black text-p-title uppercase italic tracking-tight">
                                                                    {{ \App\Core\Lang::get('tables.import_data') }} - ${tableName}
                                                                </h2>
                                                                <button onclick="closeImportModal()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                                                                    <svg class="w-6 h-6 text-p-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                    </svg>
                                                                </button>
                                                            </div>

                                                            <!-- Import Tabs -->
                                                            <div class="flex gap-2 mb-6 border-b border-glass-border pb-2">
                                                                <button onclick="switchImportTab('sql')" id="tab-sql" class="import-tab px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all bg-primary text-dark">
                                                                    SQL
                                                                </button>
                                                                <button onclick="switchImportTab('excel')" id="tab-excel" class="import-tab px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all text-p-muted hover:bg-white/5">
                                                                    Excel
                                                                </button>
                                                                <button onclick="switchImportTab('csv')" id="tab-csv" class="import-tab px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all text-p-muted hover:bg-white/5">
                                                                    CSV
                                                                </button>
                                                            </div>

                                                            <!-- SQL Import -->
                                                            <div id="import-content-sql" class="import-content">
                                                                <!-- SQL Sub-tabs -->
                                                                <div class="flex gap-2 mb-4 border-b border-glass-border/50 pb-2">
                                                                    <button onclick="switchSqlMode('file')" id="sql-mode-file" class="sql-mode-tab px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all bg-primary/20 text-primary border border-primary/30">
                                                                        📁 {{ \App\Core\Lang::get('tables.sql_file') }}
                                                                    </button>
                                                                    <button onclick="switchSqlMode('text')" id="sql-mode-text" class="sql-mode-tab px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all text-p-muted hover:bg-white/5">
                                                                        📝 {{ \App\Core\Lang::get('tables.sql_text') }}
                                                                    </button>
                                                                </div>

                                                                <!-- SQL File Upload -->
                                                                <div id="sql-mode-content-file" class="sql-mode-content">
                                                                    <form action="{{ $baseUrl }}admin/databases/table/import-sql" method="POST" enctype="multipart/form-data" class="space-y-4">
                                                                        {!! $csrf_field !!}
                                                                        <input type="hidden" name="db_id" value="${dbId}">
                                                                        <input type="hidden" name="table" value="${tableName}">

                                                                        <div class="space-y-2">
                                                                            <label class="form-label">{{ \App\Core\Lang::get('tables.sql_file') }}</label>
                                                                            <input type="file" name="sql_file" accept=".sql" required class="form-input">
                                                                            <p class="text-[9px] text-p-muted italic">{{ \App\Core\Lang::get('tables.import_sql_help') }}</p>
                                                                        </div>

                                                                        <button type="submit" class="btn-primary w-full">
                                                                            📤 {{ \App\Core\Lang::get('tables.import_from_file') }}
                                                                        </button>
                                                                    </form>
                                                                </div>

                                                                <!-- SQL Text Input -->
                                                                <div id="sql-mode-content-text" class="sql-mode-content hidden">
                                                                    <form action="{{ $baseUrl }}admin/databases/table/import-sql-text" method="POST" class="space-y-4">
                                                                        {!! $csrf_field !!}
                                                                        <input type="hidden" name="db_id" value="${dbId}">
                                                                        <input type="hidden" name="table" value="${tableName}">

                                                                        <div class="space-y-2">
                                                                            <label class="form-label">Código SQL</label>
                                                                            <textarea name="sql_code" rows="12" required 
                                                                                class="form-input font-mono text-xs resize-y"
                                                                                placeholder="INSERT INTO ${tableName} (campo1, campo2) VALUES ('valor1', 'valor2');
                                INSERT INTO ${tableName} (campo1, campo2) VALUES ('valor3', 'valor4');"></textarea>
                                                                            <p class="text-[9px] text-p-muted italic">Pega tus sentencias SQL INSERT aquí. Puedes incluir múltiples sentencias.</p>
                                                                        </div>

                                                                        <button type="submit" class="btn-primary w-full">
                                                                            ⚡ Ejecutar SQL
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>

                                                            <!-- Excel Import -->
                                                            <div id="import-content-excel" class="import-content hidden">
                                                                <form action="{{ $baseUrl }}admin/databases/table/import-excel" method="POST" enctype="multipart/form-data" class="space-y-4">
                                                                    {!! $csrf_field !!}
                                                                    <input type="hidden" name="db_id" value="${dbId}">
                                                                    <input type="hidden" name="table" value="${tableName}">

                                                                    <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-4">
                                                                        <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-2">📥 Descargar Plantilla</p>
                                                                        <a href="{{ $baseUrl }}admin/databases/table/template-excel?db_id=${dbId}&table=${tableName}" 
                                                                           class="inline-flex items-center gap-2 text-[10px] font-black text-p-title hover:text-primary transition-colors">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                            </svg>
                                                                            Descargar plantilla Excel de ejemplo
                                                                        </a>
                                                                    </div>

                                                                    <div class="space-y-2">
                                                                        <label class="form-label">Archivo Excel</label>
                                                                        <input type="file" name="excel_file" accept=".xlsx,.xls" required class="form-input">
                                                                        <p class="text-[9px] text-p-muted italic">Sube un archivo Excel (.xlsx o .xls) con los datos</p>
                                                                    </div>

                                                                    <button type="submit" class="btn-primary w-full">
                                                                        Importar Excel
                                                                    </button>
                                                                </form>
                                                            </div>

                                                            <!-- CSV Import -->
                                                            <div id="import-content-csv" class="import-content hidden">
                                                                <form action="{{ $baseUrl }}admin/databases/table/import-csv" method="POST" enctype="multipart/form-data" class="space-y-4">
                                                                    {!! $csrf_field !!}
                                                                    <input type="hidden" name="db_id" value="${dbId}">
                                                                    <input type="hidden" name="table" value="${tableName}">

                                                                    <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-4">
                                                                        <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-2">📥 Descargar Plantilla</p>
                                                                        <a href="{{ $baseUrl }}admin/databases/table/template-csv?db_id=${dbId}&table=${tableName}" 
                                                                           class="inline-flex items-center gap-2 text-[10px] font-black text-p-title hover:text-primary transition-colors">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                            </svg>
                                                                            Descargar plantilla CSV de ejemplo
                                                                        </a>
                                                                    </div>

                                                                    <div class="space-y-2">
                                                                        <label class="form-label">Archivo CSV</label>
                                                                        <input type="file" name="csv_file" accept=".csv" required class="form-input">
                                                                        <p class="text-[9px] text-p-muted italic">Sube un archivo CSV con los datos (separado por comas)</p>
                                                                    </div>

                                                                    <button type="submit" class="btn-primary w-full">
                                                                        Importar CSV
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        function closeImportModal() {
            const modal = document.getElementById('import-modal');
            if (modal) modal.remove();
        }

        function switchImportTab(type) {
            // Update tabs
            document.querySelectorAll('.import-tab').forEach(tab => {
                tab.classList.remove('bg-primary', 'text-dark');
                tab.classList.add('text-p-muted', 'hover:bg-white/5');
            });
            document.getElementById(`tab-${type}`).classList.add('bg-primary', 'text-dark');
            document.getElementById(`tab-${type}`).classList.remove('text-p-muted', 'hover:bg-white/5');

            // Update content
            document.querySelectorAll('.import-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`import-content-${type}`).classList.remove('hidden');
        }

        function switchSqlMode(mode) {
            // Update SQL mode tabs
            document.querySelectorAll('.sql-mode-tab').forEach(tab => {
                tab.classList.remove('bg-primary/20', 'text-primary', 'border-primary/30');
                tab.classList.add('text-p-muted', 'hover:bg-white/5');
            });
            document.getElementById(`sql-mode-${mode}`).classList.add('bg-primary/20', 'text-primary', 'border-primary/30');
            document.getElementById(`sql-mode-${mode}`).classList.remove('text-p-muted', 'hover:bg-white/5');

            // Update SQL mode content
            document.querySelectorAll('.sql-mode-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`sql-mode-content-${mode}`).classList.remove('hidden');
        }
        function switchCreateTableTab(type) {
            // Update tabs
            const simpleTab = document.getElementById('create-tab-simple');
            const sqlTab = document.getElementById('create-tab-sql');

            if (type === 'simple') {
                simpleTab.classList.add('bg-primary/10', 'text-primary');
                simpleTab.classList.remove('text-p-muted', 'hover:bg-white/5');
                sqlTab.classList.remove('bg-primary/10', 'text-primary');
                sqlTab.classList.add('text-p-muted', 'hover:bg-white/5');
                document.getElementById('create-form-simple').classList.remove('hidden');
                document.getElementById('create-form-sql').classList.add('hidden');
            } else {
                sqlTab.classList.add('bg-primary/10', 'text-primary');
                sqlTab.classList.remove('text-p-muted', 'hover:bg-white/5');
                simpleTab.classList.remove('bg-primary/10', 'text-primary');
                simpleTab.classList.add('text-p-muted', 'hover:bg-white/5');
                document.getElementById('create-form-sql').classList.remove('hidden');
                document.getElementById('create-form-simple').classList.add('hidden');
            }
        }
    </script>
@endsection