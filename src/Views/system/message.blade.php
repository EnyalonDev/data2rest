@extends('layouts.main')

@section('title', 'System Message')

@section('content')
    <div class="max-w-2xl mx-auto mt-20">
        <div class="glass-card p-8 text-center space-y-6">
            <div class="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center mx-auto text-blue-500 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h2 class="text-2xl font-black text-p-title uppercase italic tracking-tighter">
                {{ $lang['system_modal']['notification'] ?? 'Notification' }}
            </h2>

            <p class="text-lg text-p-muted font-medium">
                {{ $message ?? '' }}
            </p>

            <div class="pt-6">
                <a href="{{ $baseUrl }}admin/system/info"
                    class="px-6 py-3 bg-white/5 hover:bg-white/10 text-p-title rounded-xl font-bold uppercase tracking-widest text-xs transition-all border border-white/10 hover:border-primary/30">
                    {{ $lang['common']['back'] ?? 'Back' }}
                </a>
            </div>
        </div>
    </div>
@endsection