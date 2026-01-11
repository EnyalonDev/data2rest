@extends('layouts.main')

@section('title', 'System Backups')

@section('content')
    <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-p-title tracking-tight mb-2">System Backups</h1>
            <p class="text-p-muted font-medium">Create backups of your databases and push them to the cloud.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="document.getElementById('config-modal').classList.remove('hidden')"
                class="btn-outline flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.47a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
                    </path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                {{ \App\Core\Lang::get('backups.config_btn') }}
            </button>
            <form action="{{ \App\Core\Auth::getBaseUrl() }}admin/backups/create" method="POST">
                <input type="hidden" name="_token" value="{{ $csrf_token }}">
                <button type="submit" class="btn-primary flex items-center gap-2" onclick="showLoading()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    {{ \App\Core\Lang::get('backups.create') }}
                </button>
            </form>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-6">
        @forelse($backups as $backup)
            <div
                class="glass-card !p-6 flex flex-col md:flex-row items-center justify-between gap-4 group hover:border-primary/20 transition-all">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-2xl">📦</div>
                    <div>
                        <h3 class="text-sm font-bold text-p-title">{{ $backup['name'] }}</h3>
                        <div class="flex gap-4 text-[10px] text-p-muted font-mono mt-1">
                            <span>{{ date('Y-m-d H:i:s', $backup['date']) }}</span>
                            <span>{{ round($backup['size'] / 1024 / 1024, 2) }} MB</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button onclick="uploadBackup('{{ $backup['name'] }}')"
                        class="px-4 py-2 rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all text-xs font-bold uppercase tracking-widest flex items-center gap-2"
                        title="Upload to Google Drive">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.5 19c0-3.037-2.463-5.5-5.5-5.5S6.5 15.963 6.5 19"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        {{ \App\Core\Lang::get('backups.cloud_sync') }}
                    </button>
                    <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/backups/download?file={{ $backup['name'] }}"
                        class="p-2 hover:bg-white/10 rounded-lg text-p-muted hover:text-p-title transaction-colors"
                        title="{{ \App\Core\Lang::get('backups.download') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </a>
                    <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/backups/delete?file={{ $backup['name'] }}"
                        onclick="return confirm('{{ \App\Core\Lang::get('backups.delete_confirm') }}')"
                        class="p-2 hover:bg-red-500/10 rounded-lg text-p-muted hover:text-red-500 transaction-colors"
                        title="{{ \App\Core\Lang::get('backups.delete') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="glass-card py-20 text-center">
                <div
                    class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl opacity-50">
                    🛡️</div>
                <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('backups.empty') }}</h3>
                <p class="text-p-muted">{{ \App\Core\Lang::get('backups.empty_desc') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Config Modal -->
    <div id="config-modal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-lg shadow-2xl border-2 border-white/10">
            <h3 class="text-xl font-bold text-p-title mb-4">{{ \App\Core\Lang::get('backups.config_title') }}</h3>
            <form action="{{ \App\Core\Auth::getBaseUrl() }}admin/backups/config" method="POST" class="space-y-6">
                <input type="hidden" name="_token" value="{{ $csrf_token }}">
                <div>
                    <label
                        class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">{{ \App\Core\Lang::get('backups.google_script_url') }}</label>
                    <input type="url" name="cloud_url" value="{{ $cloud_url }}"
                        placeholder="https://script.google.com/macros/s/..." class="form-input w-full font-mono text-sm"
                        required>
                    <p class="text-[10px] text-p-muted mt-2 leading-relaxed">
                        {!! \App\Core\Lang::get('backups.deploy_help') !!}
                    </p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('config-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg hover:bg-white/5 text-p-muted text-xs font-bold uppercase tracking-widest">{{ \App\Core\Lang::get('common.cancel') }}</button>
                    <button type="submit"
                        class="btn-primary px-6 text-xs font-bold uppercase tracking-widest">{{ \App\Core\Lang::get('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function uploadBackup(filename) {
            if (!confirm('{{ \App\Core\Lang::get('backups.upload_confirm', ['file' => '']) }}' + filename)) return;

            // Show spinner or transforming button
            showModal({
                title: '{{ \App\Core\Lang::get('backups.uploading') }}',
                message: '{{ \App\Core\Lang::get('backups.upload_wait') }}',
                type: 'info' // assuming system_modal.blade.php supports info or just generic
            });

            const formData = new FormData();
            formData.append('_token', '{{ $csrf_token }}'); // If csrf is needed

            try {
                const res = await fetch('{{ \App\Core\Auth::getBaseUrl() }}admin/backups/upload?file=' + encodeURIComponent(filename), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ $csrf_token }}'
                    }
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Non-JSON response:', text);
                    showModal({ title: 'Server Error', message: 'The server returned an unexpected response (check console). Preview: ' + text.substring(0, 100), type: 'error' });
                    return;
                }

                if (data.success) {
                    showModal({ title: 'Success', message: '{{ \App\Core\Lang::get('backups.upload_success') }}', type: 'success' });
                } else {
                    showModal({ title: 'Upload Failed', message: data.error || data.message || 'Unknown error occurred.', type: 'error' });
                }
            } catch (e) {
                console.error(e);
                showModal({ title: 'Connection Error', message: 'Could not connect to server: ' + e.message, type: 'error' });
            }
        }

        function showLoading() {
            // Simple visual feedback
            document.body.style.cursor = 'wait';
        }
    </script>
@endsection