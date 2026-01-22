@extends('layouts.main')

@section('title', 'Import JSON Payload')

@section('content')
    <header class="mb-12">
        <div class="flex items-center gap-4">
            <a href="{{ $baseUrl }}admin/databases/view?id={{ $database['id'] }}" class="btn-outline !p-3 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-4xl font-black text-p-title italic tracking-tighter uppercase">
                    Importer <span class="text-primary">JSON</span>
                </h1>
                <p class="text-p-muted font-medium tracking-tight">
                    Populate <b>{{ $database['name'] }}</b> with a schema payload.
                </p>
            </div>
        </div>
    </header>

    <section class="max-w-4xl mx-auto">
        <form action="{{ $baseUrl }}admin/databases/import-json-process" method="POST" class="glass-card space-y-8">
            {!! $csrf_field !!}
            <input type="hidden" name="db_id" value="{{ $database['id'] }}">

            <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-xl flex gap-4">
                <div class="text-3xl">⚠️</div>
                <div>
                    <h3 class="font-bold text-amber-500 uppercase tracking-widest text-xs mb-1">Warning</h3>
                    <p class="text-amber-500/80 text-sm">
                        This process will create tables if they don't exist. Matches are approximate based on schema names.
                        Ensure your JSON is valid.
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-black text-p-muted uppercase tracking-[0.2em] ml-1">
                    JSON Payload
                </label>
                <div class="relative group">
                    <textarea name="json_payload" rows="20"
                        class="form-input font-mono text-xs leading-relaxed custom-scrollbar !bg-black/40 border-primary/20 focus:border-primary"
                        placeholder='{
      "database_schema": { ... },
      "content_payload": { ... }
    }' required></textarea>

                    <div
                        class="absolute top-4 right-4 opacity-50 text-[10px] uppercase font-black tracking-widest pointer-events-none">
                        JSON EDITOR
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-white/5">
                <button type="submit" class="btn-primary flex items-center gap-2 px-8 py-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Processing Import
                </button>
            </div>
        </form>
    </section>
@endsection