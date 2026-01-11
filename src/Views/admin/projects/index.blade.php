@extends('layouts.main')

@section('title', \App\Core\Lang::get('projects.title', 'Project Management'))

@section('content')
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <a href="{{ $baseUrl }}admin/projects/select"
                    class="text-[10px] font-black uppercase text-primary hover:underline mb-4 inline-flex items-center gap-2">
                    &larr; {{ \App\Core\Lang::get('common.back_selector') }}
                </a>
                <h1 class="text-4xl font-black text-p-title tracking-tight mb-2">
                    {{ \App\Core\Lang::get('projects.title', 'Project Management') }}
                </h1>
                <p class="text-p-muted font-medium">
                    {{ \App\Core\Lang::get('projects.subtitle', 'Manage multi-tenant isolation and subscription plans.') }}
                </p>
            </div>
            @if (\App\Core\Auth::isAdmin())
                <a href="{{ $baseUrl }}admin/projects/new"
                    class="px-8 py-4 bg-primary text-white rounded-2xl font-bold shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 group">
                    <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ \App\Core\Lang::get('projects.new_btn', 'New Project') }}
                </a>
            @endif
        </div>

        <!-- Stats/Context Alert -->
        <div class="glass-card mb-8 flex items-center gap-4 bg-primary/5 border-primary/20">
            <div class="p-3 bg-primary/20 rounded-xl text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <span class="text-sm font-bold block">
                    {{ \App\Core\Lang::get('projects.active_context', 'Active Project Context:') }}
                    <span class="text-primary">
                        {{ \App\Core\Auth::getActiveProject() ?: 'None' }}
                    </span>
                </span>
                <p class="text-xs text-p-muted">
                    {{ \App\Core\Lang::get('projects.context_note', 'All database operations are currently isolated to the selected project.') }}
                </p>
            </div>
        </div>

        <!-- Projects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($projects as $project)
                @php
                    $isActive = \App\Core\Auth::getActiveProject() == $project['id'];
                    $planClass = ($project['plan_type'] ?? '') === 'annual' ? 'bg-amber-500/10 text-amber-500' : 'bg-primary/10 text-primary';
                    $isExpired = strtotime($project['next_billing_date'] ?? '') < time();
                @endphp
                <div
                    class="glass-card group hover:scale-[1.02] transition-all duration-300 {{ $isActive ? 'border-primary shadow-2xl shadow-primary/10' : '' }}">
                    <div class="flex justify-between items-start mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $isActive ? 'from-primary to-blue-600' : 'from-gray-100 to-gray-200 dark:from-slate-800 dark:to-slate-700' }} flex items-center justify-center text-white shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $planClass }}">
                            {{ $project['plan_type'] ?? 'No Plan' }}
                        </span>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-xl font-black text-p-title mb-2 group-hover:text-primary transition-colors">
                            {{ $project['name'] }}
                        </h3>
                        <p class="text-sm text-p-muted line-clamp-2 leading-relaxed">
                            {{ $project['description'] ?: \App\Core\Lang::get('common.no_description') }}
                        </p>
                    </div>

                    <div class="space-y-3 mb-8">
                        <div class="flex justify-between text-xs font-bold">
                            <span
                                class="text-p-muted uppercase tracking-tighter">{{ \App\Core\Lang::get('common.next_billing') }}</span>
                            <span class="{{ $isExpired ? 'text-red-500' : 'text-p-text' }}">
                                {{ date('M d, Y', strtotime($project['next_billing_date'] ?? 'now')) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs font-bold">
                            <span
                                class="text-p-muted uppercase tracking-tighter">{{ \App\Core\Lang::get('common.status') }}</span>
                            <span
                                class="text-emerald-500 uppercase tracking-tighter">{{ \App\Core\Lang::get('common.active') }}</span>
                        </div>
                        @if(isset($project['storage']))
                            <div class="pt-2">
                                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest mb-2">
                                    <span class="text-p-muted">{{ \App\Core\Lang::get('projects.storage_quota') }}</span>
                                    <span class="text-p-title">{{ $project['storage']['used_mb'] }} /
                                        {{ $project['storage']['quota_mb'] }} MB</span>
                                </div>
                                <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary transition-all duration-1000"
                                        style="width: {{ $project['storage']['percent'] }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ $baseUrl }}admin/projects/switch?id={{ $project['id'] }}"
                            class="flex-1 py-3 {{ $isActive ? 'bg-primary/10 text-primary pointer-events-none' : 'bg-primary text-white hover:bg-primary/90' }} rounded-xl text-center text-sm font-black uppercase tracking-widest transition-all">
                            {{ $isActive ? \App\Core\Lang::get('common.selected') : \App\Core\Lang::get('common.select') }}
                        </a>
                        @if (\App\Core\Auth::isAdmin())
                            <a href="{{ $baseUrl }}admin/projects/edit?id={{ $project['id'] }}"
                                class="px-3 py-3 bg-p-input border border-glass-border text-p-text hover:bg-p-text hover:text-white rounded-xl transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection