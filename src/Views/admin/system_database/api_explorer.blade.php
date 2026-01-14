@extends('layouts.main')

@section('title', 'System API Explorer')

@section('styles')
    <style type="text/tailwindcss">
        .input-dark {
                @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-2 text-p-title focus:outline-none focus:border-primary/50 transition-all font-medium;
            }
        </style>
@endsection

@section('content')
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter mb-2">
            🔌 System API Explorer
        </h1>
        <p class="text-p-muted font-medium">Test and explore System Database API endpoints visually</p>
    </header>

    @if(!$isSuperAdmin)
        <div class="glass-card mb-8 bg-red-500/5 border-red-500/20">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-p-title mb-1">Super Admin Required</h3>
                    <p class="text-sm text-p-muted">Only Super Admin API Keys can access System Database endpoints.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left: API Endpoint Selector -->
        <section class="lg:col-span-1 glass-card">
            <h2 class="text-xl font-black text-p-title uppercase tracking-tight mb-6">
                📡 Endpoints
            </h2>

            <div class="space-y-2">
                <!-- GET /api/system/info -->
                <button onclick="selectEndpoint('GET', '/api/system/info', {})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-emerald-500 uppercase">GET</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/info</code>
                    <p class="text-[10px] text-p-muted mt-1">Get system database information</p>
                </button>

                <!-- POST /api/system/backup -->
                <button onclick="selectEndpoint('POST', '/api/system/backup', {})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-blue-500 uppercase">POST</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/backup</code>
                    <p class="text-[10px] text-p-muted mt-1">Create a new system backup</p>
                </button>

                <!-- GET /api/system/backups -->
                <button onclick="selectEndpoint('GET', '/api/system/backups', {})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-emerald-500 uppercase">GET</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/backups</code>
                    <p class="text-[10px] text-p-muted mt-1">List all system backups</p>
                </button>

                <!-- POST /api/system/optimize -->
                <button onclick="selectEndpoint('POST', '/api/system/optimize', {})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-blue-500 uppercase">POST</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/optimize</code>
                    <p class="text-[10px] text-p-muted mt-1">Optimize system database</p>
                </button>

                <!-- POST /api/system/query -->
                <button onclick="selectEndpoint('POST', '/api/system/query', {query: 'SELECT * FROM users LIMIT 10'})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-blue-500 uppercase">POST</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/query</code>
                    <p class="text-[10px] text-p-muted mt-1">Execute SELECT query</p>
                </button>

                <!-- GET /api/system/tables -->
                <button onclick="selectEndpoint('GET', '/api/system/tables', {})"
                    class="endpoint-btn w-full text-left p-4 rounded-xl border border-glass-border hover:border-primary/50 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-black text-emerald-500 uppercase">GET</span>
                        <svg class="w-4 h-4 text-p-muted group-hover:text-primary transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <code class="text-xs text-p-title font-mono">/api/system/tables</code>
                    <p class="text-[10px] text-p-muted mt-1">List all system tables</p>
                </button>
            </div>
        </section>

        <!-- Right: Request & Response -->
        <section class="lg:col-span-2 space-y-6">
            <!-- API Key Selection -->
            <div class="glass-card">
                <h3 class="text-sm font-black text-p-muted uppercase tracking-widest mb-4">🔑 API Key</h3>
                @if(empty($apiKeys))
                    <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4">
                        <p class="text-sm text-p-muted">
                            No API keys found. <a href="<?= $baseUrl ?>admin/api" class="text-primary hover:underline">Create
                                one</a> to test the API.
                        </p>
                    </div>
                @else
                    <select id="apiKeySelect" class="input-dark w-full">
                        @foreach($apiKeys as $key)
                            <option value="{{ $key['key_value'] }}">{{ $key['name'] }} ({{ substr($key['key_value'], 0, 20) }}...)
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Request Builder -->
            <div class="glass-card">
                <h3 class="text-sm font-black text-p-muted uppercase tracking-widest mb-4">📤 Request</h3>

                <div class="space-y-4">
                    <!-- Method & Endpoint -->
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Method</label>
                            <input type="text" id="requestMethod" readonly class="input-dark w-full" value="GET">
                        </div>
                        <div class="col-span-3">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Endpoint</label>
                            <input type="text" id="requestEndpoint" readonly class="input-dark w-full"
                                value="/api/system/info">
                        </div>
                    </div>

                    <!-- Request Body (for POST) -->
                    <div id="requestBodySection" style="display: none;">
                        <label class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Request Body
                            (JSON)</label>
                        <textarea id="requestBody" rows="6" class="input-dark w-full font-mono text-xs"
                            placeholder='{"query": "SELECT * FROM users LIMIT 10"}'></textarea>
                    </div>

                    <!-- Execute Button -->
                    <button onclick="executeRequest()"
                        class="btn-primary w-full !py-4 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span class="font-black uppercase tracking-wider">Execute Request</span>
                    </button>
                </div>
            </div>

            <!-- Response -->
            <div class="glass-card">
                <h3 class="text-sm font-black text-p-muted uppercase tracking-widest mb-4">📥 Response</h3>

                <div id="responseContainer" class="bg-black/40 rounded-xl p-4 border border-white/5 min-h-[200px]">
                    <p class="text-p-muted text-sm text-center py-8">Execute a request to see the response</p>
                </div>

                <div id="responseStats" class="mt-4 grid grid-cols-2 gap-4" style="display: none;">
                    <div class="bg-black/40 rounded-xl p-3 border border-white/5">
                        <span class="text-[10px] font-black text-p-muted uppercase tracking-widest block mb-1">Status</span>
                        <span id="responseStatus" class="text-lg font-black text-emerald-500">200 OK</span>
                    </div>
                    <div class="bg-black/40 rounded-xl p-3 border border-white/5">
                        <span class="text-[10px] font-black text-p-muted uppercase tracking-widest block mb-1">Time</span>
                        <span id="responseTime" class="text-lg font-black text-primary">0ms</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        let currentMethod = 'GET';
        let currentEndpoint = '/api/system/info';
        let currentBody = {};

        function selectEndpoint(method, endpoint, body) {
            currentMethod = method;
            currentEndpoint = endpoint;
            currentBody = body;

            document.getElementById('requestMethod').value = method;
            document.getElementById('requestEndpoint').value = endpoint;

            // Show/hide body section based on method
            const bodySection = document.getElementById('requestBodySection');
            if (method === 'POST') {
                bodySection.style.display = 'block';
                document.getElementById('requestBody').value = JSON.stringify(body, null, 2);
            } else {
                bodySection.style.display = 'none';
            }

            // Highlight selected endpoint
            document.querySelectorAll('.endpoint-btn').forEach(btn => {
                btn.classList.remove('border-primary', 'bg-primary/5');
                btn.classList.add('border-glass-border');
            });
            event.target.closest('.endpoint-btn').classList.add('border-primary', 'bg-primary/5');
            event.target.closest('.endpoint-btn').classList.remove('border-glass-border');
        }

        async function executeRequest() {
            const apiKey = document.getElementById('apiKeySelect')?.value;
            if (!apiKey) {
                alert('Please select an API key');
                return;
            }

            const responseContainer = document.getElementById('responseContainer');
            const responseStats = document.getElementById('responseStats');
            const responseStatus = document.getElementById('responseStatus');
            const responseTime = document.getElementById('responseTime');

            // Show loading
            responseContainer.innerHTML = '<p class="text-p-muted text-sm text-center py-8">⏳ Loading...</p>';
            responseStats.style.display = 'none';

            const startTime = Date.now();

            try {
                const options = {
                    method: currentMethod,
                    headers: {
                        'X-API-KEY': apiKey,
                        'Content-Type': 'application/json'
                    }
                };

                if (currentMethod === 'POST') {
                    const bodyText = document.getElementById('requestBody').value;
                    try {
                        options.body = bodyText;
                    } catch (e) {
                        alert('Invalid JSON in request body');
                        return;
                    }
                }

                const response = await fetch('<?= $baseUrl ?>' + currentEndpoint.substring(1), options);
                const endTime = Date.now();
                const data = await response.json();

                // Show response
                responseContainer.innerHTML = `<pre class="text-xs text-primary font-mono overflow-auto">${JSON.stringify(data, null, 2)}</pre>`;

                // Show stats
                responseStats.style.display = 'grid';
                responseStatus.textContent = response.status + ' ' + response.statusText;
                responseStatus.className = response.ok ? 'text-lg font-black text-emerald-500' : 'text-lg font-black text-red-500';
                responseTime.textContent = (endTime - startTime) + 'ms';

            } catch (error) {
                responseContainer.innerHTML = `<pre class="text-xs text-red-500 font-mono">${error.message}</pre>`;
                responseStats.style.display = 'none';
            }
        }
    </script>
@endsection