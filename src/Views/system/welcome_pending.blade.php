@extends('layouts.auth')

@section('title', isset($lang['welcome_pending']['title']) ? $lang['welcome_pending']['title'] : 'Pending Approval')

@section('content')
    <div class="relative w-full max-w-2xl mx-auto animate-in fade-in slide-in-from-bottom-10 duration-1000">
        <div class="text-center mb-10">
            <div class="relative inline-block mb-6">
                <div class="absolute inset-0 bg-yellow-500/20 blur-xl rounded-full"></div>
                <div
                    class="relative w-24 h-24 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-2xl items-center justify-center flex text-dark text-5xl shadow-xl">
                    ⏳
                </div>
            </div>
            <h1 class="text-4xl font-black text-p-title tracking-tighter uppercase italic leading-none mb-2">
                {{ isset($lang['welcome_pending']['title']) ? $lang['welcome_pending']['title'] : 'Account Pending Approval' }}
            </h1>
            <p class="text-sm font-bold tracking-[0.2em] text-p-muted mt-2 uppercase opacity-70">
                {{ isset($lang['welcome_pending']['status_pending']) ? $lang['welcome_pending']['status_pending'] : 'STATUS: PENDING REVIEW' }}
            </p>
        </div>

        <div class="glass-card border-t-4 border-yellow-500 p-8">

            <div
                class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-600 dark:text-yellow-400 p-6 rounded-xl mb-8 flex items-start gap-4">
                <div class="text-2xl mt-1">👋</div>
                <div>
                    <h3 class="font-bold text-lg mb-1">
                        {{ isset($lang['welcome_pending']['subtitle']) ? $lang['welcome_pending']['subtitle'] : 'Welcome!' }}
                    </h3>
                    <p class="opacity-90 leading-relaxed">
                        {{ isset($lang['welcome_pending']['message_success']) ? $lang['welcome_pending']['message_success'] : '' }}
                        <br>
                        {{ isset($lang['welcome_pending']['message_pending']) ? $lang['welcome_pending']['message_pending'] : '' }}
                    </p>
                </div>
            </div>

            <div class="text-center space-y-4 mb-8">
                <p class="text-p-text font-medium text-lg">
                    {{ isset($lang['welcome_pending']['contact_admin']) ? $lang['welcome_pending']['contact_admin'] : '' }}
                </p>
                <p class="text-p-muted text-sm">
                    {{ isset($lang['welcome_pending']['approved_msg']) ? $lang['welcome_pending']['approved_msg'] : '' }}
                </p>
            </div>

            <div class="flex flex-col items-center gap-4 border-t border-glass-border pt-8 mt-8">
                <a href="{{ $baseUrl }}logout"
                    class="group relative inline-flex items-center justify-center gap-2 px-8 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 font-bold rounded-xl transition-all duration-300 uppercase tracking-widest text-xs border border-red-500/20 hover:border-red-500/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>{{ isset($lang['welcome_pending']['logout']) ? $lang['welcome_pending']['logout'] : 'Logout' }}</span>
                </a>

                <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em] mt-4 opacity-50">
                    Data2Rest Identity Server
                </p>
            </div>
        </div>
    </div>
@endsection