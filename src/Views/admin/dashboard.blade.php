@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-primary/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-primary text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            {{ (isset($project) && $project) ? \App\Core\Lang::get('dashboard.active_project') : \App\Core\Lang::get('dashboard.title') }}
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            @if(isset($project) && $project)
                {{ $project['name'] }}
            @else
                {{ \App\Core\Lang::get('common.welcome') }}
            @endif
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            @if(isset($project) && $project)
                {{ \App\Core\Lang::get('common.welcome') }}. {{ \App\Core\Lang::get('dashboard.isolated_env') }}
            @else
                {{ \App\Core\Lang::get('dashboard.subtitle') }}
            @endif
        </p>
    </header>

    <!-- Project Context Section -->
    @if(isset($project) && $project)
        <div class="animate-in fade-in slide-in-from-top-4 duration-700 glass-card mb-12 bg-primary/5 border-primary/20">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-6 text-left">
                    <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center text-primary text-3xl">
                        📁
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-p-title tracking-tight">
                            {{ $project['name'] ?? \App\Core\Lang::get('dashboard.untitled_project') }}
                        </h2>
                        <p class="text-xs text-p-muted font-medium max-w-md">
                            {{ $project['description'] ?? \App\Core\Lang::get('dashboard.active_isolation_desc') }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-center md:justify-end gap-6">
                    <div class="text-center md:text-right">
                        <span
                            class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">{{ \App\Core\Lang::get('dashboard.subscription') }}</span>
                        <span
                            class="px-4 py-1.5 bg-primary/20 text-primary rounded-xl text-[10px] font-black uppercase tracking-widest border border-primary/30">
                            {{ $project['plan_type'] ?? 'Free' }}
                        </span>
                    </div>
                    <div class="hidden md:block w-[1px] h-12 bg-glass-border"></div>
                    <div class="text-center md:text-right">
                        <span
                            class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">{{ \App\Core\Lang::get('dashboard.billing_cycle') }}</span>
                        <span class="text-sm font-black text-p-title italic uppercase tracking-tighter">
                            {{ date('M d, Y', strtotime($project['next_billing_date'] ?? 'now')) }}
                        </span>
                    </div>
                    <div class="hidden md:block w-[1px] h-12 bg-glass-border"></div>
                    <a href="{{ $baseUrl }}admin/projects/select"
                        class="px-6 py-3 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary hover:text-dark transition-all">
                        {{ \App\Core\Lang::get('dashboard.change_project') }}
                    </a>
                </div>
            </div>
        </div>
    @elseif(!\App\Core\Auth::isAdmin())
        <div class="glass-card mb-12 bg-red-500/5 border-red-500/20 text-center py-12">
            <div
                class="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 text-3xl mx-auto mb-6">
                ⚠️
            </div>
            <h2 class="text-2xl font-black text-p-title mb-2">{{ \App\Core\Lang::get('dashboard.no_active_project') }}</h2>
            <p class="text-p-muted font-medium mb-8">{{ \App\Core\Lang::get('dashboard.select_project_msg') }}</p>
            <a href="{{ $baseUrl }}admin/projects"
                class="btn-primary !px-10 !py-4 font-black uppercase tracking-widest text-xs italic">{{ \App\Core\Lang::get('common.select_project') }}</a>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-primary/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['total_databases'] }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.stats.dbs') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ number_format($stats['total_records']) }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.stats.records') }}</span>
        </div>
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
            <span class="text-4xl font-black text-p-title mb-2">{{ $stats['storage_usage'] }}</span>
            <span
                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('dashboard.stats.storage') }}</span>
        </div>
    </div>

    @if(\App\Core\Auth::isAdmin() && $globalDbCount == 0 && $showWelcomeBanner)
        <!-- Getting Started Banner -->
        <div class="glass-card mb-12 relative overflow-hidden group border-primary/30">
            <!-- Close Button -->
            <button onclick="dismissBanner()"
                class="absolute top-4 right-4 z-50 w-8 h-8 flex items-center justify-center rounded-full bg-white/5 hover:bg-white/10 text-white/50 hover:text-white transition-all"
                title="{{ \App\Core\Lang::get('common.dismiss') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div
                class="absolute -right-20 -top-20 w-64 h-64 bg-primary/20 blur-[80px] rounded-full group-hover:bg-primary/30 transition-all duration-700">
            </div>
            <div class="relative z-10 p-8 md:p-12 flex flex-col md:flex-row items-center gap-10">
                <div
                    class="w-24 h-24 bg-primary text-dark rounded-3xl flex items-center justify-center text-4xl shadow-2xl shadow-primary/40 animate-bounce">
                    🚀
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl md:text-4xl font-black text-p-title mb-4 tracking-tight uppercase italic">
                        {{ \App\Core\Lang::get('dashboard.welcome_title') }}
                    </h2>
                    <p class="text-p-muted font-medium mb-8 max-w-xl leading-relaxed">
                        {{ \App\Core\Lang::get('dashboard.welcome_text') }}
                    </p>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        <a href="{{ $baseUrl }}admin/demo/load"
                            class="btn-primary flex items-center gap-2 !py-4 !px-8 text-xs font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-105 transition-all">
                            <span>✨</span> {{ \App\Core\Lang::get('dashboard.load_demo') }}
                        </a>
                        <a href="{{ $baseUrl }}admin/databases"
                            class="px-8 py-4 rounded-xl border border-glass-border text-xs font-black uppercase tracking-widest text-p-muted hover:text-white hover:bg-white/5 transition-all">
                            {{ \App\Core\Lang::get('databases.new_node') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Modules -->
        <div class="lg:col-span-2 space-y-8">
            <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('common.actions') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Database Module -->
                @if(\App\Core\Auth::hasPermission('module:databases', 'view_tables') || \App\Core\Auth::hasPermission('module:databases', 'create_db'))
                    <a href="{{ $baseUrl }}admin/databases"
                        class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                        <div
                            class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('dashboard.db_title') }}</h3>
                        <p class="text-xs text-p-muted mb-6 leading-relaxed">
                            {{ \App\Core\Lang::get('dashboard.db_desc') }}
                        </p>
                        <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                            {{ \App\Core\Lang::get('dashboard.enter') }} <span>&rarr;</span>
                        </div>
                    </a>
                @endif

                <!-- API Module -->
                @if(\App\Core\Auth::hasPermission('module:api', 'view_keys'))
                    <a href="{{ $baseUrl }}admin/api" class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                        <div
                            class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('dashboard.api_title') }}</h3>
                        <p class="text-xs text-p-muted mb-6 leading-relaxed">
                            {{ \App\Core\Lang::get('dashboard.api_desc') }}
                        </p>
                        <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                            {{ \App\Core\Lang::get('dashboard.enter') }} <span>&rarr;</span>
                        </div>
                    </a>
                @endif

                <!-- Media Library Module -->
                @if(\App\Core\Auth::hasPermission('module:media', 'view_files'))
                    <a href="{{ $baseUrl }}admin/media"
                        class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                        <div
                            class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('media.explorer') }}</h3>
                        <p class="text-xs text-p-muted mb-6 leading-relaxed">
                            {{ \App\Core\Lang::get('media.system') }}
                        </p>
                        <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                            {{ \App\Core\Lang::get('dashboard.enter') }} <span>&rarr;</span>
                        </div>
                    </a>
                @endif

                <!-- Users Module -->
                @if(\App\Core\Auth::hasPermission('module:users', 'view_users'))
                    <a href="{{ $baseUrl }}admin/users"
                        class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                        <div
                            class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('dashboard.team_title') }}</h3>
                        <p class="text-xs text-p-muted mb-6 leading-relaxed">
                            {{ \App\Core\Lang::get('dashboard.team_desc') }}
                        </p>
                        <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                            {{ \App\Core\Lang::get('dashboard.enter') }} <span>&rarr;</span>
                        </div>
                    </a>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <aside>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('dashboard.activity.title') }}
                </h2>
                <a href="{{ $baseUrl }}admin/logs"
                    class="text-[9px] font-black text-primary uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                    {{ \App\Core\Lang::get('common.all') }} &rarr;
                </a>
            </div>
            <div class="glass-card !p-6 space-y-6">
                @if(empty($stats['recent_activity']))
                    <div class="py-10 text-center text-p-muted">
                        <p class="text-[10px] font-black uppercase tracking-widest">
                            {{ \App\Core\Lang::get('dashboard.activity.empty') }}
                        </p>
                    </div>
                @else
                    @foreach($stats['recent_activity'] as $act)
                        <div class="relative pl-6 border-l border-white/5">
                            <div class="absolute left-[-5px] top-1 w-2.5 h-2.5 rounded-full bg-primary border-4 border-dark"></div>
                            <p class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">
                                {{ $act['date'] }}
                            </p>
                            <p class="text-xs font-bold text-p-title mb-1">{{ $act['label'] }}</p>
                            <p class="text-[9px] font-black text-primary uppercase opacity-60">
                                {{ \App\Core\Lang::get('dashboard.activity.injected') }} {{ $act['db'] }} /
                                {{ $act['table'] }}
                            </p>
                        </div>
                    @endforeach
                @endif
            </div>
        </aside>
    </div>

    <div class="mt-16 pt-16 border-t border-glass-border">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-8 flex items-center gap-3 justify-center">
            <span class="w-12 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('common.system') }} <span
                class="w-12 h-[1px] bg-slate-800"></span>
        </h2>
        <div class="max-w-2xl mx-auto flex flex-wrap justify-center gap-6">
            <button onclick="showSystemInfo()"
                class="px-8 py-4 rounded-xl border border-primary/30 text-primary text-[10px] font-black uppercase tracking-[0.2em] hover:bg-primary hover:text-dark transition-all duration-300">
                📊 {{ \App\Core\Lang::get('dashboard.system_info') }}
            </button>
            @if(\App\Core\Auth::isAdmin())
                @php $isDev = \App\Core\Auth::isDevMode(); @endphp
                <button onclick="toggleDevMode()" id="btn-dev-mode"
                    class="px-8 py-4 rounded-xl border {{ $isDev ? 'border-amber-500 bg-amber-500 text-white' : 'border-amber-500/30 text-amber-500' }} text-[10px] font-black uppercase tracking-[0.2em] hover:bg-amber-500 hover:text-white transition-all duration-300">
                    🛠️ {{ \App\Core\Lang::get('dashboard.dev_mode') }}: {{ $isDev ? 'ON' : 'OFF' }}
                </button>

                @if($isDev)
                    <button onclick="clearCache()"
                        class="px-8 py-4 rounded-xl border border-blue-500/30 text-blue-500 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-500 hover:text-white transition-all duration-300">
                        🧹 {{ \App\Core\Lang::get('dashboard.clear_cache') }}
                    </button>
                    <button onclick="clearSessions()"
                        class="px-8 py-4 rounded-xl border border-purple-500/30 text-purple-500 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-purple-500 hover:text-white transition-all duration-300">
                        👥 {{ \App\Core\Lang::get('dashboard.clear_sessions') }}
                    </button>
                @endif

                <button onclick="triggerResetSystem()"
                    class="px-8 py-4 rounded-xl border border-red-500/30 text-red-500 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-red-500 hover:text-white transition-all duration-300">
                    ⚡ {{ \App\Core\Lang::get('dashboard.reset_system') }}
                </button>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleDevMode() {
            fetch('{{ $baseUrl }}admin/system/dev-mode', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const btn = document.getElementById('btn-dev-mode');
                        const isActive = data.dev_mode === 'on';
                        btn.className = `px-8 py-4 rounded-xl border ${isActive ? 'border-amber-500 bg-amber-500 text-white' : 'border-amber-500/30 text-amber-500'} text-[10px] font-black uppercase tracking-[0.2em] hover:bg-amber-500 hover:text-white transition-all duration-300`;
                        btn.innerHTML = `🛠️ DEV MODE: ${isActive ? 'ON' : 'OFF'}`;

                        showModal({
                            title: '{!! addslashes(\App\Core\Lang::get('dashboard.dev_mode_modal_title')) !!}',
                            message: '{!! addslashes(\App\Core\Lang::get('dashboard.dev_mode_msg')) !!}'.replace(':status', isActive ? '{!! addslashes(\App\Core\Lang::get('dashboard.dev_mode_on')) !!}' : '{!! addslashes(\App\Core\Lang::get('dashboard.dev_mode_off')) !!}'),
                            type: 'success',
                            onConfirm: () => window.location.reload()
                        });
                    }
                });
        }

        function showSystemInfo() {
            fetch('{{ $baseUrl }}admin/system/info')
                .then(res => res.json())
                .then(data => {
                    const helpMap = {
                        'upload_max_filesize': '{!! addslashes(\App\Core\Lang::get('dashboard.help_upload')) !!}',
                        'post_max_size': '{!! addslashes(\App\Core\Lang::get('dashboard.help_post')) !!}',
                        'memory_limit': '{!! addslashes(\App\Core\Lang::get('dashboard.help_memory')) !!}',
                        'max_execution_time': '{!! addslashes(\App\Core\Lang::get('dashboard.help_time')) !!}',
                        'max_input_vars': '{!! addslashes(\App\Core\Lang::get('dashboard.help_vars')) !!}'
                    };

                    let html = `
                                <!-- Time Section -->
                                <div class="mb-8 p-10 rounded-[2.5rem] bg-gradient-to-br from-primary/10 to-blue-500/5 border border-primary/20 shadow-2xl overflow-hidden relative group">
                                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/10 blur-[50px] rounded-full group-hover:bg-primary/20 transition-all duration-700"></div>

                                    <div class="flex flex-col md:flex-row items-center gap-10 relative z-10">
                                        <div class="w-32 h-32 rounded-full bg-black/40 border-4 border-primary/30 flex items-center justify-center flex-shrink-0 shadow-inner">
                                            <span class="text-5xl animate-pulse">🕒</span>
                                        </div>

                                        <div class="flex-1 text-center md:text-left">
                                            <h3 class="text-xs font-black text-primary uppercase tracking-[0.3em] mb-3 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-primary"></span>
                                                {!! addslashes(\App\Core\Lang::get('dashboard.server_time')) !!}
                                            </h3>
                                            <div id="server-clock" class="text-5xl md:text-6xl font-black text-white italic tracking-tighter mb-4 tabular-nums">
                                                ${data.server_time.split(' ')[1]}
                                            </div>
                                            <p class="text-xs text-p-muted font-bold uppercase tracking-widest opacity-60">
                                                ${data.server_time.split(' ')[0]} • ${data.timezone}
                                            </p>
                                        </div>

                                        <div class="w-full md:w-auto p-6 rounded-3xl bg-black/50 border border-white/5 shadow-xl">
                                            <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-4">{!! addslashes(\App\Core\Lang::get('dashboard.time_adjustment')) !!}</label>
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="flex-1 text-center">
                                                    <input type="number" id="offset-hours" value="${Math.floor(data.time_offset / 60)}" 
                                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-center text-white font-bold focus:border-primary/50 outline-none">
                                                    <span class="text-[8px] font-black text-p-muted uppercase mt-1 block">{!! addslashes(\App\Core\Lang::get('dashboard.hours')) !!}</span>
                                                </div>
                                                <span class="text-p-muted font-bold">:</span>
                                                <div class="flex-1 text-center">
                                                    <input type="number" id="offset-minutes" value="${Math.abs(data.time_offset % 60)}" 
                                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-center text-white font-bold focus:border-primary/50 outline-none">
                                                    <span class="text-[8px] font-black text-p-muted uppercase mt-1 block">{!! addslashes(\App\Core\Lang::get('dashboard.minutes')) !!}</span>
                                                </div>
                                            </div>
                                            <button onclick="updateTimeOffset()" class="w-full btn-primary !py-2.5 !text-[9px] uppercase tracking-widest shadow-lg shadow-primary/20">
                                                {!! addslashes(\App\Core\Lang::get('dashboard.adjust_time_btn')) !!}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left mb-10">
                            `;

                    for (const [key, value] of Object.entries(data)) {
                        if (['server_time', 'time_offset'].includes(key)) continue;

                        const label = key.replace(/_/g, ' ').toUpperCase();
                        const help = helpMap[key] || '';
                        let extraWarning = '';

                        if (key === 'upload_max_filesize') {
                            const warningText = '{!! addslashes(\App\Core\Lang::get('dashboard.file_size_warning')) !!}'.replace(':value', value);
                            extraWarning = `<div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl"><p class="text-xs font-bold text-amber-500 uppercase tracking-tighter italic">${warningText}</p></div>`;
                        }

                        html += `
                                    <div class="bg-white/5 p-5 rounded-2xl border border-white/5 hover:border-primary/20 transition-all group">
                                        <span class="block text-[10px] font-black text-primary tracking-widest mb-2">${label}</span>
                                        <span class="text-lg font-black text-white block mb-2">${value}</span>
                                        ${help ? `<p class="text-xs text-p-muted font-medium italic opacity-70 group-hover:opacity-100 transition-opacity">${help}</p>` : ''}
                                        ${extraWarning}
                                    </div>
                                `;
                    }

                    html += `
                                </div>
                                <div class="p-8 rounded-3xl bg-black/40 border border-white/5">
                                    <h4 class="text-xs font-black text-white uppercase tracking-widest mb-4 flex items-center gap-3">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 
                                        {!! addslashes(\App\Core\Lang::get('dashboard.needs_more_capacity')) !!}
                                    </h4>
                                    <p class="text-sm text-p-muted leading-relaxed mb-6">
                                        {!! addslashes(\App\Core\Lang::get('dashboard.modify_values_text')) !!}
                                    </p>
                                    <div class="p-4 bg-emerald-500/5 border border-emerald-500/10 rounded-2xl">
                                        <p class="text-xs md:text-sm text-p-muted leading-relaxed">
                                            {!! addslashes(\App\Core\Lang::get('dashboard.recommendation')) !!}
                                        </p>
                                    </div>
                                </div>
                            `;

                    showModal({
                        title: '{!! addslashes(\App\Core\Lang::get('dashboard.server_config')) !!}',
                        message: '',
                        type: 'modal',
                        typeLabel: '{!! addslashes(\App\Core\Lang::get('dashboard.system_env_nodes')) !!}',
                        maxWidth: 'max-w-4xl'
                    });

                    const msgContainer = document.getElementById('modal-message');
                    msgContainer.innerHTML = html;
                    msgContainer.classList.remove('text-slate-400');
                });
        }

        function updateTimeOffset() {
            const hours = document.getElementById('offset-hours').value;
            const minutes = document.getElementById('offset-minutes').value;

            const formData = new FormData();
            formData.append('hours', hours);
            formData.append('minutes', minutes);
            formData.append('_token', '{{ $csrf_token }}');

            fetch('{{ $baseUrl }}admin/system/time-offset', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSystemInfo(); // Refresh modal
                    }
                });
        }

        function triggerResetSystem() {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('dashboard.reset_title')) !!}',
                message: '{!! addslashes(\App\Core\Lang::get('dashboard.reset_msg_1')) !!}',
                type: 'confirm',
                stayOpen: true,
                confirmText: '{!! addslashes(\App\Core\Lang::get('common.confirm')) !!}',
                onConfirm: function () {
                    showModal({
                        title: '☢️ {!! addslashes(\App\Core\Lang::get('dashboard.reset_title')) !!}',
                        message: '{!! addslashes(\App\Core\Lang::get('dashboard.reset_msg_2')) !!}',
                        type: 'confirm',
                        confirmText: '{!! addslashes(\App\Core\Lang::get('dashboard.reset_confirm_btn')) !!}',
                        safetyCheck: '{!! addslashes(\App\Core\Lang::get('dashboard.confirm_checkbox')) !!}',
                        onConfirm: function () {
                            window.location.href = '{{ $baseUrl }}admin/system/reset';
                        }
                    });
                }
            });
        }

        function dismissBanner() {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('dashboard.welcome_title')) !!}',
                message: '{!! addslashes(\App\Core\Lang::get('dashboard.dismiss_banner_msg')) !!}',
                type: 'confirm',
                onConfirm: () => {
                    fetch('{{ $baseUrl }}admin/system/dismiss-banner', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            }
                        });
                }
            });
        }

        function clearCache() {
            fetch('{{ $baseUrl }}admin/system/clear-cache', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal({
                            title: '{!! addslashes(\App\Core\Lang::get('dashboard.cache_modal_title')) !!}',
                            message: '{!! addslashes(\App\Core\Lang::get('dashboard.cache_modal_msg')) !!}',
                            type: 'success'
                        });
                    }
                });
        }

        function clearSessions() {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('dashboard.sessions_modal_title')) !!}',
                message: '{!! addslashes(\App\Core\Lang::get('dashboard.sessions_modal_msg')) !!}',
                type: 'confirm',
                onConfirm: () => {
                    fetch('{{ $baseUrl }}admin/system/clear-sessions', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showModal({
                                    title: '{!! addslashes(\App\Core\Lang::get('dashboard.sessions_modal_title')) !!}',
                                    message: '{!! addslashes(\App\Core\Lang::get('dashboard.sessions_cleared_msg')) !!}'.replace(':count', data.cleared),
                                    type: 'success'
                                });
                            }
                        });
                }
            });
        }
    </script>
@endsection