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

<header class="mb-12">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">Endpoint <span
            class="text-primary">Constructor</span></h1>
    <p class="text-p-muted font-medium italic">Configure your search parameters, pagination and projection in
        real-time.</p>
</header>

<!-- Global Configuration -->
<section class="mb-12 glass-card border-primary/20 shadow-lg shadow-primary/5">
    <div class="grid md:grid-cols-3 gap-8 items-end">
        <div class="md:col-span-2">
            <label class="label-mini">Auth Integration (Security Context)</label>
            <div class="flex flex-wrap gap-4 items-center">
                <select id="api-key-selector" onchange="updateAllUrls()" class="input-dark min-w-[200px] flex-1">
                    <option value="">-- No API Key --</option>
                    <?php foreach ($apiKeys as $key): ?>
                        <option value="<?php echo htmlspecialchars($key['key_value']); ?>">
                            <?php echo htmlspecialchars($key['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label
                    class="flex items-center gap-2 cursor-pointer bg-black/20 px-4 py-2 rounded-lg border border-white/5">
                    <input type="checkbox" id="include-key-param" onchange="updateAllUrls()" checked
                        class="checkbox-custom">
                    <span class="text-[10px] font-bold text-p-muted uppercase">Use URL Parameter</span>
                </label>
                <div class="flex-1 md:hidden">
                    <code id="base-api-url-mobile"
                        class="text-[10px] text-primary/70 font-mono italic block truncate"><?php echo \App\Core\Auth::getFullBaseUrl(); ?>api/v1/<?php echo $database['id']; ?>/</code>
                </div>
            </div>
        </div>
        <div class="bg-black/30 p-4 rounded-xl border border-glass-border hidden md:block overflow-hidden">
            <p class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-1">Base Endpoint Path</p>
            <code id="base-api-url"
                class="text-[10px] text-primary/70 font-mono italic truncate block"><?php echo \App\Core\Auth::getFullBaseUrl(); ?>api/v1/<?php echo $database['id']; ?>/</code>
        </div>
    </div>
</section>

<div class="space-y-12">
    <?php foreach ($tableDetails as $table => $columns): ?>
        <section class="glass-card group" data-table="<?php echo htmlspecialchars($table); ?>">
            <div class="flex flex-col md:flex-row justify-between md:items-center gap-6 mb-8 border-b border-white/5 pb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-xl">📦</div>
                    <div>
                        <h2 class="text-2xl font-black text-p-title uppercase tracking-tight">
                            <?php echo htmlspecialchars($table); ?>
                        </h2>
                        <p class="text-[10px] text-p-muted font-bold uppercase tracking-widest">Active Data Entity</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="badge-get">GET LIST</span>
                </div>
            </div>

            <div class="space-y-8">
                <!-- URL Display Section -->
                <div class="endpoint-url group/url relative shadow-inner">
                    <span id="url-<?php echo $table; ?>" class="truncate pr-16 select-all font-bold"></span>
                    <div class="flex gap-2">
                        <button onclick="copyTableUrl('<?php echo $table; ?>')"
                            class="bg-primary/10 hover:bg-primary text-primary hover:text-dark p-2.5 rounded-xl transition-all"
                            title="Copy URL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                </path>
                            </svg>
                        </button>
                        <a id="test-<?php echo $table; ?>" href="#" target="_blank"
                            class="bg-white/5 hover:bg-white/10 p-2.5 rounded-xl text-p-muted hover:text-p-title transition-all"
                            title="Launch Request">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Parameter Tuning Grid -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Pagination & Sorting -->
                    <div class="bg-black/20 rounded-2xl p-6 border border-glass-border">
                        <h4 class="label-mini !mb-4 text-emerald-400">Paging & Optimization</h4>
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
                        <h4 class="label-mini !mb-4 text-amber-400">Active Search Filters</h4>
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <select class="filter-col-selector input-dark flex-1">
                                    <option value="">-- Choose Field --</option>
                                    <?php foreach ($columns as $col): ?>
                                        <option value="<?php echo $col['name']; ?>"><?php echo $col['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" placeholder="Value..." class="filter-val-input input-dark flex-1">
                                <button onclick="addFilter('<?php echo $table; ?>')"
                                    class="bg-white/5 hover:bg-white/10 p-2 rounded-lg text-p-title font-bold text-[10px]">Add</button>
                            </div>
                            <div id="filter-container-<?php echo $table; ?>" class="flex flex-wrap gap-2">
                                <!-- Dynamic filters here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projection Selector -->
                <div class="bg-black/20 rounded-2xl p-6 border border-glass-border">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="label-mini !mb-0 text-primary">Data Projection (Selective Fields)</h4>
                        <div class="flex gap-4">
                            <button onclick="toggleAllFields('<?php echo $table; ?>', true)"
                                class="text-[9px] font-bold text-primary hover:underline uppercase">Select All</button>
                            <button onclick="toggleAllFields('<?php echo $table; ?>', false)"
                                class="text-[9px] font-bold text-p-muted hover:underline uppercase">Clear</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php foreach ($columns as $col): ?>
                            <label
                                class="flex items-center gap-3 p-3 bg-white/5 border border-transparent hover:border-primary/20 rounded-xl cursor-pointer transition-all group">
                                <input type="checkbox" data-field="<?php echo $col['name']; ?>" onchange="updateAllUrls()"
                                    class="field-checkbox checkbox-custom" checked>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-bold text-slate-300 group-hover:text-p-title transition-colors"><?php echo $col['name']; ?></span>
                                    <span
                                        class="text-[9px] font-medium text-p-muted uppercase"><?php echo $col['type']; ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<script>
    const baseRoot = "<?php echo \App\Core\Auth::getFullBaseUrl(); ?>api/v1/<?php echo $database['id']; ?>/";
    const tableFilters = {};

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
            const limit = section.querySelector('[data-param="limit"]').value;
            const offset = section.querySelector('[data-param="offset"]').value;
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
            document.getElementById('test-' + tableName).href = url;
        });
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

    window.onload = updateAllUrls;
</script>