@extends('layouts.main')

@section('content')
    <!-- 
            Profile Header 
            Simple title and subtitle for the User Profile section.
        -->
    <div class="max-w-4xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-p-title italic tracking-tight uppercase">
                    {{ \App\Core\Lang::get('profile.title') }}
                </h1>
                <p class="text-xs text-p-muted font-medium mt-1">
                    {{ \App\Core\Lang::get('profile.subtitle') }}
                </p>
            </div>
        </div>

        <form action="{{ $baseUrl }}admin/profile/save" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {!! $csrf_field !!}

            <!-- 
                    Sidebar: Basic Info Card 
                    ReadOnly card displaying user avatar, username, role, and metadata (ID, Member Since).
                -->
            <div class="md:col-span-1 space-y-6">
                <div class="glass-card flex flex-col items-center text-center p-8">
                    <div
                        class="w-24 h-24 rounded-3xl bg-primary/20 flex items-center justify-center text-primary text-4xl font-black mb-4 border border-primary/20 shadow-xl shadow-primary/10">
                        {{ strtoupper(substr($user['username'] ?? 'U', 0, 1)) }}
                    </div>
                    <h2 class="text-lg font-black text-p-title">{{ $user['username'] }}</h2>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-p-muted mt-1">
                        {{ $user['role_name'] ?? 'Agent' }}
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/5 w-full space-y-4">
                        <div class="flex justify-between text-[11px] font-medium">
                            <span class="text-p-muted">ID:</span>
                            <span class="text-p-title font-mono">#{{ $user['id'] }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] font-medium">
                            <span class="text-p-muted">{{ \App\Core\Lang::get('profile.member_since') }}:</span>
                            <span class="text-p-title">{{ date('M Y', strtotime($user['created_at'])) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 
                    Main Form Area 
                    Fields for editing personal information (Name, Email, Phone, Address) and Security settings (Password).
                -->
            <div class="md:col-span-2 space-y-6">
                <div class="glass-card p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-p-title uppercase tracking-widest">
                            {{ \App\Core\Lang::get('profile.personal_info') }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('profile.public_name') }}</label>
                            <input type="text" name="public_name" value="{{ $user['public_name'] ?? '' }}"
                                class="form-input" placeholder="Ej. John Doe">
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('profile.email') }}</label>
                            <input type="email" name="email" value="{{ $user['email'] ?? '' }}" class="form-input"
                                placeholder="email@example.com">
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('profile.phone') }}</label>
                            <input type="text" name="phone" value="{{ $user['phone'] ?? '' }}" class="form-input"
                                placeholder="+1 234 567 890">
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('profile.address') }}</label>
                            <textarea name="address" class="form-input min-h-[42px] max-h-[120px] resize-y" rows="1"
                                placeholder="Calle principal #123...">{{ $user['address'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="mt-12 pt-8 border-t border-white/5">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-sm font-black text-p-title uppercase tracking-widest">
                                {{ \App\Core\Lang::get('profile.security') }}
                            </h3>
                        </div>

                        <div class="space-y-2 max-w-sm">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('profile.new_password') }}</label>
                            <div class="relative">
                                <input type="password" name="new_password" class="form-input pr-10" id="password_field"
                                    placeholder="{{ \App\Core\Lang::get('profile.leave_blank') }}">
                                <button type="button" onclick="togglePasswordVisibility('password_field')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-p-muted hover:text-white transition-colors">
                                    <svg id="eye_icon" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-[9px] text-p-muted italic opacity-60">
                                {{ \App\Core\Lang::get('profile.password_help') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-12 flex justify-end">
                        <button type="submit"
                            class="btn-primary flex items-center gap-3 px-10 !py-4 font-black uppercase tracking-widest text-[11px]">
                            {{ \App\Core\Lang::get('profile.save_changes') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }
    </script>
@endsection