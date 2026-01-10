@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-blue-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-blue-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('common.system') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            {{ \App\Core\Lang::get('dashboard.activity.title') }}
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            {{ \App\Core\Lang::get('dashboard.activity.summary') }}
        </p>
    </header>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-16">
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-blue-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ count($logs) }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('common.all') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['api_calls'] }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.api_usage') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['data_changes'] }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.data_mutations') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-purple-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ count($stats['top_endpoints']) }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.active_endpoints') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Logs Timeline -->
        <div class="lg:col-span-2 space-y-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span> Recent Events
                </h2>
                <button onclick="window.location.reload()"
                    class="text-[10px] font-black text-blue-500 uppercase tracking-widest hover:text-white transition-colors">
                    Refresh ↻
                </button>
            </div>

            <div class="glass-card !p-0 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 border-b border-white/10">
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.event') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.user') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.details') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($logs as $log)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $icon = '📄';
                                            $color = 'text-p-muted';
                                            if (strpos($log['action'], 'API_') === 0) {
                                                $icon = '🔌';
                                                $color = 'text-blue-500';
                                            } elseif (strpos($log['action'], 'INSERT_') === 0) {
                                                $icon = '➕';
                                                $color = 'text-emerald-500';
                                            } elseif (strpos($log['action'], 'UPDATE_') === 0) {
                                                $icon = '📝';
                                                $color = 'text-amber-500';
                                            } elseif (strpos($log['action'], 'DELETE_') === 0) {
                                                $icon = '🗑️';
                                                $color = 'text-red-500';
                                            } elseif (strpos($log['action'], 'UPLOAD_') === 0) {
                                                $icon = '📁';
                                                $color = 'text-purple-500';
                                            }
                                        @endphp
                                        <span class="text-lg">{{ $icon }}</span>
                                        <span class="text-[10px] font-black uppercase tracking-widest {{ $color }}">
                                            {{ str_replace('_', ' ', $log['action']) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-bold text-p-title">{{ $log['username'] ?? 'System / Anonymous' }}</span>
                                        <span
                                            class="text-[9px] text-p-muted font-black uppercase tracking-tighter">{{ $log['ip_address'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="max-w-xs truncate text-[11px] text-p-muted font-medium hover:text-white transition-colors cursor-help"
                                        title="{{ $log['details'] }}">
                                        {{ $log['details'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="text-[10px] font-black text-p-muted uppercase italic">
                                        {{ date('M d, H:i', strtotime($log['created_at'])) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">No activity
                                        recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar Stats -->
        <aside class="space-y-12">
            <div>
                <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span> Top API Endpoints
                </h2>
                <div class="glass-card !p-6 space-y-4">
                    @forelse($stats['top_endpoints'] as $top)
                        <div class="flex justify-between items-center bg-white/5 p-4 rounded-xl border border-white/5">
                            <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">
                                {{ str_replace('API_', '', $top['action']) }}
                            </span>
                            <span class="text-xs font-black text-p-title">{{ $top['count'] }} calls</span>
                        </div>
                    @empty
                        <p class="text-[10px] font-black text-p-muted uppercase text-center py-4">No API data</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span> System Health
                </h2>
                <div class="glass-card !p-6">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Database Sync</span>
                            <span
                                class="px-2 py-0.5 bg-emerald-500/20 text-emerald-500 rounded text-[9px] font-black uppercase tracking-widest border border-emerald-500/30">Optimal</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">Log Retention</span>
                            <span class="text-[10px] font-black text-p-title">30 Days</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest">API Latency</span>
                            <span class="text-[10px] font-black text-p-title italic">&lt; 15ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection