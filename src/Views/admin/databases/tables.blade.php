@extends('layouts.main')

@section('title', \App\Core\Lang::get('tables.title') . ' - ' . $database['name'])

@section('content')
    <header class="mb-12 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
        <div class="flex flex-col gap-4">
            <div>
                <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
                    {{ \App\Core\Lang::get('tables.title') }}
                </h1>
                <p class="text-p-muted font-medium tracking-tight">
                    {!! str_replace(':name', '<b>' . $database['name'] . '</b>', \App\Core\Lang::get('tables.subtitle')) !!}
                </p>
            </div>
            <div class="flex gap-4">
                <a href="{{ $baseUrl }}admin/databases" class="btn-primary !bg-slate-800 !text-slate-300">
                    &larr; {{ \App\Core\Lang::get('common.back') }}
                </a>
                @if(\App\Core\Auth::hasPermission('module:databases.edit_table'))
                    <a href="{{ $baseUrl }}admin/databases/sync?id={{ $database['id'] }}"
                        class="inline-flex items-center gap-2 group text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/5 px-4 py-2 rounded-lg border border-emerald-500/20 hover:bg-emerald-500/10 transition-all">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        {{ \App\Core\Lang::get('tables.sync') }}
                    </a>
                @endif
                @if(\App\Core\Auth::hasPermission('module:databases.create_db'))
                    <a href="{{ $baseUrl }}admin/databases/export?id={{ $database['id'] }}"
                        class="inline-flex items-center gap-2 group text-[10px] font-black text-amber-400 uppercase tracking-widest bg-amber-500/5 px-4 py-2 rounded-lg border border-amber-500/20 hover:bg-amber-500/10 transition-all font-mono">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exportar SQL
                    </a>
                @endif
                <a href="{{ $baseUrl }}admin/api/docs?db_id={{ $database['id'] }}"
                    class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all">{{ \App\Core\Lang::get('tables.api_docs') }}
                    &rarr;</a>
            </div>
        </div>
        @if(\App\Core\Auth::hasPermission('module:databases.create_table'))
            <div class="glass-card !p-4 !px-6 border-primary/20 bg-primary/5 w-full md:w-auto">
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-3">
                    {{ \App\Core\Lang::get('tables.init_table') }}
                </h2>
                <form action="{{ $baseUrl }}admin/databases/table/create" method="POST" class="flex gap-2">
                    <input type="hidden" name="db_id" value="{{ $database['id'] }}">
                    <input type="text" name="table_name" placeholder="{{ \App\Core\Lang::get('tables.table_placeholder') }}"
                        required class="form-input !py-2 !px-3 text-sm">
                    <button type="submit" class="btn-primary !py-2">{{ \App\Core\Lang::get('tables.create') }}</button>
                </form>
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
                <div class="flex gap-4">
                    <a href="{{ $baseUrl }}admin/crud/list?db_id={{ $database['id'] }}&table={{ $tableName }}"
                        class="btn-primary flex-1 text-center font-bold italic tracking-wider !py-2">{{ \App\Core\Lang::get('tables.enter') }}</a>
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
                message: `{!! addslashes(\App\Core\Lang::get('tables.delete_confirm_msg')) !!}`.replace(':table', table),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('tables.delete_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/databases/table/delete?db_id={{ $database['id'] }}&table=${table}`;
                }
            });
        }
    </script>
@endsection