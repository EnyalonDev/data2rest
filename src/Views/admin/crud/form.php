<?php use App\Core\Auth;
use App\Core\Lang; ?>
<header class="mb-12 text-center">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
        <?php echo $id ? Lang::get('crud.edit') : Lang::get('crud.new'); ?>
        <span class="text-primary italic"><?php echo ucfirst($ctx['table']); ?></span>
    </h1>
    <p class="text-p-muted font-medium">Configurando registro estructural en la base de datos
        <b><?php echo htmlspecialchars($ctx['database']['name']); ?></b>.
    </p>
</header>

<section class="form-container">
    <form action="<?php echo $baseUrl; ?>admin/crud/save" method="POST" id="crud-form" enctype="multipart/form-data" class="w-full">
        <input type="hidden" name="db_id" value="<?php echo $ctx['db_id']; ?>">
        <input type="hidden" name="table" value="<?php echo $ctx['table']; ?>">
        <input type="hidden" name="id" value="<?php echo $record['id'] ?? ''; ?>">
        <div class="glass-card w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                <?php foreach ($ctx['fields'] as $field):
                if (!$field['is_editable'])
                    continue;
                $val = $record[$field['field_name']] ?? '';
                $isFullWidth = in_array($field['view_type'], ['wysiwyg', 'textarea', 'gallery', 'image']);
                ?>
                <div class="<?php echo $isFullWidth ? 'md:col-span-2' : ''; ?> space-y-4">
                    <label class="form-label flex items-center gap-2">
                        <?php echo htmlspecialchars($field['field_name']); ?>
                        <?php if ($field['is_required']): ?>
                            <span
                                class="text-primary text-[10px] font-black uppercase tracking-tighter">[<?php echo Lang::get('fields.required'); ?>]</span>
                        <?php endif; ?>
                    </label>

                    <?php 
                    if (!empty($field['is_foreign_key']) && isset($foreignOptions[$field['field_name']])): ?>
                        <div class="relative">
                            <select name="<?php echo $field['field_name']; ?>" class="custom-select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value=""><?php echo Lang::get('common.none'); ?></option>
                                <?php foreach ($foreignOptions[$field['field_name']] as $opt): ?>
                                    <option value="<?php echo $opt['id']; ?>" <?php echo ($val == $opt['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label'] ?? $opt['id']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else:
                        switch ($field['view_type']):
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

                        case 'image': ?>
                            <div class="glass-card !bg-white/5 overflow-hidden">
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
                                            class="absolute top-2 right-2 bg-red-500 text-p-title p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="flex-1 space-y-6">
                                        <div class="flex flex-col gap-3">
                                            <label
                                                class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
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
                                                    class="btn-gallery whitespace-nowrap !py-2 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <?php echo Lang::get('crud.gallery_btn'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 pt-6 border-t border-white/5">
                                            <label
                                                class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
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

                        case 'gallery': ?>
                            <div class="glass-card !bg-white/5 p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <label class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1 h-1 rounded-full bg-primary"></span>
                                        <?php echo Lang::get('fields.types.gallery'); ?>
                                    </label>
                                    <button type="button" onclick="openMediaGallery('<?php echo $field['field_name']; ?>', true)"
                                        class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all">
                                        + <?php echo Lang::get('crud.gallery_btn'); ?>
                                    </button>
                                </div>
                                
                                <div id="gallery-previews-<?php echo $field['field_name']; ?>" 
                                    class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 min-h-[100px] p-4 rounded-xl bg-black/20 border border-white/5">
                                    <?php 
                                    $images = !empty($val) ? explode(',', $val) : [];
                                    foreach ($images as $img): if(empty(trim($img))) continue; ?>
                                        <div class="relative aspect-square rounded-lg overflow-hidden border border-white/10 group">
                                            <img src="<?php echo (strpos($img, 'http') === 0) ? $img : $baseUrl . $img; ?>" class="w-full h-full object-cover">
                                            <button type="button" onclick="removeGalleryImage('<?php echo $field['field_name']; ?>', '<?php echo $img; ?>')"
                                                class="absolute top-1 right-1 bg-red-500/80 text-white p-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path></svg>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if(empty($images)): ?>
                                        <div class="col-span-full flex flex-col items-center justify-center py-6 opacity-20">
                                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="1.5"></path></svg>
                                            <span class="text-[9px] font-black uppercase tracking-widest">No Selection</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="gallery_<?php echo $field['field_name']; ?>" id="gallery-<?php echo $field['field_name']; ?>" value="<?php echo htmlspecialchars($val); ?>">
                            </div>
                            <?php break;

                        case 'boolean': ?>
                            <div class="flex items-center h-full pt-8">
                                <label class="flex items-center gap-4 cursor-pointer group/toggle">
                                    <div class="relative">
                                        <input type="checkbox" name="<?php echo $field['field_name']; ?>" value="1" <?php echo $val ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-14 h-7 bg-white/5 border border-glass-border rounded-full peer peer-checked:bg-primary/20 peer-checked:border-primary/50 transition-all duration-300"></div>
                                        <div class="absolute left-1 top-1 w-5 h-5 bg-slate-500 rounded-full transition-all duration-300 peer-checked:left-8 peer-checked:bg-primary peer-checked:shadow-[0_0_10px_rgba(56,189,248,0.5)]"></div>
                                    </div>
                                    <span class="text-xs font-black uppercase tracking-widest text-p-muted group-hover/toggle:text-primary transition-colors"><?php echo Lang::get('crud.toggle_status'); ?></span>
                                </label>
                            </div>
                            <?php break;

                        case 'datetime': 
                            // Format Y-m-d H:i:s to Y-m-d\TH:i for datetime-local input
                            $formattedDate = '';
                            if (!empty($val)) {
                                $date = new DateTime($val);
                                $formattedDate = $date->format('Y-m-d\TH:i');
                            }
                            ?>
                            <input type="datetime-local" name="<?php echo $field['field_name']; ?>"
                                value="<?php echo $formattedDate; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>
                                class="form-input">
                            <?php break;

                    endswitch; 
                    endif; ?>
                </div>
            <?php endforeach; ?>
            </div>

            <div class="pt-12 border-t border-glass-border flex justify-end gap-6">
                <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
                    class="btn-primary !bg-slate-800 !text-slate-300"><?php echo Lang::get('common.abort'); ?></a>
                <button type="submit" class="btn-primary"><?php echo Lang::get('common.commit'); ?></button>
            </div>
        </div>
    </form>
</section>

<!-- Media Modal -->
<div id="mediaModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 sm:p-8 bg-black/90 backdrop-blur-xl transition-all">
    <div class="glass-card w-full h-[90vh] flex flex-col shadow-2xl ring-1 ring-white/10 relative">
        <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-6">
            <div>
                <h2 class="text-3xl font-black text-p-title italic tracking-tighter">
                    <?php echo Lang::get('media.explorer'); ?>
                </h2>
                <p class="text-[10px] text-p-muted font-bold uppercase tracking-[0.3em] mt-1">
                    <?php echo Lang::get('media.system'); ?>
                </p>
            </div>
            <div class="flex items-center gap-6">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="media-search" placeholder="<?php echo Lang::get('media.search'); ?>"
                        oninput="handleSearch(this.value)" class="search-input pl-10">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-p-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                </div>
                <button onclick="closeMediaGallery()"
                    class="text-p-muted hover:text-p-title transition-colors bg-white/5 p-2 rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12">
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
                        <svg class="w-8 h-8 mb-2 text-primary group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
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
                        <svg class="w-16 h-16 mx-auto mb-4 text-p-title animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <h3 class="text-2xl font-black text-p-title italic uppercase tracking-tighter">
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
                class="text-[10px] font-bold text-p-muted uppercase tracking-widest"><?php echo Lang::get('media.scanning'); ?></span>
            <div class="flex gap-4">
                <button onclick="closeMediaGallery()"
                    class="btn-outline"><?php echo Lang::get('media.abort_selection'); ?></button>
                <button id="gallery-done-btn" onclick="closeMediaGallery()"
                    class="btn-primary !py-2 hidden"><?php echo Lang::get('common.commit'); ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    let currentTargetField = null;
    let isMultiMode = false;
    let allMediaData = null;
    let activeDateFilter = 'all';
    let activeTableFilter = 'all';
    let searchQuery = '';

    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '.editor', language: 'es', skin: 'oxide-dark', content_css: 'dark', height: 400,
            plugins: 'lists link image code table wordcount',
            toolbar: 'undo redo | bold italic forecolor | alignleft aligncenter alignright | bullist numlist | removeformat',
            branding: false, promotion: false,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                    if (typeof validateInput === 'function') {
                        validateInput(document.querySelector('textarea.editor'));
                    }
                });
            }
        });
    }

    function openMediaGallery(fieldName, isMulti = false) {
        currentTargetField = fieldName;
        isMultiMode = isMulti;
        const modal = document.getElementById('mediaModal');
        const doneBtn = document.getElementById('gallery-done-btn');
        
        modal.style.display = 'flex';
        doneBtn.style.display = isMulti ? 'block' : 'none';

        fetch('<?php echo $baseUrl; ?>admin/media/list')
            .then(res => res.json())
            .then(data => {
                allMediaData = data;
                renderFilters();
                renderGrid();
            });
    }

    function renderGrid() {
        if(!allMediaData) return;
        const grid = document.getElementById('mediaGrid');
        const status = document.getElementById('gallery-status');
        grid.innerHTML = '';

        let filtered = allMediaData.files;
        if (activeDateFilter !== 'all') filtered = filtered.filter(f => f.date_folder === activeDateFilter);
        if (activeTableFilter !== 'all') filtered = filtered.filter(f => f.table_folder === activeTableFilter);
        if (searchQuery) filtered = filtered.filter(f => f.name.toLowerCase().includes(searchQuery));

        status.innerText = `<?php echo Lang::get('media.sync'); ?>`.replace(':count', filtered.length);

        const currentVal = document.getElementById('gallery-' + currentTargetField)?.value || '';
        const selectedImages = currentVal.split(',').filter(x => x.trim());

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-20 text-center text-p-muted uppercase font-black tracking-widest opacity-30"><?php echo Lang::get('media.null'); ?></div>';
            return;
        }

        filtered.forEach(item => {
            const isSelected = selectedImages.includes(item.url);
            const div = document.createElement('div');
            div.className = `group relative aspect-square bg-black/40 rounded-2xl overflow-hidden cursor-pointer border ${isSelected ? 'border-primary ring-2 ring-primary/20' : 'border-glass-border'} hover:border-primary/50 transition-all shadow-xl`;
            div.onclick = () => selectMedia(item.url);

            div.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ${isSelected ? 'opacity-50' : ''}">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                    <p class="text-[9px] font-black text-primary truncate uppercase tracking-widest mb-1">${item.name}</p>
                    <p class="text-[7px] text-p-muted font-bold uppercase">${item.date_folder} / ${item.table_folder}</p>
                </div>
                ${isSelected ? `
                    <div class="absolute top-2 right-2 bg-primary text-black p-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"></path></svg>
                    </div>
                ` : ''}
            `;
            grid.appendChild(div);
        });
    }

    function selectMedia(url) {
        const input = document.getElementById('gallery-' + currentTargetField);
        
        if (isMultiMode) {
            let currentImages = input.value.split(',').filter(x => x.trim());
            if (currentImages.includes(url)) {
                currentImages = currentImages.filter(x => x !== url);
            } else {
                currentImages.push(url);
            }
            input.value = currentImages.join(',');
            refreshGalleryPreviews(currentTargetField);
            renderGrid();
        } else {
            input.value = url;
            updatePreviewFromUrl(currentTargetField, url);
            closeMediaGallery();
        }
    }

    function refreshGalleryPreviews(fieldName) {
        const container = document.getElementById('gallery-previews-' + fieldName);
        const input = document.getElementById('gallery-' + fieldName);
        const images = input.value.split(',').filter(x => x.trim());
        
        let html = '';
        images.forEach(img => {
            const fullUrl = (img.startsWith('http') ? img : '<?php echo $baseUrl; ?>' + img);
            html += `
                <div class="relative aspect-square rounded-lg overflow-hidden border border-white/10 group">
                    <img src="${fullUrl}" class="w-full h-full object-cover">
                    <button type="button" onclick="removeGalleryImage('${fieldName}', '${img}')"
                        class="absolute top-1 right-1 bg-red-500/80 text-white p-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path></svg>
                    </button>
                </div>
            `;
        });

        if (images.length === 0) {
            html = `
                <div class="col-span-full flex flex-col items-center justify-center py-6 opacity-20">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="1.5"></path></svg>
                    <span class="text-[9px] font-black uppercase tracking-widest">No Selection</span>
                </div>
            `;
        }

        container.innerHTML = html;
        if (typeof validateInput === 'function') validateInput(input);
    }

    function removeGalleryImage(fieldName, url) {
        const input = document.getElementById('gallery-' + fieldName);
        let currentImages = input.value.split(',').filter(x => x.trim());
        currentImages = currentImages.filter(x => x !== url);
        input.value = currentImages.join(',');
        refreshGalleryPreviews(fieldName);
    }

    function renderFilters() {
        if(!allMediaData) return;
        const dateContainer = document.getElementById('filter-dates');
        const tableContainer = document.getElementById('filter-tables');

        const dates = [...new Set(allMediaData.files.map(f => f.date_folder))];
        const tables = [...new Set(allMediaData.files.map(f => f.table_folder))];

        dateContainer.innerHTML = `<button onclick="setDateFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === 'all' ? 'bg-primary/20 text-primary' : 'text-p-muted hover:bg-white/5'}"><?php echo Lang::get('media.all'); ?></button>`;
        dates.forEach(d => {
            dateContainer.innerHTML += `<button onclick="setDateFilter('${d}')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === d ? 'bg-primary/20 text-primary' : 'text-p-muted hover:bg-white/5'}">${d}</button>`;
        });

        tableContainer.innerHTML = `<button onclick="setTableFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeTableFilter === 'all' ? 'bg-emerald-500/20 text-emerald-400' : 'text-p-muted hover:bg-white/5'}"><?php echo Lang::get('media.all'); ?></button>`;
        tables.forEach(t => {
            tableContainer.innerHTML += `<button onclick="setTableFilter('${t}')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeTableFilter === t ? 'bg-emerald-500/20 text-emerald-400' : 'text-p-muted hover:bg-white/5'}">${t}</button>`;
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
                    fetch('<?php echo $baseUrl; ?>admin/media/list')
                        .then(res => res.json())
                        .then(galleryData => {
                            allMediaData = galleryData;
                            renderFilters();
                            renderGrid();
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
                status.className = 'text-[10px] font-bold text-p-muted uppercase tracking-widest';
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
        if (img) img.src = (url.startsWith('http') ? url : '<?php echo $baseUrl; ?>' + url);
        pathTxt.innerText = url;

        const fileInput = document.getElementById('file-' + fieldName);
        if (fileInput) fileInput.required = false;
    }

    function clearField(fieldName) {
        document.getElementById('preview-container-' + fieldName).classList.add('hidden');
        document.getElementById('gallery-' + fieldName).value = '__EMPTY__';
        document.getElementById('file-' + fieldName).value = '';
    }

    function validateInput(input, isBlur = false) {
        if(!input) return;
        const container = input.closest('div');
        const label = container ? container.querySelector('.form-label') : null;
        let isValid = true;
        let errorMsg = '';

        if (input.hasAttribute('required') && !input.value.trim()) {
            isValid = false;
        }

        const type = input.getAttribute('data-type');
        if (type === 'INTEGER' || type === 'REAL' || type === 'NUMERIC') {
            if (input.value && isNaN(input.value)) {
                isValid = false;
                input.value = input.value.replace(/[^0-9.-]/g, '');
            }
        }

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

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('crud-form');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if(input.classList.contains('editor')) return;
            input.addEventListener('input', () => validateInput(input));
            input.addEventListener('blur', () => validateInput(input, true));
        });
    });
</script>