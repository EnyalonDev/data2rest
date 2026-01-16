@extends('layouts.main')

@section('title', 'Connection Manager')

@section('content')
    <header class="mb-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
                    Connection Manager
                </h1>
                <p class="text-p-muted font-medium tracking-tight">Monitor and manage your active database connections</p>
            </div>
            @if(\App\Core\Auth::hasPermission('module:databases.create_db'))
                <a href="{{ $baseUrl }}admin/databases/create-form"
                    class="btn-primary flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Database
                </a>
            @endif
        </div>
    </header>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-12">
        <div class="glass-card p-4 text-center group hover:bg-white/5 transition-colors">
            <div class="text-3xl font-black text-p-title mb-1 group-hover:scale-110 transition-transform">
                {{ $stats['total'] }}</div>
            <div class="text-[10px] uppercase tracking-widest text-p-muted font-bold">Total DBs</div>
        </div>
        <div class="glass-card p-4 text-center group hover:bg-white/5 transition-colors">
            <div class="text-3xl font-black text-green-500 mb-1 group-hover:scale-110 transition-transform">
                {{ $stats['connected'] }}</div>
            <div class="text-[10px] uppercase tracking-widest text-p-muted font-bold">Connected</div>
        </div>
        <div class="glass-card p-4 text-center group hover:bg-white/5 transition-colors">
            <div class="text-3xl font-black text-blue-500 mb-1 group-hover:scale-110 transition-transform">
                {{ $stats['sqlite'] }}</div>
            <div class="text-[10px] uppercase tracking-widest text-p-muted font-bold">SQLite</div>
        </div>
        <div class="glass-card p-4 text-center group hover:bg-white/5 transition-colors">
            <div class="text-3xl font-black text-orange-500 mb-1 group-hover:scale-110 transition-transform">
                {{ $stats['mysql'] }}</div>
            <div class="text-[10px] uppercase tracking-widest text-p-muted font-bold">MySQL</div>
        </div>
        <div class="glass-card p-4 text-center group hover:bg-white/5 transition-colors">
            <div class="text-3xl font-black text-purple-500 mb-1 group-hover:scale-110 transition-transform">
                {{ $stats['total_size_formatted'] }}</div>
            <div class="text-[10px] uppercase tracking-widest text-p-muted font-bold">Total Size</div>
        </div>
    </div>

    <!-- Connections Grid -->
    @if(empty($databases))
        <div class="glass-card py-16 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center text-primary mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-p-title mb-2">No Databases Found</h3>
            <p class="text-p-muted mb-6 max-w-sm">You haven't created any database connections yet. Start by creating one to
                manage your data.</p>
            <a href="{{ $baseUrl }}admin/databases/create-form"
                class="btn-primary px-8 py-3 rounded-xl font-black uppercase tracking-widest text-xs">
                Create First Database
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($databases as $db)
                <div
                    class="glass-card group relative overflow-hidden flex flex-col h-full hover:border-primary/30 transition-colors">
                    <div
                        class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                    </div>

                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4 relative z-10">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center text-lg {{ $db['db_type'] === 'sqlite' ? 'bg-blue-500/10 text-blue-500' : 'bg-orange-500/10 text-orange-500' }}">
                                {{ $db['db_type'] === 'sqlite' ? '💾' : '🐬' }}
                            </div>
                            <div>
                                <h3 class="font-bold text-p-title uppercase italic truncate max-w-[150px]"
                                    title="{{ $db['name'] }}">
                                    {{ $db['name'] }}
                                </h3>
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest {{ $db['db_type'] === 'sqlite' ? 'text-blue-500' : 'text-orange-500' }}">
                                    {{ strtoupper($db['db_type']) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-white/5 border border-white/10 text-[10px] font-bold uppercase tracking-wider {{ $db['is_connected'] ? 'text-green-500 border-green-500/20 bg-green-500/5' : 'text-red-500 border-red-500/20 bg-red-500/5' }}">
                                <span
                                    class="w-1.5 h-1.5 rounded-full {{ $db['is_connected'] ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                                {{ $db['is_connected'] ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-3 mb-6 flex-grow relative z-10">
                        <div class="flex justify-between text-xs py-2 border-b border-white/5">
                            <span class="text-p-muted font-medium">Size</span>
                            <span class="text-p-title font-bold">{{ $db['size_formatted'] }}</span>
                        </div>

                        @if($db['db_type'] === 'sqlite')
                            <div class="flex justify-between text-xs py-2 border-b border-white/5">
                                <span class="text-p-muted font-medium">Path</span>
                                <span class="text-p-title font-bold truncate max-w-[150px]" title="{{ basename($db['path']) }}">
                                    {{ basename($db['path']) }}
                                </span>
                            </div>
                        @else 
                            @php 
                                $config = isset($db['config']) && $db['config'] ? json_decode($db['config'], true) : []; 
                            @endphp
                            <div class="flex justify-between text-xs py-2 border-b border-white/5">
                                <span class="text-p-muted font-medium">Host</span>
                                <span class="text-p-title font-bold">{{ $config['host'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-xs py-2 border-b border-white/5">
                                <span class="text-p-muted font-medium">DB Name</span>
                                <span class="text-p-title font-bold truncate max-w-[150px]">{{ $config['database'] ?? 'N/A' }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-xs py-2 border-b border-white/5">
                            <span class="text-p-muted font-medium">Created</span>
                            <span class="text-p-title font-mono">{{ date('Y-m-d', strtotime($db['created_at'])) }}</span>
                        </div>

                        @if(isset($db['error']))
                            <div class="bg-red-500/10 border border-red-500/20 rounded p-2 mt-2">
                                <p class="text-[10px] text-red-400 font-medium truncate" title="{{ $db['error'] }}">
                                    Error: {{ $db['error'] }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-3 gap-2 mt-auto relative z-10 pt-4 border-t border-white/5">
                        <a href="{{ $baseUrl }}admin/databases/view?id={{ $db['id'] }}"
                            class="flex items-center justify-center p-2 rounded-lg bg-white/5 hover:bg-primary/20 hover:text-primary transition-colors text-p-muted"
                            title="View Tables">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </a>
                        <a href="{{ $baseUrl }}admin/databases/edit?id={{ $db['id'] }}"
                            class="flex items-center justify-center p-2 rounded-lg bg-white/5 hover:bg-blue-500/20 hover:text-blue-500 transition-colors text-p-muted"
                            title="Configure">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        <button
                            onclick="if(confirm('Are you sure? This action cannot be undone.')) window.location.href='{{ $baseUrl }}admin/databases/delete?id={{ $db['id'] }}'"
                            class="flex items-center justify-center p-2 rounded-lg bg-white/5 hover:bg-red-500/20 hover:text-red-500 transition-colors text-p-muted"
                            title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection