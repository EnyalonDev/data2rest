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

                                @case('password')
                                    <div class="relative group">
                                        <input type="password" name="{{ $field['field_name'] }}" id="pass-{{ $field['field_name'] }}"
                                            value="{{ $val }}" {{ $field['is_required'] ? 'required' : '' }}
                                            class="form-input pr-12" data-type="{{ $field['data_type'] }}">
                                        <button type="button" onclick="togglePassword('pass-{{ $field['field_name'] }}', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-p-muted hover:text-primary transition-colors focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
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
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6 mb-4 lg:mb-8 border-b border-white/5 pb-4 lg:pb-6">
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div>
                    <h2 class="text-xl lg:text-3xl font-black text-p-title italic tracking-tighter leading-none">
                        {{ \App\Core\Lang::get('media.explorer') }}
                    </h2>
                    <p class="text-[8px] lg:text-[10px] text-p-muted font-bold uppercase tracking-[0.2em] mt-1 lg:mt-2 hidden lg:block">
                        {{ \App\Core\Lang::get('media.system') }}
                    </p>
                </div>
                <!-- Mobile Close Button -->
                <button onclick="closeMediaGallery()"
                    class="lg:hidden text-p-muted hover:text-p-title transition-colors bg-white/5 p-2 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="flex items-center gap-2 lg:gap-3 w-full lg:flex-1 lg:max-w-2xl">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <input type="text" id="media-search" placeholder="{{ \App\Core\Lang::get('media.search') }}"
                        oninput="handleSearch(this.value)" 
                        class="w-full pl-9 pr-4 py-2 bg-p-input border border-glass-border rounded-xl text-xs text-p-title placeholder:text-p-muted transition-all focus:border-primary/50">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-p-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                </div>
                
                <!-- Type Filters (Hidden icons on very small) -->
                <div class="flex bg-black/40 p-1 rounded-xl border border-glass-border shrink-0">
                    <button onclick="setTypeFilter('all')" id="header-type-filter-all" class="header-type-filter-btn px-2 lg:px-3 py-1.5 rounded-lg text-[8px] lg:text-[9px] font-black uppercase tracking-widest transition-all bg-primary/20 text-primary">
                        {{ \App\Core\Lang::get('media.all') }}
                    </button>
                    <button onclick="setTypeFilter('images')" id="header-type-filter-images" class="header-type-filter-btn px-2 lg:px-3 py-1.5 rounded-lg text-[8px] lg:text-[9px] font-black uppercase tracking-widest transition-all text-p-muted hover:text-p-title">
                        <span class="hidden sm:inline">{{ \App\Core\Lang::get('media.only_images') }}</span>
                        <span class="sm:hidden">IMG</span>
                    </button>
                    <button onclick="setTypeFilter('files')" id="header-type-filter-files" class="header-type-filter-btn px-2 lg:px-3 py-1.5 rounded-lg text-[8px] lg:text-[9px] font-black uppercase tracking-widest transition-all text-p-muted hover:text-p-title">
                        <span class="hidden sm:inline">{{ \App\Core\Lang::get('media.only_files') }}</span>
                        <span class="sm:hidden">DOC</span>
                    </button>
                </div>

                <!-- Desktop Close Button -->
                <button onclick="closeMediaGallery()"
                    class="hidden lg:block text-p-muted hover:text-p-title transition-colors bg-white/5 p-2 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col lg:flex-row gap-4 lg:gap-8 overflow-hidden">
            <!-- Mobile Folder Button (Floating or prominent) -->
            <div class="flex lg:hidden gap-2">
                <button onclick="promptCreateFolder()" class="flex-1 flex items-center justify-center gap-2 py-3 bg-emerald-500/10 border border-emerald-200/20 text-emerald-400 rounded-xl font-black uppercase tracking-widest text-[10px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    </svg>
                    {{ \App\Core\Lang::get('media.new_folder') }}
                </button>
                <label class="flex-1 flex items-center justify-center gap-2 py-3 bg-primary/10 border border-primary/20 text-primary rounded-xl font-black uppercase tracking-widest text-[10px] cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    {{ \App\Core\Lang::get('media.upload') }}
                    <input type="file" class="hidden" onchange="handleDirectUpload(this.files[0])" accept="image/*,video/*,application/pdf,.zip,.rar,.doc,.docx,.txt">
                </label>
            </div>

            <!-- Sidebar Filters -->
            <aside class="hidden lg:flex w-64 flex-col gap-6 overflow-y-auto pr-4 custom-scrollbar border-r border-white/5">
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

                <!-- Sidebar Filters Hidden for Type (Moved to Header) -->
                <div class="hidden">
                    <h4 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary/40"></span>
                        {{ \App\Core\Lang::get('media.resource_type') }}
                    </h4>
                    <div id="filter-types" class="space-y-1">
                        <button onclick="setTypeFilter('all')" id="type-filter-all" class="type-filter-btn active w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all bg-primary/20 text-primary">{{ \App\Core\Lang::get('media.all_resources') }}</button>
                        <button onclick="setTypeFilter('images')" id="type-filter-images" class="type-filter-btn w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all text-p-muted hover:bg-white/5">{{ \App\Core\Lang::get('media.only_images') }}</button>
                        <button onclick="setTypeFilter('files')" id="type-filter-files" class="type-filter-btn w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase transition-all text-p-muted hover:bg-white/5">{{ \App\Core\Lang::get('media.only_files') }}</button>
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
                            {{ \App\Core\Lang::get('media.drop') }}
                        </h3>
                        <p class="text-[10px] text-primary font-black uppercase tracking-widest mt-2">Uploading to
                            Neural Network</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 lg:mt-8 pt-4 lg:pt-6 border-t border-glass-border flex flex-col sm:flex-row justify-between items-center gap-4">
            <span id="gallery-status"
                class="text-[9px] lg:text-[10px] font-bold text-p-muted uppercase tracking-widest order-2 sm:order-1">{{ \App\Core\Lang::get('media.scanning') }}</span>
            <div class="flex gap-2 lg:gap-4 w-full sm:w-auto order-1 sm:order-2">
                <button onclick="closeMediaGallery()"
                    class="btn-outline flex-1 sm:flex-none !py-2.5 !px-4 text-[9px] lg:text-[10px]">{{ \App\Core\Lang::get('media.abort_selection') }}</button>
                <button id="gallery-done-btn" onclick="closeMediaGallery()"
                    class="btn-primary flex-1 sm:flex-none !py-2.5 !px-8 text-[9px] lg:text-[10px] hidden">{{ \App\Core\Lang::get('common.commit') }}</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* In-line fix for Grid overlap */
    #mediaGrid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        grid-auto-rows: min-content !important;
        gap: 0.75rem !important;
        align-content: start !important;
    }
    @media (max-width: 1024px) {
        #mediaModal aside {
            display: none !important;
        }
        #mediaModal .glass-card {
            padding: 1.25rem !important;
            height: 95vh !important;
        }
    }
    @media (min-width: 1024px) {
        #mediaGrid {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
        }
    }
    #mediaGrid .media-card {
        aspect-ratio: 1/1 !important;
        width: 100% !important;
        height: auto !important;
        position: relative !important;
        display: block !important;
        overflow: hidden !important;
        cursor: pointer !important;
        border-radius: 1rem !important;
        background: #0f172a !important;
        border: 2px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        margin: 0 !important;
    }
    #mediaGrid .media-card:hover {
        border-color: rgba(255, 255, 255, 0.2) !important;
        transform: translateY(-2px) !important;
    }
    #mediaGrid .media-card.selected {
        border-color: var(--p-primary) !important;
        box-shadow: 0 0 30px rgba(var(--p-primary-rgb), 0.4) !important;
    }
    #mediaGrid .media-card img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
    }
    #mediaGrid .media-card .info-overlay {
        position: absolute !important;
        inset: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-end !important;
        padding: 1rem !important;
        background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 50%, transparent 100%) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        pointer-events: none !important;
    }
    #mediaGrid .media-card:hover .info-overlay {
        opacity: 1 !important;
    }
</style>
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
        
        // Update Header UI classes
        document.querySelectorAll('.header-type-filter-btn').forEach(btn => {
            btn.classList.remove('bg-primary/20', 'text-primary');
            btn.classList.add('text-p-muted');
        });
        
        const activeBtn = document.getElementById('header-type-filter-' + type);
        if (activeBtn) {
            activeBtn.classList.add('bg-primary/20', 'text-primary');
            activeBtn.classList.remove('text-p-muted');
        }
        
        renderGrid();
    }

    function promptCreateFolder() {
        const name = prompt('{{ \App\Core\Lang::get("media.folder_name_placeholder") }}');
        if (!name) return;

        const formData = new FormData();
        formData.append('name', name);
        formData.append('_token', '{{ $csrf_token }}');

        fetch('{{ $baseUrl }}admin/media/api/create-folder', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                openMediaGallery(currentTargetField, isMultiMode); // Refresh
            } else {
                alert(data.error || 'Error creating folder');
            }
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
        
        // Filter by Type
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
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
            const isImage = imageExtensions.includes(item.extension);
            const div = document.createElement('div');
            
            div.className = `media-card ${isSelected ? 'selected' : ''}`;
            div.onclick = () => selectMedia(item.url);

            let previewHtml = '';
            if (isImage) {
                previewHtml = `<img src="${item.url}">`;
            } else {
                let icon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z';
                let color = 'text-slate-500';
                if (item.extension === 'pdf') { icon = 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'; color = 'text-red-400'; }
                previewHtml = `
                    <div class="w-full h-full flex flex-col items-center justify-center gap-2 bg-white/5">
                        <svg class="w-10 h-10 ${color}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="${icon}"></path></svg>
                        <span class="text-[8px] font-black uppercase text-p-muted tracking-widest">${item.extension}</span>
                    </div>
                `;
            }

            div.innerHTML = `
                ${previewHtml}
                <div class="info-overlay">
                    <p class="text-[10px] font-bold text-white truncate mb-0.5">${item.name}</p>
                    <p class="text-[8px] text-p-muted font-bold uppercase tracking-tighter">${item.table_folder}</p>
                </div>
                <!-- Selection Indicator -->
                <div class="absolute top-3 right-3 transition-transform ${isSelected ? 'scale-100' : 'scale-0'}">
                    <div class="bg-primary text-dark p-1 rounded-full shadow-lg">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                </div>
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
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'].includes(ext);
            
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
        
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'].includes(ext);

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
