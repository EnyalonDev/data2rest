<div id="command-palette"
    class="fixed inset-0 z-[1100] hidden items-start justify-center p-6 sm:p-24 bg-black/60 backdrop-blur-sm transition-all duration-300">
    <div id="palette-content"
        class="glass-card max-w-2xl w-full !p-0 overflow-hidden scale-95 opacity-0 transition-all duration-300 shadow-2xl border border-white/10">
        <div class="relative group">
            <svg class="w-6 h-6 absolute left-6 top-6 text-p-muted group-focus-within:text-primary transition-colors pointer-events-none"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" id="palette-search" placeholder="{{ \App\Core\Lang::get('common.search_placeholder') }}"
                class="w-full bg-transparent p-6 pl-16 text-lg font-medium text-p-title outline-none placeholder:text-p-muted border-b border-white/5"
                autocomplete="off">
            <div id="palette-loader" class="absolute right-6 top-6 hidden">
                <div class="w-6 h-6 border-2 border-primary/20 border-t-primary rounded-full animate-spin"></div>
            </div>
        </div>

        <div id="palette-results" class="max-h-[60vh] overflow-y-auto p-4 custom-scrollbar">
            <div class="p-8 text-center text-p-muted opacity-50 flex flex-col items-center gap-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-black uppercase tracking-widest italic">
                    {{ \App\Core\Lang::get('command_palette.placeholder') }}</p>
                <div class="flex gap-2 text-[10px]">
                    <span class="px-2 py-1 bg-white/5 rounded-lg border border-white/10 uppercase tracking-widest">Esc
                        {{ \App\Core\Lang::get('command_palette.close') }}</span>
                </div>
            </div>
        </div>

        <div
            class="p-4 bg-white/[0.02] border-t border-white/5 flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-p-muted">
            <div class="flex gap-4">
                <span>{{ \App\Core\Lang::get('command_palette.navigate') }}</span>
                <span>{{ \App\Core\Lang::get('command_palette.go') }}</span>
            </div>
            <div class="text-primary italic">Command Palette</div>
        </div>
    </div>
</div>

<script>
    let searchDebounce = null;
    const palette = document.getElementById('command-palette');
    const content = document.getElementById('palette-content');
    const input = document.getElementById('palette-search');
    const results = document.getElementById('palette-results');
    const loader = document.getElementById('palette-loader');

    function openPalette() {
        palette.classList.remove('hidden');
        palette.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            input.focus();
        }, 10);
    }

    function closePalette() {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            palette.classList.remove('flex');
            palette.classList.add('hidden');
            input.value = '';
            renderPlaceholder();
        }, 300);
    }

    function renderPlaceholder() {
        results.innerHTML = `
            <div class="p-8 text-center text-p-muted opacity-50 flex flex-col items-center gap-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-black uppercase tracking-widest italic">${'{!! addslashes(\App\Core\Lang::get('command_palette.placeholder')) !!}'}</p>
                <div class="flex gap-2 text-[10px]">
                    <span class="px-2 py-1 bg-white/5 rounded-lg border border-white/10 uppercase tracking-widest">${'{!! addslashes(\App\Core\Lang::get('command_palette.close')) !!}'}</span>
                </div>
            </div>`;
    }

    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            palette.classList.contains('hidden') ? openPalette() : closePalette();
        }
        if (e.key === 'Escape' && !palette.classList.contains('hidden')) {
            closePalette();
        }
    });

    palette.addEventListener('click', (e) => {
        if (e.target === palette) closePalette();
    });

    input.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        if (searchDebounce) clearTimeout(searchDebounce);

        if (q.length < 2) {
            renderPlaceholder();
            return;
        }

        searchDebounce = setTimeout(() => {
            performSearch(q);
        }, 300);
    });

    async function performSearch(q) {
        loader.classList.remove('hidden');
        try {
            const formData = new FormData();
            formData.append('q', q);
            formData.append('_token', '{{ $csrf_token }}');

            const res = await fetch('{{ $baseUrl }}admin/system/global-search', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ $csrf_token }}'
                }
            });
            const data = await res.json();
            renderResults(data.results, q);
        } catch (err) {
            results.innerHTML = `<div class="p-8 text-center text-red-400 font-bold uppercase tracking-widest text-xs">${'{!! addslashes(\App\Core\Lang::get('command_palette.error')) !!}'}</div>`;
        } finally {
            loader.classList.add('hidden');
        }
    }

    function renderResults(list, q) {
        if (!list || list.length === 0) {
            const noResultsText = "{!! addslashes(\App\Core\Lang::get('command_palette.no_results')) !!}".replace(':q', q);
            results.innerHTML = `<div class="p-12 text-center text-p-muted italic opacity-50 uppercase tracking-[0.2em] text-xs font-black">${noResultsText}</div>`;
            return;
        }

        let html = '<div class="flex flex-col gap-1">';
        list.forEach(res => {
            const url = `{{ $baseUrl }}admin/crud/edit?db_id=${res.db_id}&table=${res.table}&id=${res.id}`;
            html += `
                <a href="${url}" class="group flex items-center justify-between p-4 rounded-xl hover:bg-white/5 border border-transparent hover:border-white/5 transition-all">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary group-hover:rotate-12 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-black p-title uppercase italic truncate">${res.display}</span>
                                <span class="text-[9px] bg-white/5 px-2 py-0.5 rounded border border-white/10 text-p-muted font-bold tracking-tighter uppercase">${res.table}</span>
                            </div>
                            <p class="text-[11px] text-p-muted truncate opacity-80">${res.snippet}</p>
                        </div>
                    </div>
                    <div class="text-[9px] font-black text-p-muted uppercase tracking-widest hidden sm:block">
                        DB: ${res.db_name}
                    </div>
                </a>
            `;
        });
        html += '</div>';
        results.innerHTML = html;
    }
</script>