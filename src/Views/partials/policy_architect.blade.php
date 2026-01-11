<div id="granular-perms" class="{{ ($permissions['all'] ?? false) ? 'hidden' : '' }} space-y-10">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        @php
        $modules = [
            'databases' => [
                'label' => \App\Core\Lang::get('roles_form.databases_label'),
                'color' => 'emerald',
                'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                'actions' => [
                    'create_db' => \App\Core\Lang::get('roles_form.perm_create_db'),
                    'delete_db' => \App\Core\Lang::get('roles_form.perm_delete_db'),
                    'view_tables' => \App\Core\Lang::get('roles_form.perm_view_tables'),
                    'create_table' => \App\Core\Lang::get('roles_form.perm_create_table'),
                    'edit_table' => \App\Core\Lang::get('roles_form.perm_fields_config'),
                    'drop_table' => \App\Core\Lang::get('roles_form.perm_drop_table'),
                    'export_data' => \App\Core\Lang::get('roles_form.perm_export_data'),
                    'import_data' => \App\Core\Lang::get('roles_form.perm_import_data'),
                    'crud_read' => \App\Core\Lang::get('roles_form.perm_crud_read'),
                    'crud_create' => \App\Core\Lang::get('roles_form.perm_crud_create'),
                    'crud_update' => \App\Core\Lang::get('roles_form.perm_crud_update'),
                    'crud_delete' => \App\Core\Lang::get('roles_form.perm_crud_delete')
                ]
            ],
            'api' => [
                'label' => \App\Core\Lang::get('roles_form.api_label'),
                'color' => 'amber',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'actions' => [
                    'view_keys' => \App\Core\Lang::get('roles_form.perm_view_keys'),
                    'create_keys' => \App\Core\Lang::get('roles_form.perm_manage_keys'),
                    'revoke_keys' => \App\Core\Lang::get('roles_form.perm_manage_keys'),
                    'view_docs' => \App\Core\Lang::get('roles_form.perm_view_docs')
                ]
            ],
            'media' => [
                'label' => \App\Core\Lang::get('roles_form.media_label'),
                'color' => 'purple',
                'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                'actions' => [
                    'view_files' => \App\Core\Lang::get('roles_form.perm_view_files'),
                    'upload' => \App\Core\Lang::get('roles_form.perm_view_files'),
                    'edit_files' => \App\Core\Lang::get('roles_form.perm_view_files'),
                    'delete_files' => \App\Core\Lang::get('roles_form.perm_view_files')
                ]
            ],
            'users' => [
                'label' => \App\Core\Lang::get('roles_form.users_label'),
                'color' => 'blue',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'actions' => [
                    'view_users' => \App\Core\Lang::get('roles_form.perm_view_users'),
                    'invite_users' => \App\Core\Lang::get('roles_form.perm_invite_users'),
                    'edit_users' => \App\Core\Lang::get('roles_form.perm_edit_users'),
                    'delete_users' => \App\Core\Lang::get('roles_form.perm_delete_users'),
                    'manage_roles' => \App\Core\Lang::get('roles_form.perm_manage_roles')
                ]
            ]
        ];
        @endphp

        @foreach ($modules as $key => $mod)
            @php
            $modPerms = $permissions['modules'][$key] ?? [];
            $hasAccess = !empty($modPerms);
            @endphp
            <div class="glass-card group hover:border-{{ $mod['color'] }}-500/30 transition-all duration-300">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $mod['color'] }}-500/10 flex items-center justify-center text-{{ $mod['color'] }}-500 group-hover:scale-110 transition-transform duration-500 border border-{{ $mod['color'] }}-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $mod['icon'] }}"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-p-title uppercase italic tracking-tighter">
                            {{ $mod['label'] }}
                        </h3>
                        <p class="text-[10px] text-p-muted font-bold uppercase tracking-widest mt-1">
                            {{ \App\Core\Lang::get('roles_form.system_module') }}
                        </p>
                    </div>
                    <label class="ml-auto relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" onchange="toggleModule('{{ $key }}', this)" class="sr-only peer" {{ $hasAccess ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-white/5 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-p-muted after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-{{ $mod['color'] }}-500"></div>
                    </label>
                </div>

                <div id="actions-{{ $key }}" class="grid grid-cols-2 gap-3 {{ $hasAccess ? '' : 'opacity-40 pointer-events-none grayscale' }} transition-all duration-300">
                    @foreach ($mod['actions'] as $actKey => $actLabel)
                        @php
                        $isChecked = in_array($actKey, $modPerms);
                        @endphp
                        <label class="flex items-center gap-3 p-3 bg-black/20 rounded-xl hover:bg-white/5 transition-colors cursor-pointer border border-transparent hover:border-{{ $mod['color'] }}-500/20">
                            <input type="checkbox" name="modules[{{ $key }}][]" value="{{ $actKey }}" 
                                {{ $isChecked ? 'checked' : '' }} 
                                class="checkbox-custom text-{{ $mod['color'] }}-500 focus:ring-{{ $mod['color'] }}-500">
                            <span class="text-[10px] font-bold text-p-muted group-hover:text-{{ $mod['color'] }}-400 transition-colors uppercase tracking-tight">
                                {{ $actLabel }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>
</div>

@once
@section('scripts')
@parent
<script>
function toggleModule(key, checkbox) {
    const container = document.getElementById('actions-' + key);
    if(!container) return;
    const inputs = container.querySelectorAll('input[type="checkbox"]');
    
    if (checkbox.checked) {
        container.classList.remove('opacity-40', 'pointer-events-none', 'grayscale');
    } else {
        container.classList.add('opacity-40', 'pointer-events-none', 'grayscale');
        inputs.forEach(input => input.checked = false);
    }
}
</script>
@endsection
@endonce
