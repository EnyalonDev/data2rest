@extends('layouts.main')

@section('title', \App\Core\Lang::get('api_control.doc_constructor'))

@section('styles')
    <style type="text/tailwindcss">
        .badge-get {
                                @apply bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded text-[10px] font-black uppercase;
                            }

                            .endpoint-url {
                                @apply bg-black/40 px-4 py-3 rounded-xl text-xs font-mono text-primary border border-white/5 flex items-center justify-between gap-4 overflow-hidden;
                            }

                            .input-dark {
                                @apply bg-black/40 border border-glass-border rounded-lg px-3 py-2 text-xs text-p-title focus:outline-none focus:border-primary/50 transition-all font-medium;
                            }

                            .checkbox-custom {
                                @apply w-4 h-4 rounded border-glass-border bg-black/40 text-primary focus:ring-primary/20 cursor-pointer;
                            }

                            .label-mini {
                                @apply block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 px-1;
                            }
                        </style>
@endsection

@section('content')
    <header class="mb-12">
        <div class="flex items-center gap-4 mb-2">
            <h1 class="text-4xl font-black text-p-title italic tracking-tighter">
                {{ \App\Core\Lang::get('api_control.doc_constructor') }} <span class="text-primary">:
                    {{ $database['name'] }}</span>
            </h1>
            @php
                $dbType = strtolower($database['type'] ?? 'sqlite');
                $badgeClass = match ($dbType) {
                    'mysql' => 'text-orange-500 bg-orange-500/10 border-orange-500/20',
                    'pgsql', 'postgresql' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                    default => 'text-slate-500 bg-slate-500/10 border-slate-500/20'
                };
                $label = match ($dbType) {
                    'mysql' => 'MySQL',
                    'pgsql', 'postgresql' => 'PG',
                    default => 'SQLite'
                };
            @endphp
            <div class="px-3 py-1 rounded-lg border {{ $badgeClass }} flex items-center gap-2">
                <svg class="w-4 h-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                    </path>
                </svg>
                <span class="text-[10px] font-black uppercase tracking-widest">{{ $label }}</span>
            </div>
        </div>
        <p class="text-p-muted font-medium italic">{{ \App\Core\Lang::get('api_control.doc_subtitle') }}</p>
    </header>

    <!-- Global Configuration -->
    <section class="mb-12 glass-card border-primary/20 shadow-lg shadow-primary/5">
        <div class="grid md:grid-cols-3 gap-8 items-end">
            <div class="md:col-span-2">
                <label class="label-mini">{{ \App\Core\Lang::get('api_control.auth_integration') }}</label>
                <div class="flex flex-wrap gap-4 items-center">
                    <select id="api-key-selector" onchange="updateAllUrls()" class="input-dark min-w-[200px] flex-1">
                        <option value="">✨ {{ \App\Core\Lang::get('api_control.internal_session') }}</option>
                        @foreach($apiKeys as $key)
                            <option value="{{ $key['key_value'] }}">
                                {{ $key['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <label
                        class="flex items-center gap-2 cursor-pointer bg-black/20 px-4 py-2 rounded-lg border border-white/5">
                        <input type="checkbox" id="include-key-param" onchange="updateAllUrls()" checked
                            class="checkbox-custom">
                        <span
                            class="text-[10px] font-bold text-p-muted uppercase">{{ \App\Core\Lang::get('api_control.use_url_param') }}</span>
                    </label>
                    <div class="flex-1 md:hidden">
                        <code id="base-api-url-mobile"
                            class="text-[10px] text-primary/70 font-mono italic block truncate">{{ \App\Core\Auth::getFullBaseUrl() }}api/v1/{{ $database['id'] }}/</code>
                    </div>
                </div>
            </div>
            <div class="bg-black/30 p-4 rounded-xl border border-glass-border hidden md:block overflow-hidden">
                <p class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-1">
                    {{ \App\Core\Lang::get('api_control.base_endpoint') }}
                </p>
                <code id="base-api-url"
                    class="text-[10px] text-primary/70 font-mono italic truncate block">{{ \App\Core\Auth::getFullBaseUrl() }}api/v1/{{ $database['id'] }}/</code>
            </div>
        </div>
    </section>

    <!-- Table Selector Tabs -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em]">
                {{ \App\Core\Lang::get('api_control.explore_endpoints') }}
            </h3>
            <div class="relative min-w-[250px]">
                <input type="text" id="table-search" oninput="filterTables(this.value)"
                    placeholder="{{ \App\Core\Lang::get('api_control.search_tables') }}"
                    class="input-dark w-full !pl-10 !rounded-full">
                <svg class="w-4 h-4 text-p-muted absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div id="table-tabs" class="flex flex-wrap gap-4">
            @foreach($tableDetails as $table => $columns)
                <button onclick="switchTable('{{ $table }}')" id="tab-{{ $table }}" data-table-name="{{ $table }}"
                    class="table-tab px-6 py-3 rounded-2xl border border-glass-border bg-white/5 text-xs font-black uppercase tracking-widest text-p-muted hover:text-p-title hover:bg-white/10 transition-all flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-white/20 tab-indicator"></span>
                    {{ $table }}
                </button>
            @endforeach
        </div>
    </div>

    <div id="tables-container" class="space-y-12">
        @foreach($tableDetails as $table => $columns)
            <section id="table-{{ $table }}" class="table-section glass-card group hidden" data-table="{{ $table }}">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-6 mb-8 border-b border-white/5 pb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-xl">📦</div>
                        <div>
                            <h2 class="text-2xl font-black text-p-title uppercase tracking-tight">
                                {{ $table }}
                            </h2>
                            <p class="text-[10px] text-p-muted font-bold uppercase tracking-widest">Active Data Entity</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="badge-get">{{ \App\Core\Lang::get('api_control.get_list') }}</span>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- URL Display Section -->
                    <div class="endpoint-url group/url relative shadow-inner">
                        <span id="url-{{ $table }}" class="truncate pr-16 select-all font-bold"></span>
                        <div class="flex gap-2">
                            <button onclick="copyTableUrl('{{ $table }}')"
                                class="bg-primary/10 hover:bg-primary text-primary hover:text-dark p-2.5 rounded-xl transition-all"
                                title="Copy URL">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </button>
                            </button>
                            <div class="flex items-center gap-2 bg-black/40 rounded-lg p-1 border border-white/5">
                                <select id="method-{{ $table }}" onchange="toggleBodyInput('{{ $table }}')"
                                    class="bg-transparent text-[10px] font-bold text-p-muted uppercase focus:outline-none cursor-pointer">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                            <button onclick="testEndpoint('{{ $table }}')"
                                class="btn-primary !p-2.5 !rounded-xl flex items-center gap-2"
                                title="{{ \App\Core\Lang::get('api_control.test_endpoint') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Request Body Input (Hidden by default) -->
                    <div id="body-input-container-{{ $table }}" class="hidden mb-4 animate-in fade-in slide-in-from-top-2">
                        <label class="text-[9px] font-bold text-p-muted uppercase tracking-widest mb-2 block">Request Payload
                            (JSON)</label>
                        <textarea id="request-body-{{ $table }}" rows="4"
                            class="w-full bg-black/40 border border-glass-border rounded-xl p-4 text-xs font-mono text-p-text focus:outline-none focus:border-primary/50"
                            placeholder='{"field": "value"}'></textarea>
                    </div>

                    <!-- API Playground (Hidden by default) -->
                    <div id="playground-{{ $table }}" class="hidden animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="glass-card !bg-black/40 border-primary/10 overflow-hidden">
                            <div class="flex items-center justify-between p-4 border-b border-white/5">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">{{ \App\Core\Lang::get('api_control.playground_title') }}</span>
                                    <div id="status-{{ $table }}"
                                        class="hidden px-2 py-0.5 rounded text-[9px] font-bold uppercase"></div>
                                    <div id="latency-{{ $table }}"
                                        class="hidden text-[9px] font-bold text-p-muted uppercase tracking-widest"></div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="copyResponse('{{ $table }}')"
                                        class="p-1.5 hover:bg-white/5 rounded-lg text-p-muted transition-all"
                                        title="Copy Response">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </button>
                                    <button onclick="closePlayground('{{ $table }}')"
                                        class="p-1.5 hover:bg-red-500/10 rounded-lg text-p-muted hover:text-red-500 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-0 grid lg:grid-cols-2">
                                <!-- Response Body -->
                                <div class="border-r border-white/5">
                                    <div class="p-3 bg-white/5 flex items-center justify-between">
                                        <span
                                            class="text-[9px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('api_control.response_body') }}</span>
                                    </div>
                                    <pre id="response-{{ $table }}"
                                        class="p-4 text-xs font-mono text-emerald-400/90 overflow-auto max-h-[400px] custom-scrollbar selection:bg-emerald-500/30">{{ \App\Core\Lang::get('api_control.waiting_response') }}</pre>
                                </div>
                                <!-- Headers / Info -->
                                <div>
                                    <div class="p-3 bg-white/5">
                                        <span
                                            class="text-[9px] font-black text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('api_control.headers') }}</span>
                                    </div>
                                    <div id="headers-{{ $table }}"
                                        class="p-4 text-[10px] font-mono text-amber-400/70 overflow-auto max-h-[400px] space-y-1">
                                        <!-- Headers injected here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parameter Tuning Grid -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Pagination & Sorting -->
                        <div class="bg-black/20 rounded-2xl p-6 border border-glass-border">
                            <h4 class="label-mini !mb-4 text-emerald-400">
                                {{ \App\Core\Lang::get('api_control.paging_optimization') }}
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[9px] font-bold text-p-muted block mb-1">LIMIT</label>
                                    <input type="number" data-param="limit" placeholder="50" oninput="updateAllUrls()"
                                        class="input-dark w-full">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-p-muted block mb-1">OFFSET</label>
                                    <input type="number" data-param="offset" placeholder="0" oninput="updateAllUrls()"
                                        class="input-dark w-full">
                                </div>
                            </div>
                        </div>

                        <!-- Filter Construction -->
                        <div class="bg-black/20 rounded-2xl p-6 border border-glass-border">
                            <h4 class="label-mini !mb-4 text-amber-400">
                                {{ \App\Core\Lang::get('api_control.active_filters') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex gap-2">
                                    <select class="filter-col-selector input-dark flex-1">
                                        <option value="">{{ \App\Core\Lang::get('api_control.choose_field') }}</option>
                                        @foreach($columns as $col)
                                            <option value="{{ $col['name'] }}">{{ $col['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" placeholder="{{ \App\Core\Lang::get('api_control.value') }}"
                                        class="filter-val-input input-dark flex-1">
                                    <button onclick="addFilter('{{ $table }}')"
                                        class="bg-white/5 hover:bg-white/10 p-2 rounded-lg text-p-title font-bold text-[10px]">{{ \App\Core\Lang::get('api_control.add') }}</button>
                                </div>
                                <div id="filter-container-{{ $table }}" class="flex flex-wrap gap-2">
                                    <!-- Dynamic filters here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Projection Selector -->
                    <div class="bg-black/20 rounded-2xl p-6 border border-glass-border">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="label-mini !mb-0 text-primary">
                                {{ \App\Core\Lang::get('api_control.data_projection') }}
                            </h4>
                            <div class="flex gap-4">
                                <button onclick="toggleAllFields('{{ $table }}', true)"
                                    class="text-[9px] font-bold text-primary hover:underline uppercase">{{ \App\Core\Lang::get('api_control.select_all') }}</button>
                                <button onclick="toggleAllFields('{{ $table }}', false)"
                                    class="text-[9px] font-bold text-p-muted hover:underline uppercase">{{ \App\Core\Lang::get('media.clear_selection') }}</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($columns as $col)
                                <label
                                    class="flex items-center gap-3 p-3 bg-white/5 border border-transparent hover:border-primary/20 rounded-xl cursor-pointer transition-all group">
                                    <input type="checkbox" data-field="{{ $col['name'] }}" onchange="updateAllUrls()"
                                        class="field-checkbox checkbox-custom" checked>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-bold text-slate-300 group-hover:text-p-title transition-colors">{{ $col['name'] }}</span>
                                        <span class="text-[9px] font-medium text-p-muted uppercase">{{ $col['type'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
@endsection

@section('scripts')
    <script>
        const baseRoot = "{{ \App\Core\Auth::getFullBaseUrl() }}api/v1/{{ $database['id'] }}/";
        const tableFilters = {};

        function switchTable(tableName) {
            // Hide all sections
            document.querySelectorAll('.table-section').forEach(sec => sec.classList.add('hidden'));
            // Remove active style from tabs
            document.querySelectorAll('.table-tab').forEach(tab => {
                tab.classList.remove('bg-primary/20', 'border-primary/40', 'text-p-title');
                tab.querySelector('.tab-indicator').classList.remove('bg-primary', 'animate-pulse');
                tab.querySelector('.tab-indicator').classList.add('bg-white/20');
            });

            // Show selected section
            const target = document.getElementById('table-' + tableName);
            if (target) target.classList.remove('hidden');

            // Apply active style to tab
            const tab = document.getElementById('tab-' + tableName);
            if (tab) {
                tab.classList.add('bg-primary/20', 'border-primary/40', 'text-p-title');
                tab.querySelector('.tab-indicator').classList.remove('bg-white/20');
                tab.querySelector('.tab-indicator').classList.add('bg-primary', 'animate-pulse');
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }

            updateAllUrls();
        }

        function filterTables(query) {
            query = query.toLowerCase();
            document.querySelectorAll('.table-tab').forEach(tab => {
                const name = tab.getAttribute('data-table-name').toLowerCase();
                if (name.includes(query)) {
                    tab.classList.remove('hidden');
                } else {
                    tab.classList.add('hidden');
                }
            });
        }

        function addFilter(tableName) {
            const section = document.querySelector(`section[data-table="${tableName}"]`);
            const col = section.querySelector('.filter-col-selector').value;
            const val = section.querySelector('.filter-val-input').value;

            if (!col || !val) return;

            if (!tableFilters[tableName]) tableFilters[tableName] = [];
            tableFilters[tableName].push({ col, val });

            section.querySelector('.filter-val-input').value = '';
            renderFilters(tableName);
            updateAllUrls();
        }

        function removeFilter(tableName, index) {
            tableFilters[tableName].splice(index, 1);
            renderFilters(tableName);
            updateAllUrls();
        }

        function renderFilters(tableName) {
            const container = document.getElementById('filter-container-' + tableName);
            container.innerHTML = '';

            if (tableFilters[tableName]) {
                tableFilters[tableName].forEach((f, idx) => {
                    const tag = document.createElement('span');
                    tag.className = 'bg-primary/10 text-primary border border-primary/20 px-3 py-1 rounded-full text-[10px] font-bold flex items-center gap-2 animate-in fade-in zoom-in duration-300';
                    tag.innerHTML = `<span>${f.col}: ${f.val}</span><button onclick="removeFilter('${tableName}', ${idx})" class="hover:text-p-title">&times;</button>`;
                    container.appendChild(tag);
                });
            }
        }

        function updateAllUrls() {
            const apiKey = document.getElementById('api-key-selector').value;
            const includeKey = document.getElementById('include-key-param').checked;

            document.querySelectorAll('section[data-table]').forEach(section => {
                const tableName = section.getAttribute('data-table');
                let url = baseRoot + tableName;
                let params = [];

                // 1. Pagination Params
                const limitInput = section.querySelector('[data-param="limit"]');
                const offsetInput = section.querySelector('[data-param="offset"]');
                const limit = limitInput ? limitInput.value : '';
                const offset = offsetInput ? offsetInput.value : '';

                if (limit) params.push('limit=' + limit);
                if (offset) params.push('offset=' + offset);

                // 2. Custom Filters
                if (tableFilters[tableName]) {
                    tableFilters[tableName].forEach(f => {
                        params.push(`${f.col}=${encodeURIComponent(f.val)}`);
                    });
                }

                // 3. Fields Selection
                const checkboxes = section.querySelectorAll('.field-checkbox:checked');
                const allCheckboxes = section.querySelectorAll('.field-checkbox');
                if (checkboxes.length > 0 && checkboxes.length < allCheckboxes.length) {
                    const fields = Array.from(checkboxes).map(cb => cb.getAttribute('data-field')).join(',');
                    params.push('fields=' + fields);
                }

                // 4. API Key
                if (apiKey && includeKey) {
                    params.push('api_key=' + apiKey);
                }

                if (params.length > 0) {
                    url += '?' + params.join('&');
                }

                document.getElementById('url-' + tableName).innerText = url;
            });
        }

        async function testEndpoint(tableName) {
            const url = document.getElementById('url-' + tableName).innerText;
            const method = document.getElementById('method-' + tableName).value;
            const playground = document.getElementById('playground-' + tableName);
            const responseContainer = document.getElementById('response-' + tableName);
            const headersContainer = document.getElementById('headers-' + tableName);
            const statusBadge = document.getElementById('status-' + tableName);
            const latencyBadge = document.getElementById('latency-' + tableName);

            // Get Request Body if applicable
            let body = null;
            if (['POST', 'PUT', 'PATCH'].includes(method)) {
                const rawBody = document.getElementById('request-body-' + tableName).value;
                try {
                    if (rawBody) {
                        // Validate JSON
                        JSON.parse(rawBody);
                        body = rawBody;
                    }
                } catch (e) {
                    alert('Invalid JSON in Request Payload');
                    return;
                }
            }

            playground.classList.remove('hidden');
            responseContainer.innerText = '{{ \App\Core\Lang::get('api_control.waiting_response') }}';
            responseContainer.className = 'p-4 text-xs font-mono text-p-muted animate-pulse';
            headersContainer.innerHTML = '';
            statusBadge.classList.add('hidden');
            latencyBadge.classList.add('hidden');

            const startTime = performance.now();

            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };

                if (body) {
                    options.body = body;
                }

                const response = await fetch(url, options);
                const endTime = performance.now();
                const latency = Math.round(endTime - startTime);

                // Status UI
                statusBadge.innerText = `HTTP ${response.status} ${response.statusText}`;
                statusBadge.className = `px-2 py-0.5 rounded text-[9px] font-bold uppercase ${response.ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}`;
                statusBadge.classList.remove('hidden');

                // Latency UI
                latencyBadge.innerText = `${latency}ms`;
                latencyBadge.classList.remove('hidden');

                // Headers UI
                let headersHtml = '';
                response.headers.forEach((v, k) => {
                    headersHtml += `<div><span class="text-p-muted capitalize font-bold">${k}:</span> ${v}</div>`;
                });
                headersContainer.innerHTML = headersHtml;

                // Body UI
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    responseContainer.innerText = JSON.stringify(data, null, 4);
                    responseContainer.className = 'p-4 text-xs font-mono text-emerald-400/90 overflow-auto max-h-[400px] custom-scrollbar selection:bg-emerald-500/30';
                } catch (e) {
                    responseContainer.innerText = text; // Fallback for non-JSON response
                    responseContainer.className = 'p-4 text-xs font-mono text-p-text overflow-auto max-h-[400px]';
                }

            } catch (e) {
                const endTime = performance.now();
                responseContainer.innerText = `Error: ${e.message}\n\nTIP: Check CORS settings and valid URL.`;
                responseContainer.className = 'p-4 text-xs font-mono text-red-400';
            }
        }

        function toggleBodyInput(tableName) {
            const method = document.getElementById('method-' + tableName).value;
            const container = document.getElementById('body-input-container-' + tableName);
            if (['POST', 'PUT', 'PATCH'].includes(method)) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function closePlayground(tableName) {
            document.getElementById('playground-' + tableName).classList.add('hidden');
        }

        function copyResponse(tableName) {
            const text = document.getElementById('response-' + tableName).innerText;
            navigator.clipboard.writeText(text);
        }

        function toggleAllFields(tableName, status) {
            const section = document.querySelector(`section[data-table="${tableName}"]`);
            section.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = status);
            updateAllUrls();
        }

        function copyTableUrl(tableName) {
            const text = document.getElementById('url-' + tableName).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.currentTarget;
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                setTimeout(() => btn.innerHTML = originalContent, 1000);
            });
        }

        window.onload = () => {
            updateAllUrls();
            // Select first table if any
            const firstTab = document.querySelector('.table-tab');
            if (firstTab) {
                const name = firstTab.getAttribute('data-table-name');
                switchTable(name);
            }
        };
    </script>
@endsection