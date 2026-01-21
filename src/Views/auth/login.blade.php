@extends('layouts.auth')

@section('title', 'Login - Data2Rest')

@section('content')
    <div class="w-full max-w-md mx-auto animate-in fade-in slide-in-from-bottom-10 duration-1000">
        <div class="text-center mb-10">
            <div class="relative inline-block mb-6">
                <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full"></div>
                <div
                    class="relative w-20 h-20 bg-gradient-to-br from-primary to-blue-600 rounded-2xl items-center justify-center flex text-dark text-4xl font-black shadow-xl">
                    D</div>
            </div>
            <h1 class="text-5xl font-black text-p-title tracking-tighter uppercase italic leading-none">Data<span
                    class="text-primary italic">2</span>Rest</h1>
            <p class="text-[10px] font-black tracking-[0.4em] text-p-muted mt-4 uppercase opacity-50">Industrial Grade
                Database Gateway</p>
        </div>

        <div class="glass-card border-t-4 border-primary">
            @if(isset($error))
                <div
                    class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-8 text-[10px] font-black uppercase tracking-widest text-center animate-bounce">
                    {{ $error }}
                </div>
            @endif

            <form action="{{ $baseUrl }}login" method="POST" class="space-y-6">
                {!! $csrf_field !!}
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] ml-1">Universal
                        Identifier</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-p-muted text-lg">👤</span>
                        <input type="text" name="username" placeholder="Username" required class="form-input pl-12">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] ml-1">Security
                        Token</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-p-muted text-lg">🔑</span>
                        <input type="password" name="password" id="login-password" placeholder="••••••••" required
                            class="form-input pl-12 pr-12">
                        <button type="button" onclick="togglePassword('login-password', this)"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-p-muted hover:text-primary transition-colors focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                    class="group relative w-full bg-primary text-dark font-black py-4 rounded-xl transition-all duration-300 hover:scale-[1.02] active:scale-95 shadow-xl shadow-primary/20 uppercase tracking-widest text-sm mt-4 overflow-hidden">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        Ingresar <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </span>
                    <div
                        class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500">
                    </div>
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-glass-border text-center">
                <p class="text-[10px] font-bold text-p-muted uppercase tracking-[0.2em] mb-4">Environment: <span
                        class="text-emerald-500">SECURE_CLUSTER</span></p>
                <div class="flex justify-center gap-4">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                </div>
            </div>
        </div>

        <footer class="mt-12 text-center opacity-40 hover:opacity-100 transition-opacity">
            <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                © 2026 EnyalonDev Framework By Néstor Ovallos Cañasz <br /> <a href="https://nestorovallos.com"
                    target="_blank" class="text-primary hover:underline">Support Node</a>
            </p>
        </footer>
    </div>
@endsection