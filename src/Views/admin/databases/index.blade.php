@extends('layouts.main')

@section('title', \App\Core\Lang::get('databases.title'))

@section('content')
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
            {{ \App\Core\Lang::get('databases.title') }}
        </h1>
        <p class="text-p-muted font-medium tracking-tight">{{ \App\Core\Lang::get('databases.subtitle') }}</p>
    </header>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            @if(\App\Core\Auth::hasPermission('module:databases.create_db'))
                <div class="glass-card">
                    <h2 class="text-xl font-bold text-p-title mb-6 uppercase italic tracking-tighter">
                        {{ \App\Core\Lang::get('databases.new_node') }}
                    </h2>
                    <form action="{{ $baseUrl }}admin/databases/create" method="POST" class="space-y-4">
                        {!! $csrf_field !!}
                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ \App\Core\Lang::get('databases.node_name') }}</label>
                            <input type="text" name="name" placeholder="{{ \App\Core\Lang::get('databases.node_placeholder') }}"
                                required class="form-input w-full">
                        </div>
                        <button type="submit"
                            class="btn-primary w-full mt-2 font-black uppercase tracking-widest text-xs">{{ \App\Core\Lang::get('databases.create_node') }}</button>
                    </form>
                </div>

                <div class="glass-card">
                    <h2 class="text-xl font-bold text-p-title mb-6 uppercase italic tracking-tighter flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importar SQL
                    </h2>
                    <form action="{{ $baseUrl }}admin/databases/import" method="POST" enctype="multipart/form-data"
                        class="space-y-4">
                        {!! $csrf_field !!}
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Nombre del
                                Nodo</label>
                            <input type="text" name="name" placeholder="Ej: Mi DB Importada" required class="form-input w-full">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Archivo
                                .sql</label>
                            <input type="file" name="sql_file" accept=".sql" required class="form-input w-full text-xs">
                        </div>
                        <button type="submit" class="btn-primary w-full mt-2 font-black uppercase tracking-widest text-xs">Crear
                            desde SQL</button>
                        <p class="text-[9px] text-p-muted italic opacity-70">El script debe contener sentencias CREATE TABLE
                            compatibles con SQLite.</p>
                    </form>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-4">
            @foreach ($databases as $db)
                <div class="glass-card flex flex-col group overflow-hidden relative">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    <div class="flex items-center gap-6 relative z-10 mb-6">
                        <div
                            class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform duration-500 border border-primary/20 text-primary flex-shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-bold text-p-title mb-1 tracking-tight uppercase italic truncate">
                                {{ $db['name'] }}
                            </h3>
                            <p class="text-[9px] text-p-muted font-black uppercase tracking-widest truncate">
                                {{ $db['path'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 relative z-10 pt-6 border-t border-white/5">
                        <a href="{{ $baseUrl }}admin/databases/view?id={{ $db['id'] }}"
                            class="flex-1 btn-primary !bg-p-bg dark:!bg-white/5 !text-p-title dark:!text-slate-300 hover:!bg-primary/20 flex items-center justify-center gap-2 italic uppercase text-[10px] font-black tracking-widest !py-3 shadow-sm">
                            {{ \App\Core\Lang::get('databases.interface') }} &rarr;
                        </a>
                        @if(\App\Core\Auth::hasPermission('module:databases.edit_db'))
                            <a href="{{ $baseUrl }}admin/databases/edit?id={{ $db['id'] }}"
                                class="p-3 bg-p-bg dark:bg-white/5 rounded-xl text-p-muted hover:text-primary transition-all shadow-sm" title="{{ \App\Core\Lang::get('databases.config_visibility') }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        @endif
                        @if(\App\Core\Auth::hasPermission('module:databases.delete_db'))
                            <button onclick="confirmDeleteDB({{ $db['id'] }}, '{{ addslashes($db['name']) }}')"
                                class="p-3 bg-p-bg dark:bg-white/5 rounded-xl text-p-muted hover:text-red-500 hover:bg-red-500/10 transition-all shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

@endsection

@section('scripts')
    <script>
        function confirmDeleteDB(id, name) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('databases.delete_confirm_title')) !!}',
                message: `{!! addslashes(\App\Core\Lang::get('databases.delete_confirm_msg')) !!}`.replace(':name', name),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('databases.delete_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/databases/delete?id=${id}`;
                }
            });
        }
    </script>
@endsection