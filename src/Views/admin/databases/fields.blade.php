@extends('layouts.main')

@section('title', \App\Core\Lang::get('fields.title') . ' - ' . $table_name)

@section('content')
<header class="mb-12">
    <h1 class="text-4xl font-black text-p-title uppercase tracking-tighter">{{ \App\Core\Lang::get('fields.title') }}
    </h1>
    <p class="text-p-muted mt-2">
        {!! str_replace(':table', '<b class="text-primary">' . $table_name . '</b>', \App\Core\Lang::get('fields.subtitle')) !!}
    </p>
</header>

<div class="flex flex-col lg:flex-row gap-8 items-start">
    <!-- Left: Configurations -->
    <div class="w-full lg:flex-1">
        <section class="glass-card">
            <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-8 flex items-center gap-4">
                {{ \App\Core\Lang::get('fields.matrix') }} <span class="h-[1px] flex-1 bg-glass-border"></span>
            </h3>

            @foreach ($configFields as $field)
                <form action="{{ $baseUrl }}admin/databases/fields/update" method="POST"
                    class="bg-white/[0.02] border border-glass-border rounded-2xl p-6 mb-6 transition-all hover:bg-p-bg/50 dark:hover:bg-white/[0.04] hover:border-primary/20">
                    {!! $csrf_field !!}
                    <input type="hidden" name="config_id" value="{{ $field['id'] }}">

                    <div
                        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-6 pb-4 border-b border-white/5">
                        <div class="flex items-center gap-4">
                            <div
                                class="bg-primary/10 text-primary px-3 py-1 rounded-lg font-mono text-xs border border-primary/20">
                                {{ $field['data_type'] }}
                            </div>
                            <h4 class="text-xl font-bold text-p-title tracking-tight">
                                {{ $field['field_name'] }}
                            </h4>
                            @if (in_array($field['field_name'], ['id', 'created_at', 'updated_at', 'fecha_de_creacion', 'fecha_edicion']))
                                <span
                                    class="bg-white/10 text-[10px] text-p-muted px-2 py-0.5 rounded uppercase font-black tracking-widest">{{ \App\Core\Lang::get('fields.system_field') }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                class="btn-primary !py-1.5 !px-4 !text-[11px] uppercase tracking-wider">{{ \App\Core\Lang::get('fields.sync') }}</button>
                            @if (!in_array($field['field_name'], ['id', 'fecha_de_creacion', 'fecha_edicion']))
                                <button type="button" onclick="confirmDeleteField({{ $field['id'] }}, '{{ addslashes($field['field_name']) }}')" 
                                    class="text-red-500 hover:text-red-400 p-2 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-3">{{ \App\Core\Lang::get('fields.ui_rep') }}</label>
                            <select name="view_type" class="custom-select w-full" {{ in_array($field['field_name'], ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 'disabled' : '' }}>
                                @php $types = ['text', 'textarea', 'wysiwyg', 'image', 'gallery', 'file', 'boolean', 'datetime']; @endphp
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ $field['view_type'] == $type ? 'selected' : '' }}>
                                        {{ \App\Core\Lang::get('fields.types.' . $type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">{{ \App\Core\Lang::get('fields.constraints') }}</label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_required"
                                    class="w-4 h-4 rounded border-glass-border bg-p-bg dark:bg-black/40 text-primary focus:ring-primary/20 cursor-pointer"
                                    {{ ($field['is_required'] ?? false) ? 'checked' : '' }}>
                                <span
                                    class="text-[10px] font-bold text-p-muted group-hover:text-p-title transition-colors uppercase">{{ \App\Core\Lang::get('fields.required') }}</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_visible"
                                    class="w-4 h-4 rounded border-glass-border bg-p-bg dark:bg-black/40 text-primary focus:ring-primary/20 cursor-pointer"
                                    {{ ($field['is_visible'] ?? false) ? 'checked' : '' }}>
                                <span
                                    class="text-[10px] font-bold text-p-muted group-hover:text-p-title transition-colors uppercase">{{ \App\Core\Lang::get('fields.visible') }}</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_editable"
                                    class="w-4 h-4 rounded border-glass-border bg-p-bg dark:bg-black/40 text-primary focus:ring-primary/20 cursor-pointer"
                                    {{ ($field['is_editable'] ?? false) ? 'checked' : '' }}     {{ in_array($field['field_name'], ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 'disabled' : '' }}>
                                <span
                                    class="text-[10px] font-bold text-p-muted group-hover:text-p-title transition-colors uppercase">{{ \App\Core\Lang::get('fields.editable') }}</span>
                            </label>
                        </div>

                        <div
                            class="lg:col-span-2 bg-p-bg dark:bg-black/20 p-4 rounded-xl border border-p-border dark:border-white/5">
                            <label class="flex items-center gap-2 mb-4 cursor-pointer group">
                                <input type="checkbox" name="is_foreign_key"
                                    class="w-4 h-4 rounded border-glass-border bg-p-bg dark:bg-black/40 text-primary focus:ring-primary/20 cursor-pointer"
                                    {{ ($field['is_foreign_key'] ?? false) ? 'checked' : '' }}>
                                <span
                                    class="text-[10px] font-black text-primary uppercase tracking-widest">{{ \App\Core\Lang::get('fields.fk') }}</span>
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-[9px] font-bold text-p-muted uppercase mb-2">{{ \App\Core\Lang::get('fields.target_table') }}</label>
                                    <select name="related_table" class="custom-select w-full !py-1 text-xs">
                                        <option value="">{{ \App\Core\Lang::get('fields.select_table') }}</option>
                                        @foreach ($allTables as $t)
                                            <option value="{{ $t }}" {{ $field['related_table'] == $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[9px] font-bold text-p-muted uppercase mb-2">{{ \App\Core\Lang::get('fields.display_field') }}</label>
                                    <input type="text" name="related_field"
                                        value="{{ $field['related_field'] ?? '' }}"
                                        placeholder="{{ \App\Core\Lang::get('fields.fk_placeholder') }}"
                                        class="w-full bg-p-bg dark:bg-black/40 border border-glass-border rounded-lg px-2 py-1 text-xs text-p-title focus:outline-none focus:border-primary/50">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endforeach
        </section>
    </div>

    <!-- Right: Add Field -->
    <aside class="w-full lg:w-[400px] sticky top-24">
        <section class="glass-card border-t-4 border-t-primary shadow-2xl">
            <h3 class="text-lg font-bold text-p-title mb-8 border-b border-glass-border pb-4">
                {{ \App\Core\Lang::get('fields.inject') }}
            </h3>
            <form action="{{ $baseUrl }}admin/databases/fields/add" method="POST" class="space-y-6">
                {!! $csrf_field !!}
                <input type="hidden" name="db_id" value="{{ $database['id'] }}">
                <input type="hidden" name="table_name" value="{{ $table_name }}">

                <div>
                    <label
                        class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] mb-3">{{ \App\Core\Lang::get('fields.sql_id') }}</label>
                    <input type="text" name="field_name"
                        placeholder="{{ \App\Core\Lang::get('fields.sql_id_placeholder') }}" required
                        class="w-full bg-p-bg dark:bg-black/40 border border-glass-border rounded-xl px-4 py-3 text-p-title focus:border-primary/50 transition-all font-mono text-sm">
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] mb-3">{{ \App\Core\Lang::get('fields.data_type') }}</label>
                    <select name="data_type" class="custom-select w-full !py-3">
                        <option value="INTEGER">
                            {{ \App\Core\Lang::get('fields.sql_types.int') }}
                        </option>
                        <option value="TEXT" selected>
                            {{ \App\Core\Lang::get('fields.sql_types.text') }}
                        </option>
                        <option value="REAL">
                            {{ \App\Core\Lang::get('fields.sql_types.real') }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] mb-3">{{ \App\Core\Lang::get('fields.ui_comp') }}</label>
                    <select name="view_type" class="custom-select w-full !py-3">
                        @php $allTypes = ['text', 'boolean', 'textarea', 'wysiwyg', 'image', 'gallery', 'file']; @endphp
                        @foreach($allTypes as $type)
                            <option value="{{ $type }}">{{ \App\Core\Lang::get('fields.types.' . $type) }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                    class="btn-primary w-full !py-4 shadow-lg shadow-primary/20">{{ \App\Core\Lang::get('fields.commit') }}</button>
            </form>
        </section>
    </aside>
</div>
@endsection

@section('scripts')
<script>
    function confirmDeleteField(id, name) {
        showModal({
            type: 'confirm',
            title: '{!! addslashes(\App\Core\Lang::get('fields.delete_confirm_title')) !!}',
            message: '{!! addslashes(\App\Core\Lang::get('fields.delete_confirm_msg', ['name' => ':name'])) !!}'.replace(':name', name),
            confirmText: '{!! addslashes(\App\Core\Lang::get('fields.delete_confirm_btn')) !!}',
            dismissText: '{!! addslashes(\App\Core\Lang::get('common.dismiss')) !!}',
            onConfirm: function() { 
                window.location.href = '{{ $baseUrl }}admin/databases/fields/delete?config_id=' + id; 
            }
        });
    }
</script>
@endsection
