@extends('layouts.main')

@section('title', ($id ? \App\Core\Lang::get('crud.edit') : \App\Core\Lang::get('crud.new')) . ' ' . ucfirst($ctx['table']))

@section('content')
<header class="mb-12 text-center">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
        {{ $id ? \App\Core\Lang::get('crud.edit') : \App\Core\Lang::get('crud.new') }}
        <span class="text-primary italic">{{ ucfirst($ctx['table']) }}</span>
    </h1>
    <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('crud_form.configuring_record') }}
        <b>{{ $ctx['database']['name'] }}</b>.
    </p>
</header>

<section class="form-container">
    <form action="{{ $baseUrl }}admin/crud/save" method="POST" id="crud-form" enctype="multipart/form-data" class="w-full">
        {!! $csrf_field !!}
        <input type="hidden" name="db_id" value="{{ $ctx['db_id'] }}">
        <input type="hidden" name="table" value="{{ $ctx['table'] }}">
        <input type="hidden" name="id" value="{{ $record['id'] ?? '' }}">
        <div class="glass-card w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                @foreach ($ctx['fields'] as $field)
                    @php
                    if (!$field['is_editable']) continue;
                    $val = $record[$field['field_name']] ?? '';
                    $isFullWidth = in_array($field['view_type'], ['wysiwyg', 'textarea', 'gallery', 'image']);
                    @endphp
                    <div class="{{ $isFullWidth ? 'md:col-span-2' : '' }} space-y-4">
                        <label class="form-label flex items-center gap-2">
                            {{ $field['field_name'] }}
                            @if ($field['is_required'])
                                <span
                                    class="text-primary text-[10px] font-black uppercase tracking-tighter">[{{ \App\Core\Lang::get('fields.required') }}]</span>
                            @endif
                        </label>

                        @if (!empty($field['is_foreign_key']) && isset($foreignOptions[$field['field_name']]))
                            <div class="relative">
                                <select name="{{ $field['field_name'] }}" class="custom-select" {{ $field['is_required'] ? 'required' : '' }}>
                                    <option value="">{{ \App\Core\Lang::get('common.none') }}</option>
                                    @foreach ($foreignOptions[$field['field_name']] as $opt)
                                        <option value="{{ $opt['id'] }}" {{ ($val == $opt['id']) ? 'selected' : '' }}>
                                            {{ $opt['label'] ?? $opt['id'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            @switch($field['view_type'])
                                @case('text')
                                    <input type="text" name="{{ $field['field_name'] }}"
                                        value="{{ $val }}" {{ $field['is_required'] ? 'required' : '' }}
                                        class="form-input" data-type="{{ $field['data_type'] }}">
                                    @break

                                @case('textarea')
                                    <textarea name="{{ $field['field_name'] }}" rows="4" {{ $field['is_required'] ? 'required' : '' }} class="form-input">{{ $val }}</textarea>
                                    @break

                                @case('wysiwyg')
                                    <textarea name="{{ $field['field_name'] }}" class="editor">{!! $val !!}</textarea>
                                    @break

                                @case('image')
                                    <div class="glass-card !bg-white/5 overflow-hidden">
                                        <div class="flex flex-col lg:flex-row gap-8">
                                            <div id="preview-container-{{ $field['field_name'] }}"
                                                class="{{ empty($val) ? 'hidden' : '' }} w-full lg:w-48 aspect-square rounded-xl overflow-hidden border border-glass-border relative group">
                                                <img id="preview-img-{{ $field['field_name'] }}"
                                                    src="{{ (strpos($val, 'http') === 0) ? $val : $baseUrl . $val }}"
                                                    class="w-full h-full object-cover">
                                                <div
                                                    class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-4">
                                                    <p id="preview-path-{{ $field['field_name'] }}"
                                                        class="text-[8px] font-mono text-primary break-all text-center">{{ $val }}
                                                    </p>
                                                </div>
                                                <button type="button" onclick="clearField('{{ $field['field_name'] }}')"
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
                                                        {{ \App\Core\Lang::get('crud.resource_url') }}
                                                    </label>
                                                    <div class="flex gap-4">
                                                        <input type="text" name="gallery_{{ $field['field_name'] }}"
                                                            id="gallery-{{ $field['field_name'] }}"
                                                            value="{{ $val }}"
                                                            placeholder="{{ \App\Core\Lang::get('crud.url_placeholder') }}"
                                                            class="form-input flex-1 !bg-white/5"
                                                            oninput="updatePreviewFromUrl('{{ $field['field_name'] }}', this.value)">

                                                        <button type="button"
                                                            onclick="openMediaGallery('{{ $field['field_name'] }}')"
                                                            class="btn-gallery whitespace-nowrap !py-2 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            {{ \App\Core\Lang::get('crud.gallery_btn') }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col gap-3 pt-6 border-t border-white/5">
                                                    <label
                                                        class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
                                                        <span class="w-1 h-1 rounded-full bg-primary/40"></span>
                                                        {{ \App\Core\Lang::get('crud.upload_new') }}
                                                    </label>
                                                    <input type="file" name="{{ $field['field_name'] }}"
                                                        id="file-{{ $field['field_name'] }}" {{ (($field['is_required'] ?? false) && empty($val)) ? 'required' : '' }}
                                                        class="text-xs w-full file:bg-primary/20 file:border file:border-primary/30 file:px-4 file:py-2 file:rounded-lg file:text-primary file:font-bold file:mr-4 file:cursor-pointer hover:file:bg-primary/30 transition-all"
                                                        onchange="if(this.value) { document.getElementById('gallery-{{ $field['field_name'] }}').required = false; }">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('gallery')
                                    <div class="glass-card !bg-white/5 p-6">
                                        <div class="flex items-center justify-between mb-6">
                                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
                                                <span class="w-1 h-1 rounded-full bg-primary"></span>
                                                {{ \App\Core\Lang::get('fields.types.gallery') }}
                                            </label>
                                            <button type="button" onclick="openMediaGallery('{{ $field['field_name'] }}', true)"
                                                class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all">
                                                + {{ \App\Core\Lang::get('crud.gallery_btn') }}
                                            </button>
                                        </div>
                                        
                                        <div id="gallery-previews-{{ $field['field_name'] }}" 
                                            class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 min-h-[100px] p-4 rounded-xl bg-black/20 border border-white/5">
                                            @php
                                            $images = !empty($val) ? explode(',', $val) : [];
                                            @endphp
                                            @foreach ($images as $img)
                                                @if(empty(trim($img))) @continue @endif
                                                <div class="relative aspect-square rounded-lg overflow-hidden border border-white/10 group">
                                                    <img src="{{ (strpos($img, 'http') === 0) ? $img : $baseUrl . $img }}" class="w-full h-full object-cover">
                                                    <button type="button" onclick="removeGalleryImage('{{ $field['field_name'] }}', '{{ $img }}')"
                                                        class="absolute top-1 right-1 bg-red-500/80 text-white p-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                            @if(empty($images))
                                                <div class="col-span-full flex flex-col items-center justify-center py-6 opacity-20">
                                                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="1.5"></path></svg>
                                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ \App\Core\Lang::get('common.none') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <input type="hidden" name="gallery_{{ $field['field_name'] }}" id="gallery-{{ $field['field_name'] }}" value="{{ $val }}">
                                    </div>
                                    @break

                                @case('boolean')
                                    <div class="flex items-center h-full pt-8">
                                        <label class="flex items-center gap-4 cursor-pointer group/toggle">
                                            <div class="relative">
                                                <input type="checkbox" name="{{ $field['field_name'] }}" value="1" {{ $val ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-14 h-7 bg-white/5 border border-glass-border rounded-full peer peer-checked:bg-primary/20 peer-checked:border-primary/50 transition-all duration-300"></div>
                                                <div class="absolute left-1 top-1 w-5 h-5 bg-slate-500 rounded-full transition-all duration-300 peer-checked:left-8 peer-checked:bg-primary peer-checked:shadow-[0_0_10px_rgba(56,189,248,0.5)]"></div>
                                            </div>
                                            <span class="text-xs font-black uppercase tracking-widest text-p-muted group-hover/toggle:text-primary transition-colors">{{ \App\Core\Lang::get('crud.toggle_status') }}</span>
                                        </label>
                                    </div>
                                    @break

                                @case('datetime') 
                                    @php
                                    $formattedDate = '';
                                    if (!empty($val)) {
                                        $date = new DateTime($val);
                                        $formattedDate = $date->format('Y-m-d\TH:i');
                                    }
                                    @endphp
                                    <input type="datetime-local" name="{{ $field['field_name'] }}"
                                        value="{{ $formattedDate }}" {{ $field['is_required'] ? 'required' : '' }}
                                        class="form-input">
                                    @break
                                
                                @case('file')
                                    <div class="glass-card !bg-white/5 overflow-hidden">
                                        <div class="flex flex-col lg:flex-row gap-8">
                                            <div id="preview-container-{{ $field['field_name'] }}"
                                                class="{{ empty($val) ? 'hidden' : '' }} w-full lg:w-48 flex items-center justify-center rounded-xl overflow-hidden border border-glass-border relative group bg-black/20">
                                                
                                                <div class="flex flex-col items-center gap-2 p-4">
                                                    <svg class="w-12 h-12 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <p class="text-[10px] font-black uppercase text-p-muted text-center truncate w-full px-2" id="preview-filename-{{ $field['field_name'] }}">
                                                        {{ basename($val) }}
                                                    </p>
                                                </div>

                                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-4">
                                                    <p id="preview-path-{{ $field['field_name'] }}"
                                                        class="text-[8px] font-mono text-primary break-all text-center">{{ $val }}
                                                    </p>
                                                </div>
                                                <button type="button" onclick="clearField('{{ $field['field_name'] }}')"
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
                                                        {{ \App\Core\Lang::get('crud.resource_url') }}
                                                    </label>
                                                    <div class="flex gap-4">
                                                        <input type="text" name="gallery_{{ $field['field_name'] }}"
                                                            id="gallery-{{ $field['field_name'] }}"
                                                            value="{{ $val }}"
                                                            placeholder="{{ \App\Core\Lang::get('crud.url_placeholder') }}"
                                                            class="form-input flex-1 !bg-white/5"
                                                            oninput="updatePreviewFromUrl('{{ $field['field_name'] }}', this.value)">

                                                        <button type="button"
                                                            onclick="openMediaGallery('{{ $field['field_name'] }}')"
                                                            class="btn-gallery whitespace-nowrap !py-2 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            {{ \App\Core\Lang::get('crud.gallery_btn') }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col gap-3 pt-6 border-t border-white/5">
                                                    <label
                                                        class="text-[10px] font-black text-p-muted uppercase tracking-widest flex items-center gap-2">
                                                        <span class="w-1 h-1 rounded-full bg-primary/40"></span>
                                                        {{ \App\Core\Lang::get('crud.upload_new') }}
                                                    </label>
                                                    <input type="file" name="{{ $field['field_name'] }}"
                                                        id="file-{{ $field['field_name'] }}" {{ (($field['is_required'] ?? false) && empty($val)) ? 'required' : '' }}
                                                        class="text-xs w-full file:bg-primary/20 file:border file:border-primary/30 file:px-4 file:py-2 file:rounded-lg file:text-primary file:font-bold file:mr-4 file:cursor-pointer hover:file:bg-primary/30 transition-all"
                                                        onchange="if(this.value) { document.getElementById('gallery-{{ $field['field_name'] }}').required = false; }">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @break

                            @endswitch 
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="pt-12 border-t border-glass-border flex justify-end gap-6">
                <a href="{{ $baseUrl }}admin/crud/list?db_id={{ $ctx['db_id'] }}&table={{ $ctx['table'] }}"
                    class="btn-primary !bg-slate-800 !text-slate-300">{{ \App\Core\Lang::get('common.abort') }}</a>
                <button type="submit" class="btn-primary">{{ \App\Core\Lang::get('common.commit') }}</button>
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
                    {{ \App\Core\Lang::get('media.explorer') }}
                </h2>
                <p class="text-[10px] text-p-muted font-bold uppercase tracking-[0.3em] mt-1">
                    {{ \App\Core\Lang::get('media.system') }}
                </p>
            </div>
            <div class="flex items-center gap-6">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="media-search" placeholder="{{ \App\Core\Lang::get('media.search') }}"
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
                            class="text-[9px] font-black text-primary uppercase tracking-widest text-center">{{ \App\Core\Lang::get('media.upload') }}</span>
                        <input type="file" class="hidden" onchange="handleDirectUpload(this.files[0])"
                            accept="image/*,video/*,application/pdf,.zip,.rar,.doc,.docx,.txt">
                    </label>
                </div>
                <div>
                    <h4
                        class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        {{ \App\Core\Lang::get('media.temporal_nodes') }}
                    </h4>
                    <div id="filter-dates" class="space-y-1">
                        <!-- Dates inject here -->
                    </div>
                </div>

                <div>
                    <h4
                        class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        {{ \App\Core\Lang::get('media.entity_collections') }}
                    </h4>
                    <div id="filter-tables" class="space-y-1">
                        <!-- Tables inject here -->
                    </div>
                </div>

                    <h4 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary/40"></span>
                        {{ \App\Core\Lang::get('media.resource_type') }}
                    </h4>
                    <div id="filter-types" class="space-y-1">
                        <button onclick="setTypeFilter('all')" id="type-filter-all" class="type-filter-btn active w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all bg-primary/20 text-primary">{{ \App\Core\Lang::get('media.all_resources') }}</button>
                        <button onclick="setTypeFilter('images')" id="type-filter-images" class="type-filter-btn w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all text-p-muted hover:bg-white/5">{{ \App\Core\Lang::get('media.only_images') }}</button>
                        <button onclick="setTypeFilter('files')" id="type-filter-files" class="type-filter-btn w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all text-p-muted hover:bg-white/5">{{ \App\Core\Lang::get('media.only_files') }}</button>
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
                            {{ \App\Core\Lang::get('media.drop') }}
                        </h3>
                        <p class="text-[10px] text-primary font-black uppercase tracking-widest mt-2">Uploading to
                            Neural Network</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-glass-border flex justify-between items-center">
            <span id="gallery-status"
                class="text-[10px] font-bold text-p-muted uppercase tracking-widest">{{ \App\Core\Lang::get('media.scanning') }}</span>
            <div class="flex gap-4">
                <button onclick="closeMediaGallery()"
                    class="btn-outline">{{ \App\Core\Lang::get('media.abort_selection') }}</button>
                <button id="gallery-done-btn" onclick="closeMediaGallery()"
                    class="btn-primary !py-2 hidden">{{ \App\Core\Lang::get('common.commit') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const tinyMceScript = document.createElement('script');
    tinyMceScript.src = `https://cdn.tiny.cloud/1/${window.appConfig.tinyMceApiKey}/tinymce/6/tinymce.min.js`;
    tinyMceScript.referrerPolicy = "origin";
    document.head.appendChild(tinyMceScript);
</script>
<script>
    let currentTargetField = null;
    let isMultiMode = false;
    let allMediaData = null;
    let activeDateFilter = 'all';
    let activeTableFilter = 'all';
    let activeTypeFilter = 'all'; // New: all, images, files
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

        fetch('{{ $baseUrl }}admin/media/list?db_id={{ $ctx['db_id'] }}')
            .then(res => res.json())
            .then(data => {
                allMediaData = data;
                renderFilters();
                renderGrid();
            });
    }

    function setTypeFilter(type) {
        activeTypeFilter = type;
        
        // Update UI classes
        document.querySelectorAll('.type-filter-btn').forEach(btn => {
            btn.classList.remove('bg-primary/20', 'text-primary');
            btn.classList.add('text-p-muted');
        });
        
        const activeBtn = document.getElementById('type-filter-' + type);
        if (activeBtn) {
            activeBtn.classList.add('bg-primary/20', 'text-primary');
            activeBtn.classList.remove('text-p-muted');
        }
        
        renderGrid();
    }

    function renderGrid() {
        if(!allMediaData) return;
        const grid = document.getElementById('mediaGrid');
        const status = document.getElementById('gallery-status');
        grid.innerHTML = '';

        let filtered = allMediaData.files;
        if (activeDateFilter !== 'all') filtered = filtered.filter(f => f.date_folder === activeDateFilter);
        if (activeTableFilter !== 'all') filtered = filtered.filter(f => f.table_folder === activeTableFilter);
        
        // Filter by Type
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (activeTypeFilter === 'images') {
            filtered = filtered.filter(f => imageExtensions.includes(f.extension));
        } else if (activeTypeFilter === 'files') {
            filtered = filtered.filter(f => !imageExtensions.includes(f.extension));
        }

        if (searchQuery) filtered = filtered.filter(f => f.name.toLowerCase().includes(searchQuery));

        status.innerText = `{!! addslashes(\App\Core\Lang::get('media.sync')) !!}`.replace(':count', filtered.length);

        const currentVal = document.getElementById('gallery-' + currentTargetField)?.value || '';
        const selectedImages = currentVal.split(',').filter(x => x.trim());

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-20 text-center text-p-muted uppercase font-black tracking-widest opacity-30">{!! addslashes(\App\Core\Lang::get('media.null')) !!}</div>';
            return;
        }

        filtered.forEach(item => {
            const isSelected = selectedImages.includes(item.url);
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(item.extension);
            const div = document.createElement('div');
            div.className = `group relative aspect-square bg-black/40 rounded-2xl overflow-hidden cursor-pointer border ${isSelected ? 'border-primary ring-2 ring-primary/20' : 'border-glass-border'} hover:border-primary/50 transition-all shadow-xl`;
            div.onclick = () => selectMedia(item.url);

            let previewHtml = '';
            if (isImage) {
                previewHtml = `<img src="${item.url}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ${isSelected ? 'opacity-50' : ''}">`;
            } else {
                let icon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z'; // Default file
                let color = 'text-p-muted';
                
                if (item.extension === 'pdf') { icon = 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'; color = 'text-red-400'; }
                else if (['zip', 'rar'].includes(item.extension)) { icon = 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'; color = 'text-yellow-500'; }
                else if (['doc', 'docx'].includes(item.extension)) { icon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z'; color = 'text-blue-400'; }
                else if (['mp4', 'mov'].includes(item.extension)) { icon = 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'; color = 'text-purple-400'; }

                previewHtml = `
                    <div class="w-full h-full flex flex-col items-center justify-center gap-3 p-4 bg-white/5 group-hover:bg-white/10 transition-colors">
                        <svg class="w-12 h-12 ${color}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="${icon}"></path></svg>
                        <span class="text-[8px] font-black uppercase text-p-title tracking-widest bg-black/40 px-2 py-1 rounded-md border border-white/5 truncate w-full text-center">${item.extension}</span>
                    </div>
                `;
            }

            div.innerHTML = `
                ${previewHtml}
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                    <p class="text-[9px] font-black text-primary truncate uppercase tracking-widest mb-1">${item.name}</p>
                    <p class="text-[7px] text-p-muted font-bold uppercase">${item.date_folder} / ${item.table_folder}</p>
                </div>
                ${isSelected ? `
                    <div class="absolute top-2 right-2 bg-primary text-black p-1 rounded-full z-10">
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
            const fullUrl = (img.startsWith('http') ? img : '{{ $baseUrl }}' + img);
            
            let ext = '';
            try {
                const urlObj = new URL(fullUrl, window.location.origin);
                ext = urlObj.pathname.split('.').pop().toLowerCase();
            } catch (e) {
                ext = img.split('.').pop().split('?')[0].split('#')[0].toLowerCase();
            }
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
            
            let preview = '';
            if (isImage) {
                preview = `<img src="${fullUrl}" class="w-full h-full object-cover">`;
            } else {
                preview = `
                    <div class="w-full h-full flex flex-col items-center justify-center p-2 bg-white/5 border border-white/5 rounded-lg">
                        <svg class="w-6 h-6 text-primary/40 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"></path></svg>
                        <span class="text-[6px] font-black uppercase text-p-muted truncate w-full text-center">${img.split('/').pop()}</span>
                    </div>
                `;
            }

            html += `
                <div class="relative aspect-square rounded-lg overflow-hidden border border-white/10 group">
                    ${preview}
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
                    <span class="text-[9px] font-black uppercase tracking-widest">{!! addslashes(\App\Core\Lang::get('common.none')) !!}</span>
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

        dateContainer.innerHTML = `<button onclick="setDateFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === 'all' ? 'bg-primary/20 text-primary' : 'text-p-muted hover:bg-white/5'}">{!! addslashes(\App\Core\Lang::get('media.all')) !!}</button>`;
        dates.forEach(d => {
            dateContainer.innerHTML += `<button onclick="setDateFilter('${d}')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeDateFilter === d ? 'bg-primary/20 text-primary' : 'text-p-muted hover:bg-white/5'}">${d}</button>`;
        });

        tableContainer.innerHTML = `<button onclick="setTableFilter('all')" class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all ${activeTableFilter === 'all' ? 'bg-emerald-500/20 text-emerald-400' : 'text-p-muted hover:bg-white/5'}">{!! addslashes(\App\Core\Lang::get('media.all')) !!}</button>`;
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
        formData.append('db_id', '{{ $ctx['db_id'] }}');
        formData.append('_token', '{{ $csrf_token }}');

        const status = document.getElementById('gallery-status');
        status.innerText = 'Uploading: ' + file.name + '...';
        status.className = 'text-[10px] font-bold text-primary animate-pulse uppercase tracking-widest';

        fetch('{{ $baseUrl }}admin/media/upload', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.url) {
                    fetch('{{ $baseUrl }}admin/media/list')
                        .then(res => res.json())
                        .then(galleryData => {
                            allMediaData = galleryData;
                            renderFilters();
                            renderGrid();
                            selectMedia(data.url);
                        });
                } else {
                    showModal({
                        title: '{!! addslashes(\App\Core\Lang::get('media.upload_error')) !!}',
                        message: data.error || '{!! addslashes(\App\Core\Lang::get('media.upload_error_unknown')) !!}',
                        type: 'error'
                    });
                }
            })
            .catch(err => {
                showModal({
                    title: '{!! addslashes(\App\Core\Lang::get('common.network_failure')) !!}',
                    message: err.message,
                    type: 'error'
                });
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
        
        let ext = '';
        try {
            const urlObj = new URL(url, window.location.origin);
            const pathname = urlObj.pathname;
            ext = pathname.split('.').pop().toLowerCase();
        } catch (e) {
            ext = url.split('.').pop().split('?')[0].split('#')[0].toLowerCase();
        }
        
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);

        if (isImage) {
            if (img) {
                img.src = (url.startsWith('http') ? url : '{{ $baseUrl }}' + url);
                img.classList.remove('hidden');
            }
            const iconContainer = container.querySelector('.file-icon-preview');
            if (iconContainer) iconContainer.remove();
        } else {
            if (img) img.classList.add('hidden');
            let iconContainer = container.querySelector('.file-icon-preview');
            if (!iconContainer) {
                iconContainer = document.createElement('div');
                iconContainer.className = 'file-icon-preview w-full h-full flex flex-col items-center justify-center p-4 bg-black/20';
                container.appendChild(iconContainer);
            }
            iconContainer.innerHTML = `
                <svg class="w-16 h-16 text-primary/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"></path></svg>
                <span class="text-[10px] font-black uppercase text-p-muted">${ext}</span>
            `;
        }

        pathTxt.innerText = url;

        const fileInput = document.getElementById('file-' + fieldName);
        if (fileInput) fileInput.required = false;
    }

    function clearField(fieldName) {
        const container = document.getElementById('preview-container-' + fieldName);
        container.classList.add('hidden');
        const iconContainer = container.querySelector('.file-icon-preview');
        if (iconContainer) iconContainer.remove();
        
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
@endsection
