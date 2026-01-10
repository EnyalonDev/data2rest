@extends('layouts.main')

@section('title', ($id ? \App\Core\Lang::get('users.verify') : \App\Core\Lang::get('users.manifest')))

@section('content')
    <header class="mb-12 text-center">
        <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
            {{ $id ? \App\Core\Lang::get('users.verify') : \App\Core\Lang::get('users.manifest') }}
        </h1>
        <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('users.desc') }}</p>
    </header>

    <form action="{{ $baseUrl }}admin/users/save" method="POST" class="space-y-8 max-w-4xl mx-auto">
        {!! $csrf_field !!}
        <input type="hidden" name="id" value="{{ $user['id'] ?? '' }}">

        <section class="glass-card space-y-6">
            <div>
                <label class="form-label">{{ \App\Core\Lang::get('users.username') }}</label>
                <input type="text" name="username" value="{{ $user['username'] ?? '' }}" required class="form-input"
                    placeholder="{{ \App\Core\Lang::get('users.username_placeholder') }}">
            </div>
            <div>
                <label class="form-label">{{ \App\Core\Lang::get('users.password') }}
                    @if($user) <span
                        class="text-amber-500/50 italic">({{ \App\Core\Lang::get('users.password_hint') }})</span>
                    @endif</label>
                <input type="password" name="password" id="password-input" {{ $user ? '' : 'required' }} class="form-input"
                    placeholder="{{ \App\Core\Lang::get('users.password_placeholder') }}">
                <!-- Password Strength -->
                <div class="mt-3 flex gap-1 h-1">
                    <div id="strength-1" class="flex-1 bg-white/5 rounded-full transition-all duration-500"></div>
                    <div id="strength-2" class="flex-1 bg-white/5 rounded-full transition-all duration-500"></div>
                    <div id="strength-3" class="flex-1 bg-white/5 rounded-full transition-all duration-500"></div>
                </div>
                <p id="strength-text" class="text-[8px] font-black uppercase tracking-[0.2em] text-p-muted mt-2">
                    {{ \App\Core\Lang::get('users.security') }}: {{ \App\Core\Lang::get('users.security_levels.null') }}
                </p>
            </div>
            <div>
                <label class="form-label">{{ \App\Core\Lang::get('users.role') }}</label>
                <select name="role_id" required class="form-input">
                    @foreach ($roles as $r)
                        <option value="{{ $r['id'] }}" {{ ($user['role_id'] ?? '') == $r['id'] ? 'selected' : '' }}>
                            {{ $r['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ \App\Core\Lang::get('common.groups') }}</label>
                <select name="group_id" class="form-input">
                    <option value="">{{ \App\Core\Lang::get('common.none') }}</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g['id'] }}" {{ ($user['group_id'] ?? '') == $g['id'] ? 'selected' : '' }}>
                            {{ $g['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="pt-4 flex items-center justify-between">
                <span
                    class="text-[10px] font-black uppercase text-p-muted tracking-widest">{{ \App\Core\Lang::get('users.status') }}</span>
                <label class="flex items-center gap-4 cursor-pointer">
                    <input type="checkbox" name="status" value="1" {{ ($user['status'] ?? 1) ? 'checked' : '' }}
                        class="w-6 h-6 rounded bg-black/40 text-primary border-glass-border">
                    <span
                        class="text-xs font-black uppercase tracking-widest">{{ \App\Core\Lang::get('users.active') }}</span>
                </label>
            </div>
        </section>

        <div class="flex justify-center pt-8 gap-4">
            <a href="{{ $baseUrl }}admin/users"
                class="btn-primary !bg-slate-800 !text-slate-300">{{ \App\Core\Lang::get('common.abort') }}</a>
            <button type="submit" class="btn-primary">{{ \App\Core\Lang::get('common.commit') }}</button>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passInput = document.getElementById('password-input');
            const strengthTexts = [
                '{!! addslashes(\App\Core\Lang::get('users.security_levels.null')) !!}',
                '{!! addslashes(\App\Core\Lang::get('users.security_levels.weak')) !!}',
                '{!! addslashes(\App\Core\Lang::get('users.security_levels.medium')) !!}',
                '{!! addslashes(\App\Core\Lang::get('users.security_levels.industrial')) !!}'
            ];
            const strengthColors = ['bg-white/5', 'bg-red-500', 'bg-amber-500', 'bg-emerald-500'];
            const securityPrefix = '{!! addslashes(\App\Core\Lang::get('users.security')) !!}: ';

            passInput.addEventListener('input', () => {
                const val = passInput.value;
                let strength = 0;

                if (val.length > 5) strength++;
                if (val.length > 8 && /[A-Z]/.test(val) && /[0-9]/.test(val)) strength++;
                if (val.length > 12 && /[^A-Za-z0-9]/.test(val)) strength++;

                // Update UI
                document.getElementById('strength-text').innerText = securityPrefix + strengthTexts[strength];
                for (let i = 1; i <= 3; i++) {
                    const bar = document.getElementById('strength-' + i);
                    bar.className = 'flex-1 rounded-full transition-all duration-500 ' + (i <= strength ? strengthColors[strength] : 'bg-white/5');
                }

                if (strength > 0) {
                    passInput.classList.add('form-input-valid');
                    passInput.classList.remove('form-input-error');
                } else if (val.length > 0) {
                    passInput.classList.add('form-input-error');
                    passInput.classList.remove('form-input-valid');
                }
            });

            // Global validation for other inputs
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                if (input.id === 'password-input') return;
                input.addEventListener('input', () => {
                    if (input.hasAttribute('required') && !input.value.trim()) {
                        input.classList.add('form-input-error');
                        input.classList.remove('form-input-valid');
                    } else if (input.value.trim()) {
                        input.classList.remove('form-input-error');
                        input.classList.add('form-input-valid');
                    }
                });
            });
        });
    </script>
@endsection