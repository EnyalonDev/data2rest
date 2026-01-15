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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-blue-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ count($logs) }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('common.all') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['api_calls'] }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.api_usage') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['data_changes'] }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.data_mutations') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-purple-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ count($stats['top_endpoints']) }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.activity.active_endpoints') }}</span>
        </div>
    </div>

    <!-- Filters Section -->
    <section class="mb-12 glass-card border-white/5 bg-white/[0.02]">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            </div>
            <h3 class="text-xs font-black text-p-title uppercase tracking-widest">{{ \App\Core\Lang::get('logs.filters') }}</h3>
        </div>
        
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
            <!-- User Filter -->
            <div class="md:col-span-2">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">{{ \App\Core\Lang::get('logs.all_users') }}</label>
                <select name="user_id" class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs">
                    <option value="">{{ \App\Core\Lang::get('common.all') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user['id'] }}" {{ ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : '' }}>
                            {{ $user['username'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Filter -->
            <div class="md:col-span-2">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">{{ \App\Core\Lang::get('logs.all_actions') }}</label>
                <select name="action_type" class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs">
                    <option value="">{{ \App\Core\Lang::get('common.all') }}</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ ($filters['action_type'] ?? '') == $action ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', $action) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range -->
            <div class="md:col-span-2">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">{{ \App\Core\Lang::get('logs.start_date') }}</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs text-p-muted">
            </div>
            <div class="md:col-span-2">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">{{ \App\Core\Lang::get('logs.end_date') }}</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-input !bg-black/40 !border-white/10 !py-2.5 text-xs text-p-muted">
            </div>

            <!-- Search Details -->
            <div class="md:col-span-3">
                <label class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-2 block px-1">{{ \App\Core\Lang::get('logs.search_details') }}</label>
                <div class="relative">
                    <input type="text" name="s" value="{{ $filters['s'] ?? '' }}" placeholder="..." class="form-input !bg-black/40 !border-white/10 !py-2.5 !pl-9 text-xs">
                    <svg class="absolute left-3 top-2.5 text-p-muted" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
            </div>

            <!-- Submit -->
            <div class="md:col-span-1">
                <button type="submit" class="btn-primary w-full !h-[38px] !px-0 flex items-center justify-center" title="{{ \App\Core\Lang::get('logs.filter_btn') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Logs Timeline -->
        <div class="lg:col-span-2 space-y-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('logs.recent_events') }}
                </h2>
                <button onclick="window.location.reload()"
                    class="text-[10px] font-black text-blue-500 uppercase tracking-widest hover:text-white transition-colors">
                    {{ \App\Core\Lang::get('logs.refresh') }} ↻
                </button>
            </div>

            <div class="glass-card !p-0 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 border-b border-white/10">
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                                {{ \App\Core\Lang::get('dashboard.activity.event') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                                {{ \App\Core\Lang::get('dashboard.activity.user') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                                {{ \App\Core\Lang::get('dashboard.activity.details') }}</th>
                            <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                                {{ \App\Core\Lang::get('dashboard.activity.time') }}</th>
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
                                    <div class="flex flex-col gap-2">
                                        <div class="max-w-xs truncate text-[11px] text-p-muted font-medium hover:text-white transition-colors cursor-help"
                                            title="{{ $log['details'] }}">
                                            {{ $log['details'] }}
                                        </div>
                                        
                                        @php
                                            $details = json_decode((string)($log['details'] ?? '{}'), true);
                                            $hasHistory = in_array($log['action'], ['UPDATE_RECORD', 'DELETE_RECORD', 'RESTORE_VERSION', 'API_UPDATE', 'API_DELETE']);
                                        @endphp

                                        @if($hasHistory && isset($details['table']) && isset($details['id']))
                                            <a href="{{ $baseUrl }}admin/crud/history?db_id={{ $log['project_id'] }}&table={{ $details['table'] }}&id={{ $details['id'] }}" 
                                               class="inline-flex items-center gap-1.5 text-[9px] font-black uppercase text-primary hover:text-white transition-colors group">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                View Version History
                                            </a>
                                        @endif
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
                                    <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                                        {{ \App\Core\Lang::get('logs.no_activity') }}</p>
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
                    <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('logs.top_endpoints') }}
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
                    <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('logs.system_health') }}
                </h2>
                <div class="glass-card !p-6">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('logs.db_sync') }}</span>
                            <span
                                class="px-2 py-0.5 bg-emerald-500/20 text-emerald-500 rounded text-[9px] font-black uppercase tracking-widest border border-emerald-500/30">{{ \App\Core\Lang::get('logs.optimal') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('logs.log_retention') }}</span>
                            <span class="text-[10px] font-black text-p-title">30 Days</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('logs.api_latency') }}</span>
                            <span class="text-[10px] font-black text-p-title italic">&lt; 15ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection