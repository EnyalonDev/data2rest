@extends('layouts.main')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-black text-p-title tracking-tight mb-2">Google Login Configuration</h2>
            <p class="text-sm font-medium text-p-muted">Configure OAuth2 credentials to enable 'Login with Google'.</p>
        </div>
        <a href="{{ $baseUrl }}admin/dashboard" class="flex items-center gap-2 px-6 py-3 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-p-muted hover:bg-white/10 hover:text-p-title transition-all">
            <span>&larr;</span> {{ isset($lang['common']['back']) ? $lang['common']['back'] : 'Back' }}
        </a>
    </div>

    <div class="glass-card mb-8">
        <form action="{{ $baseUrl }}admin/settings/google" method="POST">
            {!! $csrf_field !!}
            
            <!-- Toggle Switch -->
            <div class="flex items-center justify-between p-6 bg-primary/5 border border-primary/10 rounded-2xl mb-8">
                <div>
                    <label class="block text-lg font-bold text-p-title mb-1" for="google_login_enabled">
                        Enable Google Login
                    </label>
                    <p class="text-xs text-p-muted font-medium">
                        If disabled, the login button will be hidden from the login page.
                    </p>
                </div>
                <div class="relative inline-block w-14 mr-2 align-middle select-none transition duration-200 ease-in">
                    <input type="checkbox" name="google_login_enabled" id="google_login_enabled" value="1" class="toggle-checkbox absolute block w-8 h-8 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer peer checked:right-0 checked:border-primary" {{ ($settings['google_login_enabled'] ?? '0') == '1' ? 'checked' : '' }} style="right: 1.5rem; transition: right 0.2s;">
                    <label for="google_login_enabled" class="toggle-label block overflow-hidden h-8 rounded-full bg-gray-300 cursor-pointer peer-checked:bg-primary transition-colors"></label>
                </div>
                <style>
                    #google_login_enabled:checked { right: 0; border-color: #38bdf8; }
                    #google_login_enabled:checked + label { background-color: #38bdf8; }
                </style>
            </div>

            <!-- Client ID -->
            <div class="mb-6">
                <label for="google_client_id" class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Client ID</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-p-muted">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                    </div>
                    <input type="text" class="form-input pl-12" id="google_client_id" name="google_client_id" value="{{ $settings['google_client_id'] ?? '' }}" placeholder="123456789-abc..." required>
                </div>
            </div>

            <!-- Client Secret -->
            <div class="mb-6">
                <label for="google_client_secret" class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Client Secret</label>
                <div class="relative flex">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-p-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </div>
                    <input type="password" class="form-input pl-12 rounded-r-none border-r-0" id="google_client_secret" name="google_client_secret" value="{{ !empty($settings['google_client_secret']) ? '••••••••••••••••••••••••••••••' : '' }}" placeholder="Enter new secret to update..." required>
                    <button type="button" onclick="toggleSecretVisibility()" class="px-4 bg-p-input border-2 border-l-0 border-glass-border rounded-r-2xl hover:bg-white/5 text-p-muted hover:text-primary transition-colors">
                        <svg id="secret-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                </div>
                @if(!empty($settings['google_client_secret']))
                    <p class="mt-2 text-[10px] font-bold text-emerald-500 uppercase tracking-widest flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Secret is configured
                    </p>
                @endif
            </div>

            <!-- Redirect URI -->
            <div class="mb-8">
                <label for="google_redirect_uri" class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Authorized Redirect URI</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-p-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    </div>
                    <input type="text" class="form-input pl-12" id="google_redirect_uri" name="google_redirect_uri" value="{{ $settings['google_redirect_uri'] ?? 'https://nestorovallos.dev/auth/google/callback' }}" placeholder="https://yourdomain.com/auth/google/callback" required>
                </div>
                <p class="mt-2 text-xs text-p-muted">Must match exactly what is configured in Google Cloud Console.</p>
            </div>

            <div class="flex justify-end pt-6 border-t border-glass-border">
                <button type="submit" class="btn-primary space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    <span>Save Configuration</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Instructions -->
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] pl-4 border-l-2 border-primary">Integration Steps</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="glass-card !p-6 border-t-0 border-l-4 {{ class_exists('Google\Client') ? 'border-l-emerald-500/50' : 'border-l-red-500/50' }}">
            <div class="mb-4 {{ class_exists('Google\Client') ? 'text-emerald-500' : 'text-red-500' }} font-bold text-lg">00. Library</div>
            <p class="text-sm text-p-muted mb-2">Install PHP Library</p>
            @if(class_exists('Google\Client'))
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    INSTALLED
                </p>
            @else
                <p class="text-xs text-p-muted opacity-80 break-all mb-2">Run in terminal:</p>
                <code class="block bg-black/20 px-2 py-1 rounded text-red-400 text-[10px] font-mono">composer require google/apiclient</code>
            @endif
        </div>

        <div class="glass-card !p-6 border-t-0 border-l-4 border-l-blue-500/50">
            <div class="mb-4 text-blue-500 font-bold text-lg">01. Create Project</div>
            <p class="text-sm text-p-muted mb-2">Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-primary hover:underline font-bold">Google Cloud Console</a>.</p>
            <p class="text-xs text-p-muted opacity-80">Create a new project specifically for this application.</p>
        </div>

        <div class="glass-card !p-6 border-t-0 border-l-4 border-l-indigo-500/50">
            <div class="mb-4 text-indigo-500 font-bold text-lg">02. OAuth Consent</div>
            <p class="text-sm text-p-muted mb-2">Configure the OAuth consent screen.</p>
            <p class="text-xs text-p-muted opacity-80">Select "External" user type and add your support email.</p>
        </div>

        <div class="glass-card !p-6 border-t-0 border-l-4 border-l-purple-500/50">
            <div class="mb-4 text-purple-500 font-bold text-lg">03. Credentials</div>
            <p class="text-sm text-p-muted mb-2">Create "OAuth 2.0 Client IDs".</p>
            <p class="text-xs text-p-muted opacity-80">Select "Web application" type.</p>
        </div>

        <div class="glass-card !p-6 border-t-0 border-l-4 border-l-emerald-500/50">
            <div class="mb-4 text-emerald-500 font-bold text-lg">04. Redirection</div>
            <p class="text-sm text-p-muted mb-2">Add "Authorized Redirect URI".</p>
            <p class="text-xs text-p-muted opacity-80 break-all"><code class="bg-black/20 px-1 py-0.5 rounded text-emerald-400 select-all">{{ $settings['google_redirect_uri'] ?? '...' }}</code></p>
        </div>
    </div>
</div>

<script>
function toggleSecretVisibility() {
    const input = document.getElementById('google_client_secret');
    const icon = document.getElementById('secret-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />';
        if(input.value === '••••••••••••••••••••••••••••••') input.value = '';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
    }
}
</script>
@endsection