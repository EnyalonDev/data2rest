@extends('layouts.main')

@section('title', 'Webhooks')

@section('content')
    <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-p-title tracking-tight mb-2">{{ \App\Core\Lang::get('webhooks.title') }}
            </h1>
            <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('webhooks.subtitle') }}</p>
        </div>
        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/new" class="btn-primary flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            {{ \App\Core\Lang::get('webhooks.new') }}
        </a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Webhook List -->
        <div class="lg:col-span-3 space-y-4">
            @forelse($webhooks as $webhook)
                <div
                    class="glass-card !p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 group hover:border-primary/30 transition-all">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl {{ $webhook['status'] ? 'bg-primary/10 text-primary' : 'bg-white/5 text-p-muted' }} flex items-center justify-center text-2xl">
                            ⚡
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-p-title flex items-center gap-3">
                                {{ $webhook['name'] }}
                                @if(!$webhook['status'])
                                    <span
                                        class="px-2 py-0.5 rounded bg-white/10 text-p-muted text-[10px] font-black uppercase tracking-widest">{{ \App\Core\Lang::get('webhooks.inactive') }}</span>
                                @endif
                            </h3>
                            <code
                                class="text-xs font-mono text-p-muted bg-black/30 px-2 py-1 rounded mt-1 block w-fit max-w-[300px] truncate">{{ $webhook['url'] }}</code>
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach(explode(',', $webhook['events']) as $evt)
                                    <span
                                        class="px-2 py-0.5 rounded border border-white/10 text-xs text-p-muted font-medium">{{ $evt }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 self-end md:self-center">
                        <button onclick="testWebhook({{ $webhook['id'] }})"
                            class="p-2 hover:bg-white/5 text-p-muted hover:text-primary rounded-lg transition-all"
                            title="{{ \App\Core\Lang::get('webhooks.test') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                        </button>
                        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/logs?id={{ $webhook['id'] }}"
                            class="p-2 hover:bg-white/5 text-p-muted hover:text-blue-400 rounded-lg transition-all"
                            title="{{ \App\Core\Lang::get('webhooks.logs') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </a>
                        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/edit?id={{ $webhook['id'] }}"
                            class="p-2 hover:bg-white/5 text-p-muted hover:text-p-text rounded-lg transition-all"
                            title="{{ \App\Core\Lang::get('common.edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/delete?id={{ $webhook['id'] }}"
                            onclick="return confirm('{{ \App\Core\Lang::get('webhooks.delete_confirm_msg') }}')"
                            class="p-2 hover:bg-white/5 text-p-muted hover:text-red-500 rounded-lg transition-all"
                            title="{{ \App\Core\Lang::get('common.delete') }}">
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
                        class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl opacity-50">
                        🔌</div>
                    <h3 class="text-xl font-bold text-p-title mb-2">{{ \App\Core\Lang::get('webhooks.no_webhooks') }}</h3>
                    <p class="text-p-muted mb-6">{{ \App\Core\Lang::get('webhooks.no_webhooks_desc') }}</p>
                    <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/new"
                        class="btn-primary inline-flex items-center gap-2">
                        {{ \App\Core\Lang::get('webhooks.create') }}
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Sidebar Stats -->
        <div class="space-y-6">
            <div class="glass-card p-6">
                <h3 class="text-xs font-black text-p-muted uppercase tracking-widest mb-4">
                    {{ \App\Core\Lang::get('common.status') }}</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-p-title font-medium">{{ \App\Core\Lang::get('webhooks.active') }}</span>
                        <span class="text-sm font-black text-emerald-400">{{ $stats['active'] }} /
                            {{ $stats['total'] }}</span>
                    </div>
                    <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500"
                            style="width: {{ $stats['total'] > 0 ? ($stats['active'] / $stats['total']) * 100 : 0 }}%">
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span
                            class="text-sm text-p-title font-medium">{{ \App\Core\Lang::get('webhooks.success_rate') }}</span>
                        <span
                            class="text-sm font-black {{ $stats['success_rate'] > 90 ? 'text-emerald-400' : ($stats['success_rate'] > 50 ? 'text-amber-400' : 'text-red-400') }}">{{ $stats['success_rate'] }}%</span>
                    </div>
                    <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full {{ $stats['success_rate'] > 90 ? 'bg-emerald-500' : ($stats['success_rate'] > 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                            style="width: {{ $stats['success_rate'] }}%"></div>
                    </div>
                    <p class="text-[10px] text-p-muted text-right mt-1">Based on last 100 triggers</p>
                </div>
            </div>

            <div class="glass-card p-6 bg-blue-500/5 border-blue-500/10">
                <h3 class="text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Did you know?</h3>
                <p class="text-xs text-blue-200/70 leading-relaxed">
                    You can use services like <strong>postbin.co</strong> or <strong>webhook.site</strong> to test your
                    webhooks before connecting them to real production services.
                </p>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        async function testWebhook(id) {
            if (!confirm('This will send a test payload to the configured URL. Continue?')) return;

            try {
                const formData = new FormData();
                formData.append('id', id);

                const res = await fetch('{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/test', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert('Test payload dispatched successfully!');
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Connectivity Error');
            }
        }
    </script>
@endsection