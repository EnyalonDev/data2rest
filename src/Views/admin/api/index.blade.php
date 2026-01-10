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
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter mb-2">
            {{ \App\Core\Lang::get('api_control.title') }}
        </h1>
        <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('api_control.subtitle') }}</p>
    </header>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Left: API Keys -->
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
                                <h3 class="font-bold text-p-title dark:text-slate-300">{{ $key['name'] }}
                                </h3>
                                <button onclick="confirmRevokeKey({{ $key['id'] }}, '{{ addslashes($key['name']) }}')"
                                    class="text-p-muted hover:text-red-500 transition-all cursor-pointer opacity-60 group-hover:opacity-100 p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
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

        <!-- Right: Database Documentation Selection -->
        <section class="glass-card">
            <h2 class="text-xl font-black text-p-title uppercase tracking-tight mb-8">
                {{ \App\Core\Lang::get('api_control.doc_explorer') }}
            </h2>
            <div class="grid gap-4">
                @foreach($databases as $db)
                    <a href="{{ $baseUrl }}admin/api/docs?db_id={{ $db['id'] }}"
                        class="group bg-p-bg dark:bg-white/5 border border-glass-border p-6 rounded-2xl hover:border-primary/50 transition-all flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-p-title group-hover:text-primary transition-colors">
                                {{ $db['name'] }}
                            </h3>
                            <p class="text-xs text-p-muted mt-1 uppercase font-black tracking-widest">
                                {{ \App\Core\Lang::get('api_control.connect_internal') }}
                            </p>
                        </div>
                        <div class="text-primary group-hover:translate-x-1 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                        </div>
                    </a>
                @endforeach
                @if(empty($databases))
                    <div class="py-12 text-center opacity-20">
                        <svg class="w-12 h-12 mx-auto mb-4 text-p-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M3 7c0 2.21 3.582 4 8 4s8-1.79 8-4M3 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                        <p class="text-xs font-black uppercase tracking-widest">
                            {{ \App\Core\Lang::get('api_control.no_databases') }}
                        </p>
                    </div>
                @endif
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
                X-API-KEY: your_generated_key_here
            </div>
            <p class="mt-4">{{ \App\Core\Lang::get('api_control.guide_footer') }}</p>
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