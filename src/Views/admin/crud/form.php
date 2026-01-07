<?php use App\Core\Auth;
use App\Core\Lang; ?>
<header class="mb-12 text-center">
    <h1 class="text-4xl font-black text-white italic tracking-tighter mb-2">
        <?php echo $id ? Lang::get('crud.edit') : Lang::get('crud.new'); ?>
        <span class="text-primary italic"><?php echo ucfirst($ctx['table']); ?></span>
    </h1>
    <p class="text-slate-500 font-medium">Configurando registro estructural en la base de datos
        <b><?php echo htmlspecialchars($ctx['database']['name']); ?></b>.
    </p>
</header>

<section class="max-w-4xl mx-auto">
    <form action="<?php echo $baseUrl; ?>admin/crud/save" method="POST" id="crud-form" enctype="multipart/form-data">
        <input type="hidden" name="db_id" value="<?php echo $ctx['db_id']; ?>">
        <input type="hidden" name="table" value="<?php echo $ctx['table']; ?>">
        <input type="hidden" name="id" value="<?php echo $record['id'] ?? ''; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($ctx['fields'] as $field):
                if (!$field['is_editable'])
                    continue;
                $val = $record[$field['field_name']] ?? '';
                $isFullWidth = in_array($field['view_type'], ['wysiwyg', 'textarea', 'gallery', 'image']);
                ?>
                <div class="<?php echo $isFullWidth ? 'md:col-span-2' : ''; ?> space-y-3">
                    <label class="form-label flex items-center gap-2">
                        <?php echo htmlspecialchars($field['field_name']); ?>
                        <?php if ($field['is_required']): ?>
                            <span
                                class="text-primary text-[10px] font-black uppercase tracking-tighter">[<?php echo Lang::get('fields.required'); ?>]</span>
                        <?php endif; ?>
                    </label>

                    <?php switch ($field['view_type']):
                        case 'text': ?>
                            <input type="text" name="<?php echo $field['field_name']; ?>"
                                value="<?php echo htmlspecialchars($val); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>
                                class="form-input" data-type="<?php echo $field['data_type']; ?>">
                            <?php break;

                        case 'textarea': ?>
                            <textarea name="<?php echo $field['field_name']; ?>" rows="4" <?php echo $field['is_required'] ? 'required' : ''; ?> class="form-input"><?php echo htmlspecialchars($val); ?></textarea>
                            <?php break;

                        case 'wysiwyg': ?>
                            <textarea name="<?php echo $field['field_name']; ?>" class="editor"><?php echo $val; ?></textarea>
                            <?php break;

                        case 'image':
                        case 'gallery': ?>
                            <div class="glass-card !bg-black/20 overflow-hidden">
                                <div class="flex flex-col lg:flex-row gap-8">
                                    <div id="preview-container-<?php echo $field['field_name']; ?>"
                                        class="<?php echo empty($val) ? 'hidden' : ''; ?> w-full lg:w-48 aspect-square rounded-xl overflow-hidden border border-glass-border relative group">
                                        <img id="preview-img-<?php echo $field['field_name']; ?>"
                                            src="<?php echo (strpos($val, 'http') === 0) ? $val : $baseUrl . $val; ?>"
                                            class="w-full h-full object-cover">
                                        <div
                                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-4">
                                            <p id="preview-path-<?php echo $field['field_name']; ?>"
                                                class="text-[8px] font-mono text-primary break-all text-center"><?php echo $val; ?>
                                            </p>
                                        </div>
                                        <button type="button" onclick="clearField('<?php echo $field['field_name']; ?>')"
                                            class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="flex-1 space-y-6">
                                        <div class="flex flex-col gap-3">
                                            <label
                                                class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                <span class="w-1 h-1 rounded-full bg-primary"></span>
                                                <?php echo Lang::get('crud.resource_url'); ?>
                                            </label>
                                            <div class="flex gap-4">
                                                <input type="text" name="gallery_<?php echo $field['field_name']; ?>"
                                                    id="gallery-<?php echo $field['field_name']; ?>"
                                                    value="<?php echo htmlspecialchars($val); ?>"
                                                    placeholder="<?php echo Lang::get('crud.url_placeholder'); ?>"
                                                    class="form-input flex-1 !bg-white/5"
                                                    oninput="updatePreviewFromUrl('<?php echo $field['field_name']; ?>', this.value)">

                                                <button type="button"
                                                    onclick="openMediaGallery('<?php echo $field['field_name']; ?>')"
                                                    class="btn-gallery whitespace-nowrap !py-2">
                                                    <span>📁</span> <?php echo Lang::get('crud.gallery_btn'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 pt-6 border-t border-white/5">
                                            <label
                                                class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                <span class="w-1 h-1 rounded-full bg-primary/40"></span>
                                                <?php echo Lang::get('crud.upload_new'); ?>
                                            </label>
                                            <input type="file" name="<?php echo $field['field_name']; ?>"
                                                id="file-<?php echo $field['field_name']; ?>" <?php echo (($field['is_required'] ?? false) && empty($val)) ? 'required' : ''; ?>
                                                class="text-xs w-full file:bg-primary/20 file:border file:border-primary/30 file:px-4 file:py-2 file:rounded-lg file:text-primary file:font-bold file:mr-4 file:cursor-pointer hover:file:bg-primary/30 transition-all"
                                                onchange="if(this.value) { document.getElementById('gallery-<?php echo $field['field_name']; ?>').required = false; }">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php break;

                        case 'boolean': ?>
                            <label class="flex items-center gap-4 cursor-pointer">
                                <input type="checkbox" name="<?php echo $field['field_name']; ?>" value="1" <?php echo $val ? 'checked' : ''; ?> class="w-6 h-6 rounded bg-black/40 text-primary">
                                <span class="text-sm font-bold uppercase"><?php echo Lang::get('crud.toggle_status'); ?></span>
                            </label>
                            <?php break;

                    endswitch; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-12 border-t border-glass-border flex justify-end gap-6">
            <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
                class="btn-primary !bg-slate-800 !text-slate-300"><?php echo Lang::get('common.abort'); ?></a>
            <button type="submit" class="btn-primary"><?php echo Lang::get('common.commit'); ?></button>
        </div>
    </form>
</section>

<!-- Media Modal -->
<div id="mediaModal" class="modal-bg">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-6">
            <div>
                <h2 class="text-3xl font-black text-white italic tracking-tighter">
                    <?php echo Lang::get('media.explorer'); ?></h2>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] mt-1">
                    <?php echo Lang::get('media.system'); ?></p>
            </div>
            <div class="flex items-center gap-6">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="media-search" placeholder="<?php echo Lang::get('media.search'); ?>"
                        oninput="handleSearch(this.value)" class="search-input pl-10">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">🔍</span>
                </div>
                <button onclick="closeMediaGallery()"
                    class="text-slate-500 hover:text-white transition-colors bg-white/5 p-2 rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-1 flex gap-8 overflow-hidden">
            <!-- Sidebar Filters -->
            <aside class="w-64 flex flex-col gap-6 overflow-y-auto pr-4 custom-scrollbar border-r border-white/5">
                <!-- Drop to Upload Button/Zone -->
                <div class="mb-4">
                    <label
                        class="w-full flex flex-col items-center justify-center py-6 px-4 bg-primary/5 border border-dashed border-primary/30 rounded-2xl cursor-pointer hover:bg-primary/10 hover:border-primary/50 transition-all group">
                        <span class="text-2xl mb-2 group-hover:scale-110 transition-transform">🚀</span>
                        <span
                            class="text-[9px] font-black text-primary uppercase tracking-widest text-center"><?php echo Lang::get('media.upload'); ?></span>
                        <input type="file" class="hidden" onchange="handleDirectUpload(this.files[0])"
                            accept="image/*,video/*,application/pdf">
                    </label>
                </div>
                <div>
                    <h4
                        class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <?php echo Lang::get('media.temporal_nodes'); ?>
                    </h4>
                    <div id="filter-dates" class="space-y-1">
                        <!-- Dates inject here -->
                    </div>
                </div>

                <div>
                    <h4
                        class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <?php echo Lang::get('media.entity_collections'); ?>
                    </h4>
                    <div id="filter-tables" class="space-y-1">
                        <!-- Tables inject here -->
                    </div>
                </div>
            </aside>

            <!-- Grid Area -->
            <div class="flex-1 flex flex-col overflow-hidden relative">
                <div id="mediaGrid"
                    class="flex-1 overflow-y-auto grid grid-cols-2 md:grid-cols-4 gap-4 pr-2 custom-scrollbar content-start pb-10 transition-all"
                    ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                    <!-- Media items will be injected here -->
                </div>

                <!-- Drop Overlay -->
                <div id="drop-overlay"
                    class="absolute inset-0 bg-primary/10 backdrop-blur-sm border-4 border-dashed border-primary rounded-2xl flex items-center justify-center z-50 pointer-events-none opacity-0 transition-opacity">
                    <div class="text-center">
                        <div class="text-6xl mb-4 animate-bounce">⚡</div>
                        <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">
                            <?php echo Lang::get('media.drop'); ?>
                        </h3>
                        <p class="text-[10px] text-primary font-black uppercase tracking-widest mt-2">Uploading to
                            Neural Network</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-glass-border flex justify-between items-center">
            <span id="gallery-status"
                class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"><?php echo Lang::get('media.scanning'); ?></span>
            <button onclick="closeMediaGallery()"
                class="btn-outline"><?php echo Lang::get('media.abort_selection'); ?></button>
        </div>
    </div>
</div>

<script>
    let currentTargetField = null;
    let allMediaData = null;
    let activeDateFilter = 'all';
    let activeTableFilter = 'all';
    let searchQuery = '';

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

        const dates = [...new Set(allMediaData.files.map(f => f.date_folder))];
        const tables = [...new Set(allMediaData.files.map(f => f.table_folder))];

        dateContainer.innerHTML = `<button onclick="setDateFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === 'all' ? 'bg-primary/20 text-primary' : 'text-slate-500 hover:bg-white/5'}"><?php echo Lang::get('media.all'); ?></button>`;
        dates.forEach(d => {
            dateContainer.innerHTML += `<button onclick="setDateFilter('${d}')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === d ? 'bg-primary/20 text-primary' : 'text-slate-500 hover:bg-white/5'}">${d}</button>`;
        });

        tableContainer.innerHTML = `<button onclick="setTableFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeTableFilter === 'all' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-500 hover:bg-white/5'}"><?php echo Lang::get('media.all'); ?></button>`;
        tables.forEach(t => {
            tableContainer.innerHTML += `<button onclick="setTableFilter('${t}')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeTableFilter === t ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-500 hover:bg-white/5'}">${t}</button>`;
        });
    }

    function setDateFilter(d) { activeDateFilter = d; renderFilters(); renderGrid(); }
    function setTableFilter(t) { activeTableFilter = t; renderFilters(); renderGrid(); }
    function handleSearch(q) { searchQuery = q.toLowerCase(); renderGrid(); }

    function handleDragOver(e) { e.preventDefault(); document.getElementById('drop-overlay').style.opacity = '1'; }
    function handleDragLeave(e) { e.preventDefault(); document.getElementById('drop-overlay').style.opacity = '0'; }
    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('drop-overlay').style.opacity = '0';
        const file = e.dataTransfer.files[0];
        if (file) handleDirectUpload(file);
    }

    function handleDirectUpload(file) {
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        const status = document.getElementById('gallery-status');
        status.innerText = 'Uploading: ' + file.name + '...';
        status.className = 'text-[10px] font-bold text-primary animate-pulse uppercase tracking-widest';

        fetch('<?php echo $baseUrl; ?>admin/media/upload', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.url) {
                    // Refresh gallery and select the new file
                    fetch('<?php echo $baseUrl; ?>admin/media/list')
                        .then(res => res.json())
                        .then(galleryData => {
                            allMediaData = galleryData;
                            renderFilters();
                            renderGrid();
                            // Optional: auto-select the new one
                            selectMedia(data.url);
                        });
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown Error'));
                }
            })
            .catch(err => {
                alert('Upload failed: ' + err.message);
            })
            .finally(() => {
                status.className = 'text-[10px] font-bold text-slate-500 uppercase tracking-widest';
            });
    }

    function renderGrid() {
        const grid = document.getElementById('mediaGrid');
        const status = document.getElementById('gallery-status');
        grid.innerHTML = '';

        let filtered = allMediaData.files;
        if (activeDateFilter !== 'all') filtered = filtered.filter(f => f.date_folder === activeDateFilter);
        if (activeTableFilter !== 'all') filtered = filtered.filter(f => f.table_folder === activeTableFilter);
        if (searchQuery) filtered = filtered.filter(f => f.name.toLowerCase().includes(searchQuery));

        status.innerText = `<?php echo Lang::get('media.sync'); ?>`.replace(':count', filtered.length);

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-20 text-center text-slate-500 uppercase font-black tracking-widest opacity-30"><?php echo Lang::get('media.null'); ?></div>';
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

    // Smart Validation Engine
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('crud-form');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            input.addEventListener('input', () => validateInput(input));
            input.addEventListener('blur', () => validateInput(input, true));
        });

        function validateInput(input, isBlur = false) {
            const container = input.closest('div');
            const label = container ? container.querySelector('.form-label') : null;
            let isValid = true;
            let errorMsg = '';

            // 1. Check Required
            if (input.hasAttribute('required') && !input.value.trim()) {
                isValid = false;
            }

            // 2. Numeric Type Check (SQLite Types)
            const type = input.getAttribute('data-type');
            if (type === 'INTEGER' || type === 'REAL' || type === 'NUMERIC') {
                if (input.value && isNaN(input.value)) {
                    isValid = false;
                    errorMsg = 'Numeric signal required';
                    // Prevent typing non-numeric characters
                    input.value = input.value.replace(/[^0-9.-]/g, '');
                }
            }

            // Apply Styles
            if (!isValid) {
                input.classList.add('form-input-error');
                input.classList.remove('form-input-valid');
                if (label) label.classList.add('text-red-500');
                if (isBlur) input.classList.add('animate-shake');
            } else {
                input.classList.remove('form-input-error', 'animate-shake');
                if (label) label.classList.remove('text-red-500');
                if (input.value.trim()) {
                    input.classList.add('form-input-valid');
                } else {
                    input.classList.remove('form-input-valid');
                }
            }
        }
    });
</script>