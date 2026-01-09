<?php use App\Core\Lang; ?>
<div class="flex flex-col gap-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-p-title tracking-tight"><?php echo Lang::get('media.explorer'); ?></h1>
            <p class="text-p-muted mt-1"><?php echo Lang::get('media.library_subtitle'); ?></p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="showSettings()"
                class="p-3 bg-p-input hover:bg-primary/10 hover:text-primary rounded-xl transition-all border border-glass-border"
                title="<?php echo Lang::get('media.settings'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3" />
                    <path
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
            </button>
            <button onclick="document.getElementById('file-upload').click()" class="btn-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <?php echo Lang::get('media.upload_file'); ?>
            </button>
            <button id="btn-view-trash" onclick="loadFiles('.trash')"
                class="p-3 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-all border border-red-500/20"
                title="<?php echo Lang::get('media.view_trash'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    <line x1="10" y1="11" x2="10" y2="17" />
                    <line x1="14" y1="11" x2="14" y2="17" />
                </svg>
            </button>
            <input type="file" id="file-upload" class="hidden" multiple onchange="handleUpload(this.files)">
        </div>
    </div>

    <!-- Bar 1: Actions Toolbar -->
    <div class="glass-card !p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 flex-grow">
            <!-- Search -->
            <div class="relative w-full md:w-80">
                <input type="text" id="media-search" placeholder="<?php echo Lang::get('media.search_placeholder'); ?>"
                    class="form-input !py-2 !pl-10 text-sm" oninput="debounceFilterFiles()">
                <svg class="absolute left-3 top-2.5 text-p-muted" xmlns="http://www.w3.org/2000/svg" width="18"
                    height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>

            <div class="h-8 w-[1px] bg-glass-border hidden md:block"></div>

            <!-- Selection Actions -->
            <div id="selection-actions" class="hidden flex items-center gap-2">
                <span id="selection-count"
                    class="text-[10px] font-black text-primary uppercase tracking-widest bg-primary/10 px-3 py-1 rounded-full">0
                    <?php echo Lang::get('media.selected'); ?></span>

                <div class="flex items-center gap-1 ml-2">
                    <button onclick="bulkDelete()"
                        class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                        title="<?php echo Lang::get('media.delete_selected'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18" />
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                    </button>
                    <button onclick="bulkMove()"
                        class="p-2 text-p-muted hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                        title="<?php echo Lang::get('media.move_selected'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 14l11-11" />
                            <path d="M21 3v5" />
                            <path d="M16 3h5" />
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        </svg>
                    </button>
                    <button onclick="deselectAll()"
                        class="ml-2 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                        title="<?php echo Lang::get('media.deselect_all'); ?>">
                        <?php echo Lang::get('media.clear_selection'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- View Modes -->
            <div class="flex items-center bg-p-input rounded-xl border border-glass-border p-1">
                <button onclick="setViewMode('grid')" id="btn-grid-view"
                    class="p-2 rounded-lg transition-all text-primary bg-primary/10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                </button>
                <button onclick="setViewMode('list')" id="btn-list-view"
                    class="p-2 rounded-lg transition-all text-p-muted hover:text-p-text">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Bar 2: Path Breadcrumbs (Low Height) -->
    <div class="bg-black/10 border border-glass-border rounded-xl px-4 py-1.5 flex items-center min-h-[40px]">
        <div id="media-breadcrumbs"
            class="flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide py-1 text-xs">
            <!-- Breadcrumbs injected here -->
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex gap-8 relative min-h-[600px]">

        <!-- Dropzone Overlay -->
        <div id="dropzone"
            class="absolute inset-0 bg-primary/10 border-4 border-dashed border-primary rounded-3xl z-40 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
            <div class="text-center">
                <div class="w-20 h-20 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-primary" xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-primary"><?php echo Lang::get('media.drop_to_upload'); ?></h3>
                <p class="text-primary/70"><?php echo Lang::get('media.upload_current_folder'); ?></p>
            </div>
        </div>

        <!-- Files Container (Fixed Width Area) -->
        <div class="flex-grow">
            <div id="media-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-6">
                <!-- Items injected here -->
                <div class="col-span-full py-20 flex flex-col items-center justify-center opacity-40">
                    <svg class="animate-spin h-10 w-10 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <p><?php echo Lang::get('media.scanning'); ?></p>
                </div>
            </div>
        </div>

        <!-- Sidebar / Inspector Panel (Overlay Drawer) -->
        <div id="media-sidebar" class="hidden absolute top-0 right-0 h-full w-[400px] z-[50] pointer-events-none">
            <div
                class="glass-card !p-0 sticky top-0 h-[calc(100vh-250px)] overflow-hidden border-2 border-primary/20 shadow-[0_0_50px_rgba(0,0,0,0.5)] pointer-events-auto flex flex-col scale-in relative">
                <!-- Close Button -->
                <button onclick="deselectAll()"
                    class="absolute top-4 right-4 p-2 bg-black/20 hover:bg-red-500/20 text-p-muted hover:text-red-500 rounded-full transition-all z-20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
                <div id="sidebar-preview"
                    class="w-full h-80 bg-black/5 flex items-center justify-center relative group shrink-0">
                    <!-- Preview injected here -->
                </div>
                <div class="p-8 flex-grow overflow-y-auto custom-scrollbar">
                    <div class="flex items-start justify-between mb-6">
                        <div class="overflow-hidden">
                            <h3 id="sidebar-name" class="text-xl font-black text-p-title truncate leading-tight">
                                filename.jpg</h3>
                            <p id="sidebar-meta" class="text-sm text-p-muted mt-1 uppercase font-black tracking-widest">
                                JPG • 1.2 MB</p>
                        </div>
                        <div class="flex gap-1" id="sidebar-actions-container">
                            <!-- Actions injected by JS based on context -->
                        </div>
                        <button id="btn-edit-image" onclick="openEditor()"
                            class="hidden p-2 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg transition-all"
                            title="<?php echo Lang::get('media.edit_image'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <span
                                class="text-xs font-black uppercase text-p-muted tracking-widest block mb-3"><?php echo Lang::get('media.usage_tracker'); ?></span>
                            <div id="usage-container" class="space-y-2">
                                <!-- Usage info injected here -->
                                <div class="animate-pulse bg-p-input h-10 rounded-xl"></div>
                            </div>
                        </div>

                        <div>
                            <span
                                class="text-xs font-black uppercase text-p-muted tracking-widest block mb-2"><?php echo Lang::get('media.rename_file'); ?></span>
                            <div class="flex gap-2">
                                <input type="text" id="rename-input" class="form-input !py-3 text-base flex-grow">
                                <button onclick="renameSelected()" class="btn-primary !p-2 !rounded-lg block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Editor Modal -->
    <div id="image-editor-modal"
        class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div class="glass-card w-full max-w-6xl h-[90vh] !p-0 flex flex-col overflow-hidden border-2 border-primary/30">
            <div class="p-6 border-b border-glass-border flex justify-between items-center bg-p-card/50">
                <div>
                    <h3 class="text-2xl font-black text-p-title"><?php echo Lang::get('media.image_editor'); ?></h3>
                    <p class="text-xs text-p-muted uppercase font-black tracking-widest mt-1">
                        <?php echo Lang::get('media.editor_subtitle'); ?>
                    </p>
                </div>
                <button onclick="closeEditor()"
                    class="p-2 hover:bg-red-500/10 text-red-500 rounded-full transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="flex-grow flex overflow-hidden">
                <!-- Workspace (Constrained preview) -->
                <div
                    class="flex-grow bg-black/60 flex items-center justify-center p-12 lg:p-24 overflow-hidden relative">
                    <div class="w-full h-full max-w-[85%] max-h-[85%] flex items-center justify-center">
                        <img id="editor-image-preview"
                            class="max-w-full max-h-full object-contain shadow-[0_0_70px_rgba(0,0,0,1)] rounded-xl border border-white/5">
                    </div>
                </div>

                <!-- Controls (Wider to prevent horizontal cramp) -->
                <div class="w-96 border-l border-glass-border bg-p-input/30 p-8 overflow-y-auto custom-scrollbar">
                    <div class="space-y-10">
                        <!-- Actions -->
                        <div>
                            <span
                                class="text-[10px] font-black uppercase text-p-muted tracking-widest block mb-4"><?php echo Lang::get('media.tools'); ?></span>
                            <div class="grid grid-cols-2 gap-2">
                                <button onclick="setEditorTool('crop')" class="editor-tool-btn active" id="tool-crop">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M6.13 1L6 16a2 2 0 0 0 2 2h15" />
                                        <path d="M1 6.13L16 6a2 2 0 0 1 2 2v15" />
                                    </svg>
                                    <?php echo Lang::get('media.crop'); ?>
                                </button>
                                <button onclick="setEditorTool('filters')" class="editor-tool-btn" id="tool-filters">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                                    </svg>
                                    <?php echo Lang::get('media.filters'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Resize -->
                        <div>
                            <span
                                class="text-[10px] font-black uppercase text-p-muted tracking-widest block mb-4"><?php echo Lang::get('media.dimensions'); ?></span>
                            <div class="flex items-center gap-3">
                                <div>
                                    <label
                                        class="text-[9px] font-bold text-p-muted block mb-1"><?php echo Lang::get('media.width'); ?></label>
                                    <input type="number" id="edit-width"
                                        class="form-input !py-1 !px-2 !rounded-lg text-xs w-20"
                                        oninput="maintainAspect('w')">
                                </div>
                                <span class="pt-4 text-p-muted">×</span>
                                <div>
                                    <label
                                        class="text-[9px] font-bold text-p-muted block mb-1"><?php echo Lang::get('media.height'); ?></label>
                                    <input type="number" id="edit-height"
                                        class="form-input !py-1 !px-2 !rounded-lg text-xs w-20"
                                        oninput="maintainAspect('h')">
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div id="filter-controls" class="hidden">
                            <span
                                class="text-[10px] font-black uppercase text-p-muted tracking-widest block mb-4"><?php echo Lang::get('media.artistic_filters'); ?></span>
                            <div class="grid grid-cols-2 gap-4">
                                <button onclick="applyFilter('none')" class="filter-btn active"
                                    data-filter="none"><?php echo Lang::get('media.filter_original'); ?></button>
                                <button onclick="applyFilter('grayscale')" class="filter-btn"
                                    data-filter="grayscale"><?php echo Lang::get('media.filter_bw'); ?></button>
                                <button onclick="applyFilter('sepia')" class="filter-btn"
                                    data-filter="sepia"><?php echo Lang::get('media.filter_sepia'); ?></button>
                                <button onclick="applyFilter('negative')" class="filter-btn"
                                    data-filter="negative"><?php echo Lang::get('media.filter_invert'); ?></button>
                                <button onclick="applyFilter('vintage')" class="filter-btn"
                                    data-filter="vintage"><?php echo Lang::get('media.filter_vintage'); ?></button>
                                <button onclick="applyFilter('dramatic')" class="filter-btn"
                                    data-filter="dramatic"><?php echo Lang::get('media.filter_dramatic'); ?></button>
                                <button onclick="applyFilter('blur')" class="filter-btn"
                                    data-filter="blur"><?php echo Lang::get('media.filter_blur'); ?></button>
                                <button onclick="applyFilter('sharpen')" class="filter-btn"
                                    data-filter="sharpen"><?php echo Lang::get('media.filter_sharpen'); ?></button>
                            </div>
                        </div>

                        <!-- Optimization -->
                        <div>
                            <span
                                class="text-[10px] font-black uppercase text-p-muted tracking-widest block mb-4"><?php echo Lang::get('media.quality_optimization'); ?></span>
                            <input type="range" id="edit-quality" min="10" max="100" value="85"
                                class="w-full accent-primary h-1 bg-primary/20 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between mt-2">
                                <span class="text-[10px] text-p-muted font-bold">10%</span>
                                <span id="quality-val" class="text-xs text-primary font-black">85%</span>
                                <span class="text-[10px] text-p-muted font-bold">100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-glass-border flex justify-between items-center bg-p-card/50">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="save-copy" checked
                            class="w-4 h-4 rounded border-glass-border bg-p-input text-primary focus:ring-primary/20">
                        <label for="save-copy"
                            class="text-xs font-black text-p-muted uppercase cursor-pointer"><?php echo Lang::get('media.save_as_copy'); ?></label>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button onclick="closeEditor()"
                        class="px-6 py-3 font-black uppercase tracking-widest text-xs text-p-muted hover:text-p-title transition-all"><?php echo Lang::get('common.cancel'); ?></button>
                    <button onclick="saveEdit()" id="btn-save-edit" class="btn-primary !px-8 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        <?php echo Lang::get('common.save_changes'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="media-settings-modal"
        class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="glass-card w-full max-w-md !p-8 shadow-2xl scale-in border-2 border-primary/20">
            <h3 class="text-2xl font-black text-p-title mb-2"><?php echo Lang::get('media.media_settings'); ?></h3>
            <p class="text-p-muted text-sm mb-6 uppercase font-black tracking-widest">
                <?php echo Lang::get('media.retention_behaviors'); ?>
            </p>

            <div class="space-y-6">
                <div>
                    <label
                        class="text-xs font-black text-p-muted uppercase tracking-widest mb-2 block"><?php echo Lang::get('media.retention_days'); ?></label>
                    <input type="number" id="setting-retention" class="form-input" min="1" max="365" placeholder="30">
                    <p class="text-[10px] text-p-muted mt-2"><?php echo Lang::get('media.retention_help'); ?></p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button onclick="closeSettings()"
                        class="btn-primary !bg-p-input !text-p-text flex-grow"><?php echo Lang::get('common.cancel'); ?></button>
                    <button onclick="saveSettings()"
                        class="btn-primary flex-grow"><?php echo Lang::get('media.save_settings'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .media-item {
        @apply glass-card !p-0 overflow-hidden cursor-pointer relative;
        border: 3px solid transparent !important;
        transition: background 0.1s ease, box-shadow 0.1s ease, border-color 0.1s ease;
        transform: none !important;
    }

    .media-item:hover {
        @apply shadow-xl border-primary/20;
    }

    .media-item.selected,
    .media-item.bulk-selected {
        border-color: #38bdf8 !important;
        background: rgba(56, 189, 248, 0.15) !important;
        box-shadow: 0 0 40px rgba(56, 189, 248, 0.4) !important;
        z-index: 10;
        transform: none !important;
    }

    .media-item.selected::after,
    .media-item.bulk-selected::after {
        content: '';
        @apply absolute inset-0 bg-primary/5 pointer-events-none;
    }

    .media-checkbox {
        @apply absolute top-3 left-3 w-6 h-6 border-2 border-primary/50 rounded-lg bg-white/20 backdrop-blur-md opacity-0 transition-all z-10 flex items-center justify-center;
    }

    .media-item:hover .media-checkbox,
    .media-item.selected .media-checkbox,
    .media-item.bulk-selected .media-checkbox {
        @apply opacity-100;
    }

    /* Checkbox scale is fine as it's internal to the item */
    .media-item.selected .media-checkbox,
    .media-item.bulk-selected .media-checkbox {
        @apply bg-primary border-primary text-white scale-110 shadow-[0_0_10px_rgba(56, 189, 248, 0.5)];
    }

    .orphan-indicator {
        @apply absolute top-3 right-3 w-3 h-3 bg-red-500 rounded-full shadow-[0_0_12px_rgba(239, 68, 68, 0.9)] z-10 animate-pulse;
    }

    .editor-tool-btn {
        @apply flex flex-col items-center justify-center gap-3 p-5 rounded-2xl bg-p-input border border-glass-border text-p-muted hover:text-primary hover:border-primary/50 transition-all font-black text-[11px] uppercase tracking-wider shadow-sm;
    }

    .editor-tool-btn.active {
        @apply bg-primary/10 border-primary text-primary shadow-[0_0_20px_rgba(56, 189, 248, 0.15)];
    }

    .filter-btn {
        @apply p-3 rounded-xl bg-p-input border border-glass-border text-p-muted text-[10px] font-black uppercase tracking-widest hover:border-primary/50 hover:text-primary transition-all shadow-sm;
    }

    .filter-btn.active {
        @apply bg-primary/20 border-primary text-primary shadow-[0_0_15px_rgba(56, 189, 248, 0.2)];
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(56, 189, 248, 0.2);
        border-radius: 10px;
    }
</style>

<script>
    let cropper = null;
    let originalAspect = 1;
    let selectedFilter = 'none';

    function openEditor() {
        if (!selectedItem || !selectedItem.is_image) return;

        const modal = document.getElementById('image-editor-modal');
        const img = document.getElementById('editor-image-preview');
        img.src = selectedItem.url + '?t=' + new Date().getTime();

        modal.classList.remove('hidden');

        img.onload = () => {
            originalAspect = img.naturalWidth / img.naturalHeight;
            document.getElementById('edit-width').value = img.naturalWidth;
            document.getElementById('edit-height').value = img.naturalHeight;

            initCropper();
        };

        // Ensure dropzone is hidden when entering editor
        hideDropzone();
    }

    function initCropper() {
        if (cropper) cropper.destroy();
        const img = document.getElementById('editor-image-preview');
        cropper = new Cropper(img, {
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    }

    function setEditorTool(tool) {
        document.getElementById('tool-crop').classList.toggle('active', tool === 'crop');
        document.getElementById('tool-filters').classList.toggle('active', tool === 'filters');
        document.getElementById('filter-controls').classList.toggle('hidden', tool !== 'filters');

        if (tool === 'crop') {
            initCropper();
        } else {
            if (cropper) cropper.destroy();
            cropper = null;
        }
    }

    function applyFilter(filter) {
        selectedFilter = filter;
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === filter);
        });

        // CSS Preview for the user
        const img = document.querySelector('.cropper-container img, #editor-image-preview');
        if (img) {
            let cssFilter = '';
            switch (filter) {
                case 'grayscale': cssFilter = 'grayscale(1)'; break;
                case 'sepia': cssFilter = 'sepia(1)'; break;
                case 'negative': cssFilter = 'invert(1)'; break;
                case 'vintage': cssFilter = 'sepia(0.5) contrast(1.2) brightness(0.9)'; break;
                case 'dramatic': cssFilter = 'contrast(1.5) brightness(0.8)'; break;
                case 'blur': cssFilter = 'blur(2px)'; break;
                case 'sharpen': cssFilter = 'contrast(1.2) brightness(1.1)'; break;
                default: cssFilter = 'none';
            }
            img.style.filter = cssFilter;
        }
    }

    function maintainAspect(changed) {
        const wInput = document.getElementById('edit-width');
        const hInput = document.getElementById('edit-height');

        if (changed === 'w') {
            hInput.value = Math.round(wInput.value / originalAspect);
        } else {
            wInput.value = Math.round(hInput.value * originalAspect);
        }
    }

    document.getElementById('edit-quality').oninput = function () {
        document.getElementById('quality-val').innerText = this.value + '%';
    };

    function closeEditor() {
        if (cropper) cropper.destroy();
        cropper = null;
        document.getElementById('image-editor-modal').classList.add('hidden');
    }

    async function saveEdit() {
        const btn = document.getElementById('btn-save-edit');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <?php echo addslashes(Lang::get('media.processing')); ?>';

        const formData = new FormData();
        formData.append('path', selectedItem.path);
        formData.append('action', 'transform');
        formData.append('filter', selectedFilter);
        formData.append('quality', document.getElementById('edit-quality').value);
        formData.append('save_as_copy', document.getElementById('save-copy').checked);
        formData.append('width', document.getElementById('edit-width').value);
        formData.append('height', document.getElementById('edit-height').value);

        if (cropper) {
            const cropData = cropper.getData(true);
            formData.append('crop', JSON.stringify({
                x: cropData.x,
                y: cropData.y,
                width: cropData.width,
                height: cropData.height
            }));
        }

        try {
            const res = await fetch(`${API_BASE}/edit`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                closeEditor();
                loadFiles(currentPath);
            } else {
                showModal({
                    title: 'Error de Edición',
                    message: data.error,
                    type: 'error',
                    typeLabel: 'MEDIA ENGINE'
                });
            }
        } catch (e) {
            console.error(e);
            showModal({
                title: 'Error de Comunicación',
                message: 'No se pudo contactar con el servidor para procesar la imagen.',
                type: 'error',
                typeLabel: 'NETWORK ERROR'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            hideDropzone(); // Safety clear
        }
    }
    async function restoreSelected() {
        if (!selectedItem) return;
        const trashPath = selectedItem.path.replace('.trash/', '');

        try {
            const formData = new FormData();
            formData.append('trash_path', trashPath);
            const res = await fetch(`${API_BASE}/restore`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                loadFiles(currentPath);
                selectedItem = null;
                document.getElementById('media-sidebar').classList.add('hidden');
            } else {
                showModal({
                    title: 'Error de Restauración',
                    message: data.error,
                    type: 'error'
                });
            }
        } catch (e) { console.error(e); }
    }

    async function purgeSelected() {
        if (!selectedItem) return;
        const trashPath = selectedItem.path.replace('.trash/', '');

        showModal({
            title: 'Borrado Permanente',
            message: `¿Estás seguro de que deseas eliminar permanentemente "${selectedItem.name}" de la papelera? Esta acción no se puede revertir.`,
            type: 'confirm',
            onConfirm: async function () {
                const formData = new FormData();
                formData.append('trash_path', trashPath);
                const res = await fetch(`${API_BASE}/purge`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    loadFiles(currentPath);
                    selectedItem = null;
                    document.getElementById('media-sidebar').classList.add('hidden');
                }
            }
        });
    }
    let currentData = null;
    let selectedItem = null;
    let bulkSelected = [];
    let viewMode = 'grid';
    let currentPath = '';
    let isBulkMode = false;
    let orphanCache = {}; // Cache usage results to avoid repeated fetches

    const API_BASE = '<?php echo $baseUrl; ?>admin/media/api';

    async function loadFiles(path = '') {
        deselectAll(); // Clear selection when changing folder
        currentPath = path;
        const container = document.getElementById('media-container');

        try {
            const response = await fetch(`${API_BASE}/list?path=${encodeURIComponent(path)}`);
            currentData = await response.json();

            renderBreadcrumbs(currentData.breadcrumbs);
            renderItems(currentData.items);

            // Check orphans in background
            checkMultipleOrphans(currentData.items);

            // Update trash button
            const trashBtn = document.getElementById('btn-view-trash');
            if (currentPath === '.trash') {
                trashBtn.classList.add('!bg-primary', '!text-white');
                trashBtn.onclick = () => loadFiles('');
            } else {
                trashBtn.classList.remove('!bg-primary', '!text-white');
                trashBtn.onclick = () => loadFiles('.trash');
            }
        } catch (error) {
            console.error('Error loading files:', error);
        }
    }

    async function checkMultipleOrphans(items) {
        // Parallel check for orphan status
        for (const item of items) {
            if (item.is_dir) continue;
            if (orphanCache[item.url] !== undefined) continue;

            // We do it sequentially or in small batches to not overwhelm
            fetch(`${API_BASE}/usage?url=${encodeURIComponent(item.url)}`)
                .then(r => r.json())
                .then(data => {
                    orphanCache[item.url] = data.usage.length === 0;
                    // Update UI if item is still visible
                    const indicator = document.querySelector(`[data-path="${item.path}"] .orphan-indicator`);
                    if (indicator) {
                        indicator.classList.toggle('hidden', !orphanCache[item.url]);
                    }
                });
        }
    }

    function renderBreadcrumbs(crumbs) {
        const bc = document.getElementById('media-breadcrumbs');
        bc.innerHTML = crumbs.map((crumb, index) => `
            <div class="flex items-center gap-2">
                ${index > 0 ? '<span class="text-p-muted opacity-30">/</span>' : ''}
                <button onclick="loadFiles('${crumb.path}')" class="text-sm font-black uppercase tracking-widest ${index === crumbs.length - 1 ? 'text-primary' : 'text-p-muted hover:text-primary'}">
                    ${crumb.name}
                </button>
            </div>
        `).join('');
    }

    function renderItems(items) {
        const container = document.getElementById('media-container');
        const filter = document.getElementById('media-search').value.toLowerCase();

        if (viewMode === 'grid') {
            container.className = "grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4";
            container.innerHTML = items
                .filter(i => i.name.toLowerCase().includes(filter))
                .map(item => {
                    const isSelected = selectedItem?.path === item.path;
                    const isBulk = bulkSelected.includes(item.path);
                    const isOrphan = orphanCache[item.url];

                    return `
                    <div onclick="toggleSelect(this, ${JSON.stringify(item).replace(/"/g, '&quot;')}, event)" 
                         data-path="${item.path}"
                         class="media-item ${isSelected ? 'selected' : ''} ${isBulk ? 'bulk-selected' : ''}" 
                         ondblclick="${item.is_dir ? `loadFiles('${item.path}')` : ''}">
                        
                        <div class="media-checkbox">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        
                        ${!item.is_dir ? `<div class="orphan-indicator ${isOrphan ? '' : 'hidden'}" title="Archivo sin referencias (Orphan)"></div>` : ''}

                        <div class="aspect-square bg-black/5 flex items-center justify-center overflow-hidden">
                            ${getFilePreview(item)}
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-bold text-p-title truncate">${item.name}</p>
                            <p class="text-[10px] text-p-muted uppercase font-black tracking-widest">${item.is_dir ? 'Folder' : item.extension}</p>
                        </div>
                    </div>
                `}).join('');
        } else {
            container.className = "flex flex-col gap-2";
            container.innerHTML = `
                <div class="flex items-center gap-4 px-6 py-4 text-xs font-black text-p-muted uppercase tracking-widest border-b border-glass-border">
                    <div class="w-10"></div>
                    <div class="flex-grow"><?php echo Lang::get('media.name_header'); ?></div>
                    <div class="w-32"><?php echo Lang::get('media.type_header'); ?></div>
                    <div class="w-32"><?php echo Lang::get('media.size_header'); ?></div>
                    <div class="w-40"><?php echo Lang::get('media.modified_header'); ?></div>
                </div>
                ${items
                    .filter(i => i.name.toLowerCase().includes(filter))
                    .map(item => {
                        const isSelected = selectedItem?.path === item.path;
                        const isBulk = bulkSelected.includes(item.path);
                        const isOrphan = orphanCache[item.url];

                        return `
                        <div onclick="toggleSelect(this, ${JSON.stringify(item).replace(/"/g, '&quot;')}, event)" 
                             data-path="${item.path}"
                             class="flex items-center gap-4 px-6 py-4 glass-card !p-0 !rounded-xl !bg-transparent hover:!bg-primary/5 cursor-pointer border border-transparent ${isSelected ? '!border-primary/50 !bg-primary/5' : ''} ${isBulk ? '!border-primary/50 !bg-primary/20' : ''}"
                             ondblclick="${item.is_dir ? `loadFiles('${item.path}')` : ''}">
                            <div class="w-10 h-10 flex items-center justify-center opacity-60 relative">
                                ${getFileIcon(item, 28)}
                                ${!item.is_dir ? `<div class="orphan-indicator ${isOrphan ? '' : 'hidden'} !top-0 !right-0 !w-2 !h-2"></div>` : ''}
                            </div>
                            <div class="flex-grow font-bold text-base truncate flex items-center gap-2">
                                ${isBulk ? '<div class="w-2 h-2 rounded-full bg-primary"></div>' : ''}
                                ${item.name}
                            </div>
                            <div class="w-32 text-xs font-black uppercase text-p-muted">${item.is_dir ? '<?php echo Lang::get('media.folder'); ?>' : item.extension}</div>
                            <div class="w-32 text-xs font-black uppercase text-p-muted">${formatSize(item.size)}</div>
                            <div class="w-40 text-xs font-black uppercase text-p-muted">${formatDate(item.mtime)}</div>
                        </div>
                    `}).join('')}
            `;
        }

        if (items.length === 0) {
            container.innerHTML = `
                <div class="col-span-full py-20 text-center opacity-40">
                    <p><?php echo Lang::get('media.no_files'); ?></p>
                </div>
            `;
        }
    }

    function getFilePreview(item) {
        if (item.is_dir) {
            return '<svg class="text-primary/40" width="60" height="60" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
        }
        if (item.is_image) {
            return `<img src="${item.url}" class="w-full h-full object-cover">`;
        }
        return getFileIcon(item, 60);
    }

    function getFileIcon(item, size = 20) {
        const ext = item.extension || '';
        let color = 'text-p-muted';
        if (ext === 'pdf') color = 'text-red-400';
        if (['zip', 'rar'].includes(ext)) color = 'text-yellow-400';
        if (['doc', 'docx'].includes(ext)) color = 'text-blue-400';
        if (['xls', 'xlsx'].includes(ext)) color = 'text-green-400';

        return `<svg class="${color}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>`;
    }

    function toggleSelect(el, item, event) {
        if (event.ctrlKey || event.metaKey || isBulkMode) {
            if (bulkSelected.includes(item.path)) {
                bulkSelected = bulkSelected.filter(p => p !== item.path);
            } else {
                bulkSelected.push(item.path);
            }
            updateSelectionUI();
            renderItems(currentData.items);
            return;
        }

        document.querySelectorAll('.media-item, .selected').forEach(e => e.classList.remove('selected', '!border-primary/50', '!bg-primary/5'));
        el.classList.add(viewMode === 'grid' ? 'selected' : '!border-primary/50', '!bg-primary/5');

        selectedItem = item;
        showSidebar(item);
    }

    function updateSelectionUI() {
        const actions = document.getElementById('selection-actions');
        const count = document.getElementById('selection-count');

        if (bulkSelected.length > 0) {
            actions.classList.remove('hidden');
            count.innerText = '<?php echo addslashes(Lang::get('media.selected_count')); ?>'.replace(':count', bulkSelected.length);
            isBulkMode = true;
        } else {
            actions.classList.add('hidden');
            isBulkMode = false;
        }
    }

    function deselectAll() {
        bulkSelected = [];
        selectedItem = null;
        isBulkMode = false;
        document.querySelectorAll('.media-item, .selected').forEach(e => e.classList.remove('selected', 'bulk-selected', '!border-primary/50', '!bg-primary/5'));
        const sidebar = document.getElementById('media-sidebar');
        if (sidebar) sidebar.classList.add('hidden');
        updateSelectionUI();
    }

    async function showSidebar(item) {
        const sidebar = document.getElementById('media-sidebar');
        sidebar.classList.remove('hidden');

        const actions = document.getElementById('sidebar-actions-container');
        const isInTrash = item.path.startsWith('.trash');

        if (isInTrash) {
            actions.innerHTML = `
                <button onclick="restoreSelected()" class="p-2 bg-green-500/10 text-green-600 hover:bg-green-600 hover:text-white rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.restore_file')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </button>
                <button onclick="purgeSelected()" class="p-2 bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.permanent_delete')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </button>
            `;
        } else {
            actions.innerHTML = `
                <button onclick="copyCurrentUrl('url')" class="p-2 bg-p-input hover:bg-primary/10 hover:text-primary rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.copy_url')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </button>
                <button onclick="copyCurrentUrl('markdown')" class="p-2 bg-p-input hover:bg-primary/10 hover:text-primary rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.copy_markdown')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
                </button>
                <button onclick="copyCurrentUrl('html')" class="p-2 bg-p-input hover:bg-primary/10 hover:text-primary rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.copy_html')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </button>
                <button onclick="deleteSelected()" class="p-2 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition-all" title="<?php echo addslashes(Lang::get('media.delete_action')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </button>
            `;
        }

        document.getElementById('sidebar-name').innerText = item.name;
        document.getElementById('sidebar-meta').innerText = `${item.is_dir ? '<?php echo Lang::get('media.folder'); ?>' : item.extension.toUpperCase()} • ${formatSize(item.size)}`;
        document.getElementById('rename-input').value = item.name;

        document.getElementById('btn-edit-image').classList.toggle('hidden', !item.is_image);

        const preview = document.getElementById('sidebar-preview');
        if (item.is_image) {
            preview.innerHTML = `<img src="${item.url}" class="w-full h-full object-contain p-2">`;
        } else {
            preview.innerHTML = `<div class="p-10 opacity-40">${getFileIcon(item, 80)}</div>`;
        }

        // Check usage
        const usageContainer = document.getElementById('usage-container');
        if (item.is_dir) {
            usageContainer.innerHTML = '<p class="text-xs text-p-muted italic"><?php echo Lang::get('media.no_usage_folder'); ?></p>';
            return;
        }

        usageContainer.innerHTML = '<div class="animate-pulse bg-p-input h-10 rounded-xl"></div>';

        try {
            const res = await fetch(`${API_BASE}/usage?url=${encodeURIComponent(item.url)}`);
            const data = await res.json();

            if (data.usage.length === 0) {
                usageContainer.innerHTML = `
                    <div class="p-3 bg-red-400/10 text-red-500 rounded-xl border border-red-500/20">
                        <p class="text-xs font-black tracking-tight leading-none mb-1"><?php echo Lang::get('media.unreferenced'); ?></p>
                        <p class="text-sm font-medium"><?php echo Lang::get('media.orphan_desc'); ?></p>
                    </div>
                `;
            } else {
                usageContainer.innerHTML = data.usage.map(u => `
                    <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=${u.db_id}&table=${u.table}" class="block p-3 bg-green-400/10 text-green-600 rounded-xl border border-green-500/20 hover:scale-[1.02] transition-transform">
                        <p class="text-xs font-black tracking-tight leading-none mb-1">${u.database.toUpperCase()}</p>
                        <p class="text-sm font-bold">${u.table} <span class="opacity-50 font-normal">(${u.row_ids.length} rows)</span></p>
                    </a>
                `).join('');
            }
        } catch (e) {
            usageContainer.innerHTML = '<p class="text-xs text-red-400"><?php echo Lang::get('media.error_usage'); ?></p>';
        }
    }

    function setViewMode(mode) {
        viewMode = mode;
        document.getElementById('btn-grid-view').className = mode === 'grid' ? 'p-2 rounded-lg transition-all text-primary bg-primary/10' : 'p-2 rounded-lg transition-all text-p-muted hover:text-p-text';
        document.getElementById('btn-list-view').className = mode === 'list' ? 'p-2 rounded-lg transition-all text-primary bg-primary/10' : 'p-2 rounded-lg transition-all text-p-muted hover:text-p-text';
        renderItems(currentData.items);
    }

    function debounceFilterFiles() {
        if (this.timeout) clearTimeout(this.timeout);
        this.timeout = setTimeout(() => renderItems(currentData.items), 300);
    }

    async function handleUpload(files) {
        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', currentPath);

            try {
                const res = await fetch(`${API_BASE}/upload`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    loadFiles(currentPath);
                } else {
                    showModal({
                        title: '<?php echo addslashes(Lang::get('media.upload_error')); ?>',
                        message: data.error || '<?php echo addslashes(Lang::get('media.upload_error_unknown')); ?>',
                        type: 'error'
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }
    }

    async function deleteSelected() {
        if (!selectedItem) return;

        showModal({
            title: '<?php echo addslashes(Lang::get('media.confirm_delete_title')); ?>',
            message: `<?php echo addslashes(Lang::get('media.confirm_delete_msg')); ?>`.replace(':name', selectedItem.name),
            type: 'confirm',
            confirmText: '<?php echo addslashes(Lang::get('media.confirm_delete_btn')); ?>',
            safetyCheck: '<?php echo addslashes(Lang::get('media.confirm_delete_safety')); ?>',
            onConfirm: async function () {
                try {
                    const formData = new FormData();
                    formData.append('path', selectedItem.path);
                    const res = await fetch(`${API_BASE}/delete`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        selectedItem = null;
                        document.getElementById('media-sidebar').classList.add('hidden');
                        loadFiles(currentPath);
                    } else {
                        showModal({
                            title: '<?php echo addslashes(Lang::get('common.error')); ?>',
                            message: data.error || '<?php echo addslashes(Lang::get('common.unknown_error')); ?>',
                            type: 'error'
                        });
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        });
    }

    async function renameSelected() {
        const newName = document.getElementById('rename-input').value;
        if (!selectedItem || !newName || newName === selectedItem.name) return;

        try {
            const formData = new FormData();
            formData.append('old_path', selectedItem.path);
            formData.append('new_name', newName);
            const res = await fetch(`${API_BASE}/rename`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                loadFiles(currentPath);
            } else {
                showModal({
                    title: '<?php echo addslashes(Lang::get('media.rename_error_title')); ?>',
                    message: data.error || '<?php echo addslashes(Lang::get('common.unknown_error')); ?>',
                    type: 'error'
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    function copyCurrentUrl(format = 'url') {
        if (!selectedItem) return;

        let textToCopy = selectedItem.url;

        if (format === 'markdown') {
            textToCopy = `![${selectedItem.name}](${selectedItem.url})`;
        } else if (format === 'html') {
            textToCopy = `<img src="${selectedItem.url}" alt="${selectedItem.name}">`;
        } else if (format === 'relative') {
            textToCopy = 'uploads/' + selectedItem.path;
        }

        navigator.clipboard.writeText(textToCopy);

        // Visual feedback
        const btn = event.currentTarget;
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        btn.classList.add('text-green-500', 'bg-green-500/10');
        setTimeout(() => {
            btn.innerHTML = originalIcon;
            btn.classList.remove('text-green-500', 'bg-green-500/10');
        }, 2000);
    }

    async function bulkDelete() {
        if (bulkSelected.length === 0) return;

        showModal({
            title: '<?php echo addslashes(Lang::get('media.bulk_delete_title')); ?>',
            message: '<?php echo addslashes(Lang::get('media.bulk_delete_msg')); ?>'.replace(':count', bulkSelected.length),
            type: 'confirm',
            onConfirm: async function () {
                const formData = new FormData();
                bulkSelected.forEach(p => formData.append('paths[]', p));

                const res = await fetch(`${API_BASE}/bulk-delete`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                bulkSelected = [];
                updateSelectionUI();
                loadFiles(currentPath);
            }
        });
    }

    async function bulkMove() {
        if (bulkSelected.length === 0) return;

        const target = prompt("<?php echo addslashes(Lang::get('media.bulk_move_prompt')); ?>", "");
        if (target === null) return;

        const formData = new FormData();
        bulkSelected.forEach(p => formData.append('paths[]', p));
        formData.append('target', target);

        const res = await fetch(`${API_BASE}/bulk-move`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.error && data.error.length > 0) {
            showModal({
                title: '<?php echo addslashes(Lang::get('media.move_error_title')); ?>',
                message: '<?php echo addslashes(Lang::get('media.move_error_msg')); ?>'.replace(':error', data.error.join(', ')),
                type: 'error'
            });
        }

        bulkSelected = [];
        updateSelectionUI();
        loadFiles(currentPath);
    }

    function showSettings() {
        const modal = document.getElementById('media-settings-modal');
        document.getElementById('setting-retention').value = currentData.settings?.trash_retention || 30;
        modal.classList.remove('hidden');
    }

    function closeSettings() {
        document.getElementById('media-settings-modal').classList.add('hidden');
    }

    async function saveSettings() {
        const retention = document.getElementById('setting-retention').value;
        const formData = new FormData();
        formData.append('trash_retention', retention);

        const res = await fetch(`${API_BASE}/settings`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeSettings();
            loadFiles(currentPath);
        }
    }

    // Drag & Drop
    const dropzone = document.getElementById('dropzone');
    let dragCounter = 0;

    function showDropzone() {
        dropzone.classList.remove('opacity-0', 'pointer-events-none');
    }

    function hideDropzone() {
        dropzone.classList.add('opacity-0', 'pointer-events-none');
        dragCounter = 0;
    }

    window.addEventListener('dragenter', e => {
        e.preventDefault();
        dragCounter++;
        if (dragCounter === 1) showDropzone();
    });

    window.addEventListener('dragleave', e => {
        e.preventDefault();
        dragCounter--;
        if (dragCounter === 0) hideDropzone();
    });

    window.addEventListener('dragover', e => {
        e.preventDefault();
    });

    window.addEventListener('drop', e => {
        e.preventDefault();
        hideDropzone();
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleUpload(e.dataTransfer.files);
        }
    });

    // Initial Load
    loadFiles();

    // Helpers
    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function formatDate(timestamp) {
        return new Date(timestamp * 1000).toLocaleDateString();
    }
</script>