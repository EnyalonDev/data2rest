<?php use App\Core\Auth; ?>
<script src="https://cdn.tiny.cloud/1/4twb1yrntltl1gohwrjplvnczgb9nlnr12ywqhns44icc3ur/tinymce/7/tinymce.min.js"
    referrerpolicy="origin"></script>

<style type="text/tailwindcss">
    .btn-gallery {
        @apply bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-bold py-2 px-4 rounded-lg hover:bg-emerald-500 hover:text-white transition-all text-[10px] flex items-center gap-2;
    }

    .modal-bg {
        @apply fixed inset-0 bg-black/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-6;
    }

    .modal-content {
        @apply bg-dark border border-glass-border rounded-3xl w-full max-w-6xl p-8 max-h-[85vh] overflow-hidden flex flex-col;
    }

    .sidebar-item {
        @apply w-full text-left px-4 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all mb-2 border border-transparent;
    }

    .sidebar-item.active {
        @apply bg-primary/10 text-primary border-primary/20;
    }

    .sidebar-item:not(.active) {
        @apply text-slate-500 hover:text-slate-200 hover:bg-white/5;
    }
</style>

<header class="text-center mb-16">
    <h1 class="text-5xl font-black text-white italic tracking-tighter mb-4">
        <?php echo $record ? 'Refine' : 'Initialize'; ?> <span class="text-primary">Record</span>
    </h1>
    <p class="text-slate-500 font-medium">Injecting data into <b><?php echo ucfirst($ctx['table']); ?></b> collection.
    </p>
</header>

<section class="glass-card relative overflow-hidden max-w-6xl mx-auto">
    <form action="<?php echo $baseUrl; ?>admin/crud/save" method="POST" enctype="multipart/form-data" id="crud-form"
        class="space-y-10 relative">
        <input type="hidden" name="db_id" value="<?php echo $ctx['db_id']; ?>">
        <input type="hidden" name="table" value="<?php echo $ctx['table']; ?>">
        <?php if ($record): ?>
            <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
        <?php endif; ?>

        <div class="space-y-12">
            <?php foreach ($ctx['fields'] as $field): ?>
                <?php if ($field['field_name'] === 'id' || !$field['is_editable'])
                    continue; ?>

                <div class="group">
                    <label class="form-label group-focus-within:text-primary transition-colors">
                        <?php echo ucfirst(str_replace('_', ' ', $field['field_name'])); ?>
                        <?php if ($field['is_required'] ?? false): ?><span class="text-red-500 ml-1">*</span><?php endif; ?>
                    </label>

                    <?php
                    $val = $record[$field['field_name']] ?? '';

                    if ($field['is_foreign_key']): ?>
                        <select name="<?php echo $field['field_name']; ?>" class="form-input" <?php echo ($field['is_required'] ?? false) ? 'required' : ''; ?>>
                            <option value="">-- Select from <?php echo $field['related_table']; ?> --</option>
                            <?php foreach ($foreignOptions[$field['field_name']] as $opt): ?>
                                <option value="<?php echo $opt['id']; ?>" <?php echo $val == $opt['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($opt['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else:
                        switch ($field['view_type']):
                            case 'textarea': ?>
                                <textarea name="<?php echo $field['field_name']; ?>" rows="6" class="form-input" <?php echo ($field['is_required'] ?? false) ? 'required' : ''; ?>><?php echo htmlspecialchars($val); ?></textarea>
                                <?php break;

                            case 'wysiwyg': ?>
                                <div class="rounded-xl overflow-hidden shadow-inner"><textarea
                                        name="<?php echo $field['field_name']; ?>"
                                        class="editor"><?php echo htmlspecialchars($val); ?></textarea></div>
                                <?php break;

                            case 'image':
                            case 'gallery':
                            case 'file': ?>
                                <div
                                    class="bg-black/40 p-8 rounded-2xl border-2 border-dashed border-glass-border hover:border-primary/40 transition-all flex flex-col gap-6">

                                    <div id="preview-container-<?php echo $field['field_name']; ?>"
                                        class="<?php echo empty($val) ? 'hidden' : ''; ?> flex items-center gap-6 p-4 bg-white/5 rounded-xl border border-glass-border">
                                        <?php if ($field['view_type'] === 'image' || $field['view_type'] === 'gallery'): ?>
                                            <img id="preview-img-<?php echo $field['field_name']; ?>" src="<?php echo $val; ?>"
                                                class="max-h-32 rounded border border-primary/20">
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-black text-primary uppercase mb-1">Enlace Activo</p>
                                            <p id="preview-path-<?php echo $field['field_name']; ?>"
                                                class="text-xs text-slate-400 font-mono break-all"><?php echo $val; ?></p>
                                        </div>
                                        <button type="button" onclick="clearField('<?php echo $field['field_name']; ?>')"
                                            class="text-red-400 hover:text-red-200 transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="flex flex-col gap-3">
                                            <label
                                                class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                <span class="w-1 h-1 rounded-full bg-primary"></span> URL del Recurso (Interna o
                                                Externa)
                                            </label>
                                            <div class="flex gap-4">
                                                <input type="text" name="gallery_<?php echo $field['field_name']; ?>"
                                                    id="gallery-<?php echo $field['field_name']; ?>"
                                                    value="<?php echo htmlspecialchars($val); ?>"
                                                    placeholder="https://ejemplo.com/imagen.jpg o selecciona de la galería"
                                                    class="form-input flex-1 !bg-white/5"
                                                    oninput="updatePreviewFromUrl('<?php echo $field['field_name']; ?>', this.value)">

                                                <button type="button" onclick="openMediaGallery('<?php echo $field['field_name']; ?>')"
                                                    class="btn-gallery whitespace-nowrap !py-2">
                                                    <span>📁</span> Galería
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 pt-6 border-t border-white/5">
                                            <label
                                                class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                <span class="w-1 h-1 rounded-full bg-primary/40"></span> O Subir Nuevo Archivo
                                            </label>
                                            <input type="file" name="<?php echo $field['field_name']; ?>"
                                                id="file-<?php echo $field['field_name']; ?>" <?php echo (($field['is_required'] ?? false) && empty($val)) ? 'required' : ''; ?>
                                                class="text-xs w-full file:bg-primary/20 file:border file:border-primary/30 file:px-4 file:py-2 file:rounded-lg file:text-primary file:font-bold file:mr-4 file:cursor-pointer hover:file:bg-primary/30 transition-all"
                                                onchange="if(this.value) { document.getElementById('gallery-<?php echo $field['field_name']; ?>').required = false; }">
                                        </div>
                                    </div>
                                </div>
                                <?php break;

                            case 'boolean': ?>
                                <label class="flex items-center gap-4 cursor-pointer">
                                    <input type="checkbox" name="<?php echo $field['field_name']; ?>" value="1" <?php echo $val ? 'checked' : ''; ?> class="w-6 h-6 rounded bg-black/40 text-primary">
                                    <span class="text-sm font-bold uppercase">Toggle Status</span>
                                </label>
                                <?php break;

                            default: ?>
                                <input type="text" name="<?php echo $field['field_name']; ?>"
                                    value="<?php echo htmlspecialchars($val); ?>" class="form-input" <?php echo ($field['is_required'] ?? false) ? 'required' : ''; ?>>
                                <?php break;
                        endswitch;
                    endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-12 border-t border-glass-border flex justify-end gap-6">
            <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
                class="btn-primary !bg-slate-800 !text-slate-300">ABORT</a>
            <button type="submit" class="btn-primary">Commit Operation</button>
        </div>
    </form>
</section>

<!-- Media Modal -->
<div id="mediaModal" class="modal-bg">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-6">
            <div>
                <h2 class="text-3xl font-black text-white italic tracking-tighter">Media Explorer</h2>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] mt-1">Neural Asset Selection
                    System</p>
            </div>
            <button onclick="closeMediaGallery()"
                class="text-slate-500 hover:text-white transition-colors bg-white/5 p-2 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <div class="flex-1 flex gap-8 overflow-hidden">
            <!-- Sidebar Filters -->
            <aside class="w-64 flex flex-col gap-6 overflow-y-auto pr-4 custom-scrollbar border-r border-white/5">
                <div>
                    <h4
                        class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span> Temporal Nodes
                    </h4>
                    <div id="filter-dates" class="space-y-1">
                        <!-- Dates inject here -->
                    </div>
                </div>

                <div>
                    <h4
                        class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Entity Collections
                    </h4>
                    <div id="filter-tables" class="space-y-1">
                        <!-- Tables inject here -->
                    </div>
                </div>
            </aside>

            <!-- Grid Area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <div id="mediaGrid"
                    class="flex-1 overflow-y-auto grid grid-cols-2 md:grid-cols-4 gap-4 pr-2 custom-scrollbar content-start pb-10">
                    <!-- Media items will be injected here -->
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-glass-border flex justify-between items-center">
            <span id="gallery-status" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Scanning
                Uplink...</span>
            <button onclick="closeMediaGallery()" class="btn-outline">Abort Selection</button>
        </div>
    </div>
</div>

<script>
    let currentTargetField = null;
    let allMediaData = null;
    let activeDateFilter = 'all';
    let activeTableFilter = 'all';

    tinymce.init({
        selector: '.editor', language: 'es', skin: 'oxide-dark', content_css: 'dark', height: 400,
        plugins: 'lists link image code table wordcount',
        toolbar: 'undo redo | bold italic forecolor | alignleft aligncenter alignright | bullist numlist | removeformat',
        branding: false, promotion: false
    });

    function openMediaGallery(fieldName) {
        currentTargetField = fieldName;
        const modal = document.getElementById('mediaModal');
        modal.style.display = 'flex';

        fetch('<?php echo $baseUrl; ?>admin/media/list')
            .then(res => res.json())
            .then(data => {
                allMediaData = data;
                renderFilters();
                renderGrid();
            });
    }

    function renderFilters() {
        const dateContainer = document.getElementById('filter-dates');
        const tableContainer = document.getElementById('filter-tables');

        dateContainer.innerHTML = `<button onclick="setFilter('date', 'all')" class="sidebar-item ${activeDateFilter === 'all' ? 'active' : ''}">All Timelines</button>`;
        allMediaData.available_dates.sort().reverse().forEach(date => {
            const btn = document.createElement('button');
            btn.className = `sidebar-item ${activeDateFilter === date ? 'active' : ''}`;
            btn.innerText = date;
            btn.onclick = () => setFilter('date', date);
            dateContainer.appendChild(btn);
        });

        tableContainer.innerHTML = `<button onclick="setFilter('table', 'all')" class="sidebar-item ${activeTableFilter === 'all' ? 'active' : ''}">All Entities</button>`;
        allMediaData.available_tables.sort().forEach(table => {
            const btn = document.createElement('button');
            btn.className = `sidebar-item ${activeTableFilter === table ? 'active' : ''}`;
            btn.innerText = table;
            btn.onclick = () => setFilter('table', table);
            tableContainer.appendChild(btn);
        });
    }

    function setFilter(type, value) {
        if (type === 'date') activeDateFilter = value;
        if (type === 'table') activeTableFilter = value;
        renderFilters();
        renderGrid();
    }

    function renderGrid() {
        const grid = document.getElementById('mediaGrid');
        const status = document.getElementById('gallery-status');
        grid.innerHTML = '';

        let filtered = allMediaData.files;
        if (activeDateFilter !== 'all') filtered = filtered.filter(f => f.date_folder === activeDateFilter);
        if (activeTableFilter !== 'all') filtered = filtered.filter(f => f.table_folder === activeTableFilter);

        status.innerText = `${filtered.length} Assets syncronized`;

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-20 text-center text-slate-500 uppercase font-black tracking-widest opacity-30">Null sector: No assets in this query path</div>';
            return;
        }

        filtered.forEach(item => {
            const div = document.createElement('div');
            div.className = 'group relative aspect-square bg-black/40 rounded-2xl overflow-hidden cursor-pointer border border-glass-border hover:border-primary/50 transition-all shadow-xl';
            div.onclick = () => selectMedia(item.url);

            div.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                    <p class="text-[9px] font-black text-primary truncate uppercase tracking-widest mb-1">${item.name}</p>
                    <p class="text-[7px] text-slate-400 font-bold uppercase">${item.date_folder} / ${item.table_folder}</p>
                </div>
            `;
            grid.appendChild(div);
        });
    }

    function closeMediaGallery() { document.getElementById('mediaModal').style.display = 'none'; }

    function updatePreviewFromUrl(fieldName, url) {
        const container = document.getElementById('preview-container-' + fieldName);
        const img = document.getElementById('preview-img-' + fieldName);
        const pathTxt = document.getElementById('preview-path-' + fieldName);

        if (!url || url === '__EMPTY__') {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        if (img) img.src = url;
        pathTxt.innerText = url;

        const fileInput = document.getElementById('file-' + fieldName);
        if (fileInput) fileInput.required = false;
    }

    function selectMedia(url) {
        const input = document.getElementById('gallery-' + currentTargetField);
        input.value = url;
        updatePreviewFromUrl(currentTargetField, url);
        closeMediaGallery();
    }

    function clearField(fieldName) {
        document.getElementById('preview-container-' + fieldName).classList.add('hidden');
        document.getElementById('gallery-' + fieldName).value = '__EMPTY__';
        document.getElementById('file-' + fieldName).value = '';
    }
</script>