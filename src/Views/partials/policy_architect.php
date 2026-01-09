<div id="granular-perms" class="<?php echo ($permissions['all'] ?? false) ? 'hidden' : ''; ?> space-y-10">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <?php
        $modules = [
            'databases' => [
                'label' => 'Centro de Datos',
                'color' => 'emerald',
                'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                'actions' => [
                    'create_db' => 'Crear Base de Datos',
                    'delete_db' => 'Eliminar Base de Datos',
                    'view_tables' => 'Ver Tablas',
                    'create_table' => 'Crear Tablas',
                    'edit_table' => 'Editar Estructura',
                    'drop_table' => 'Borrar Tablas',
                    'crud_read' => 'Leer Registros',
                    'crud_create' => 'Insertar Registros',
                    'crud_update' => 'Actualizar Registros',
                    'crud_delete' => 'Eliminar Registros'
                ]
            ],
            'api' => [
                'label' => 'API Gateway',
                'color' => 'amber',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'actions' => [
                    'view_keys' => 'Ver Llaves API',
                    'create_keys' => 'Crear Llaves',
                    'revoke_keys' => 'Revocar Llaves',
                    'view_docs' => 'Ver Documentación'
                ]
            ],
            'media' => [
                'label' => 'Biblioteca de Medios',
                'color' => 'purple',
                'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                'actions' => [
                    'view_files' => 'Ver Archivos',
                    'upload' => 'Subir Archivos',
                    'edit_files' => 'Editar Imágenes',
                    'delete_files' => 'Borrar Archivos'
                ]
            ],
            'users' => [
                'label' => 'Gestión de Equipos',
                'color' => 'blue',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'actions' => [
                    'view_users' => 'Ver Miembros',
                    'invite_users' => 'Invitar Usuarios',
                    'edit_users' => 'Editar Perfiles',
                    'delete_users' => 'Eliminar Usuarios',
                    'manage_roles' => 'Gestionar Roles'
                ]
            ]
        ];

        foreach ($modules as $key => $mod): 
            $modPerms = $permissions['modules'][$key] ?? [];
            $hasAccess = !empty($modPerms);
        ?>
        <div class="glass-card group hover:border-<?php echo $mod['color']; ?>-500/30 transition-all duration-300">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-<?php echo $mod['color']; ?>-500/10 flex items-center justify-center text-<?php echo $mod['color']; ?>-500 group-hover:scale-110 transition-transform duration-500 border border-<?php echo $mod['color']; ?>-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo $mod['icon']; ?>"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-p-title uppercase italic tracking-tighter">
                        <?php echo $mod['label']; ?>
                    </h3>
                    <p class="text-[10px] text-p-muted font-bold uppercase tracking-widest mt-1">
                        Módulo del Sistema
                    </p>
                </div>
                <label class="ml-auto relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" onchange="toggleModule('<?php echo $key; ?>', this)" class="sr-only peer" <?php echo $hasAccess ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/5 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-p-muted after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-<?php echo $mod['color']; ?>-500"></div>
                </label>
            </div>

            <div id="actions-<?php echo $key; ?>" class="grid grid-cols-2 gap-3 <?php echo $hasAccess ? '' : 'opacity-40 pointer-events-none grayscale'; ?> transition-all duration-300">
                <?php foreach ($mod['actions'] as $actKey => $actLabel): 
                    $isChecked = in_array($actKey, $modPerms);
                ?>
                <label class="flex items-center gap-3 p-3 bg-black/20 rounded-xl hover:bg-white/5 transition-colors cursor-pointer border border-transparent hover:border-<?php echo $mod['color']; ?>-500/20">
                    <input type="checkbox" name="modules[<?php echo $key; ?>][]" value="<?php echo $actKey; ?>" 
                        <?php echo $isChecked ? 'checked' : ''; ?> 
                        class="checkbox-custom text-<?php echo $mod['color']; ?>-500 focus:ring-<?php echo $mod['color']; ?>-500">
                    <span class="text-[10px] font-bold text-p-muted group-hover:text-<?php echo $mod['color']; ?>-400 transition-colors uppercase tracking-tight">
                        <?php echo $actLabel; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<script>
function toggleModule(key, checkbox) {
    const container = document.getElementById('actions-' + key);
    const inputs = container.querySelectorAll('input[type="checkbox"]');
    
    if (checkbox.checked) {
        container.classList.remove('opacity-40', 'pointer-events-none', 'grayscale');
        // Auto-select first permission (usually 'view') as a convenience? 
        // No, let user decide.
    } else {
        container.classList.add('opacity-40', 'pointer-events-none', 'grayscale');
        inputs.forEach(input => input.checked = false);
    }
}
</script>
