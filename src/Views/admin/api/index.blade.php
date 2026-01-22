@extends('layouts.main')

@section('title', \App\Core\Lang::get('api_control.title'))

@section('styles')
    <style type="text/tailwindcss">
        .input-dark {
                            @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-2 text-p-title focus:outline-none focus:border-primary/50 transition-all font-medium;
                        }
                    </style>
@endsection

@section('content')
    <!-- 
            API Control Panel Header 
            Includes Analytics navigation and title.
        -->
    <header class="mb-12 flex justify-between items-end">
        <div>
            <h1 class="text-5xl font-black text-p-title italic tracking-tighter mb-2">
                {{ \App\Core\Lang::get('api_control.title') }}
            </h1>
            <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('api_control.subtitle') }}</p>
        </div>
        <div>
            <a href="{{ $baseUrl }}admin/api/analytics" class="btn-secondary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                View Analytics
            </a>
        </div>
    </header>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- 
                Left Column: API Keys Management 
                Create new keys and list existing active keys.
            -->
        <section class="glass-card flex flex-col h-full">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-xl font-black text-p-title uppercase tracking-tight">
                    {{ \App\Core\Lang::get('api_control.tokens') }}
                </h2>
                <span
                    class="text-[10px] bg-primary/10 text-primary border border-primary/20 px-3 py-1 rounded-full font-bold uppercase">{{ count($keys) }}
                    {{ \App\Core\Lang::get('api_control.active') }}</span>
            </div>

            <form action="{{ $baseUrl }}admin/api/keys/create" method="POST" class="mb-8 flex gap-2">
                {!! $csrf_field !!}
                <input type="text" name="name" placeholder="{{ \App\Core\Lang::get('api_control.placeholder') }}" required
                    class="input-dark flex-1">
                <input type="number" name="rate_limit" placeholder="Limit (1000)" value="1000"
                    class="input-dark w-24 text-center">
                <button type="submit" class="btn-primary">{{ \App\Core\Lang::get('api_control.generate') }}</button>
            </form>

            <div class="flex-1 space-y-4">
                @if(empty($keys))
                    <div class="py-12 text-center opacity-20">
                        <svg class="w-12 h-12 mx-auto mb-4 text-p-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                            </path>
                        </svg>
                        <p class="text-xs font-black uppercase tracking-widest">{{ \App\Core\Lang::get('api_control.no_keys') }}
                        </p>
                    </div>
                @else
                    @foreach($keys as $key)
                        <div class="bg-p-bg dark:bg-white/5 border border-glass-border p-4 rounded-xl group transition-all">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-p-title dark:text-slate-300">{{ $key['name'] }}</h3>
                                    <span class="text-[10px] text-p-muted uppercase font-bold tracking-wider">
                                        Limit: {{ number_format($key['rate_limit']) }}/hr
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ $baseUrl }}admin/api/permissions?key_id={{ $key['id'] }}"
                                        class="text-xs bg-white/10 hover:bg-white/20 px-2 py-1 rounded text-white font-medium"
                                        title="Manage Permissions">
                                        Manage
                                    </a>
                                    <button onclick="confirmRevokeKey({{ $key['id'] }}, '{{ addslashes($key['name']) }}')"
                                        class="text-p-muted hover:text-red-500 transition-all cursor-pointer p-1"
                                        title="Revoke Key">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 bg-white dark:bg-black/40 p-2 rounded-lg border border-p-border dark:border-white/5">
                                <code
                                    class="text-[10px] text-primary break-all flex-1 font-mono font-bold">{{ $key['key_value'] }}</code>
                                <button onclick="navigator.clipboard.writeText('{{ $key['key_value'] }}')"
                                    class="text-p-muted hover:text-p-title transition-colors" title="Copy to clipboard">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2-2h-8a2 2 0 00-2-2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        <!-- 
                Right Column: Interactive Documentation & SDKs
                Links to Swagger UI, Legacy Docs, and SDK downloads.
            -->
        <section class="glass-card">
            <h2 class="text-xl font-black text-p-title uppercase tracking-tight mb-8">
                Interactive Documentation
            </h2>
            <div class="grid gap-4">
                @foreach($databases as $db)
                    <div
                        class="group bg-p-bg dark:bg-white/5 border border-glass-border p-6 rounded-2xl hover:border-primary/50 transition-all flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3
                                class="text-lg font-bold text-p-title group-hover:text-primary transition-colors flex items-center gap-3">
                                {{ $db['name'] }}
                                <span
                                    class="text-[9px] px-1.5 py-0.5 rounded border border-white/10 uppercase font-black tracking-widest text-p-muted">{{ $db['type'] }}</span>
                            </h3>
                            <a href="{{ $baseUrl }}admin/api/swagger?db_id={{ $db['id'] }}" target="_blank"
                                class="text-primary hover:bg-primary/10 px-3 py-1.5 rounded-lg flex items-center gap-2 text-sm font-bold transition-all">
                                <span>Swagger UI</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium text-p-muted">
                            <a href="{{ $baseUrl }}admin/api/docs?db_id={{ $db['id'] }}"
                                class="hover:text-white underline decoration-dotted">Legacy Docs</a>
                            <span>&bull;</span>
                            <span class="hover:text-white cursor-help"
                                title="Base Endpoint">{{ $baseUrl }}api/v2/db/{{ $db['id'] }}/...</span>
                        </div>
                    </div>
                @endforeach
                @if(empty($databases))
                    <div class="py-12 text-center opacity-20">
                        <svg class="w-12 h-12 mx-auto mb-4 text-p-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                        <p class="text-xs font-black uppercase tracking-widest">
                            {{ \App\Core\Lang::get('api_control.no_databases') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8 pt-8 border-t border-glass-border">
                <h3 class="text-xs font-black uppercase tracking-widest text-p-muted mb-4">Official SDKs</h3>
                <div class="flex gap-4">
                    <a href="{{ $baseUrl }}sdk/javascript/data2rest.js" download
                        class="flex-1 bg-black/20 hover:bg-black/40 p-3 rounded-lg border border-white/5 flex items-center justify-center gap-2 transition-all">
                        <span class="text-yellow-400 font-bold">JS</span>
                        <span class="text-xs text-p-muted">Download SDK</span>
                    </a>
                    <a href="{{ $baseUrl }}sdk/python/data2rest.py" download
                        class="flex-1 bg-black/20 hover:bg-black/40 p-3 rounded-lg border border-white/5 flex items-center justify-center gap-2 transition-all">
                        <span class="text-blue-400 font-bold">PY</span>
                        <span class="text-xs text-p-muted">Download SDK</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <section class="mt-8 glass-card border-emerald-500/20">
        <h3 class="text-emerald-400 font-black text-[10px] uppercase tracking-[0.3em] mb-4">
            {{ \App\Core\Lang::get('api_control.guide_title') }}
        </h3>
        <div class="prose prose-invert max-w-none text-sm text-p-muted leading-relaxed font-medium">
            <p>{{ \App\Core\Lang::get('api_control.guide_text') }}</p>
            <div class="bg-black/40 p-4 rounded-xl border border-white/5 font-mono text-primary text-xs mt-3">
                curl -H "X-API-KEY: key" "{{ $baseUrl }}api/v2/db/1/table?limit=10"
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function confirmRevokeKey(id, name) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('api_control.revoke_confirm_title')) !!}',
                message: `{!! addslashes(\App\Core\Lang::get('api_control.revoke_confirm_msg')) !!}`.replace(':name', name),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('api_control.revoke_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/api/keys/delete?id=${id}`;
                }
            });
        }
    </script>
@endsection