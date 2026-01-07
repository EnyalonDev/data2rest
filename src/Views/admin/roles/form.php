<header class="mb-12">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
        <?php echo \App\Core\Lang::get('roles_form.policy_architect'); ?></h1>
    <p class="text-p-muted font-medium"><?php echo \App\Core\Lang::get('roles_form.policy_subtitle'); ?></p>
</header>

<form action="<?php echo $baseUrl; ?>admin/roles/save" method="POST" class="space-y-10">
    <input type="hidden" name="id" value="<?php echo $role['id'] ?? ''; ?>">

    <section class="glass-card">
        <div class="grid md:grid-cols-2 gap-8 items-end">
            <div>
                <label
                    class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-3"><?php echo \App\Core\Lang::get('roles_form.policy_title'); ?></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($role['name'] ?? ''); ?>"
                    placeholder="e.g. Content Editor" required class="form-input">
            </div>
            <div class="pb-3">
                <label
                    class="flex items-center gap-4 cursor-pointer p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <input type="checkbox" name="is_admin" value="1" onchange="toggleMaster()" <?php echo ($role['permissions']['all'] ?? false) ? 'checked' : ''; ?> id="master-toggle"
                        class="checkbox-custom">
                    <span
                        class="text-xs font-black uppercase tracking-widest text-primary"><?php echo \App\Core\Lang::get('roles_form.master_admin'); ?></span>
                </label>
            </div>
        </div>
    </section>

    <div id="granular-perms" class="<?php echo ($role['permissions']['all'] ?? false) ? 'hidden' : ''; ?> space-y-10">
        <!-- Module Permissions -->
        <section class="glass-card">
            <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-primary translate-y-[-1px]"></span>
                <?php echo \App\Core\Lang::get('roles_form.global_module_access'); ?></h3>
            <div class="space-y-6">
                <div class="perm-grid">
                    <?php
                    $globalPerms = [
                        'databases' => ['view' => 'perm_view_db_hub', 'create' => 'perm_create_db', 'delete' => 'perm_delete_db'],
                        'api' => ['view_keys' => 'perm_view_keys', 'manage_keys' => 'perm_manage_keys', 'view_docs' => 'perm_view_docs'],
                        'users' => ['view' => 'perm_view_users', 'manage' => 'perm_manage_users', 'manage_roles' => 'perm_manage_roles']
                    ];
                    foreach ($globalPerms as $module => $actions) {
                        foreach ($actions as $action => $labelKey) {
                            $isChecked = in_array($action, $role['permissions']['modules'][$module] ?? []);
                            ?>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[<?php echo $module; ?>][]" value="<?php echo $action; ?>"
                                    <?php echo $isChecked ? 'checked' : ''; ?> class="checkbox-custom">
                                <span
                                    class="text-[11px] font-bold"><?php echo \App\Core\Lang::get('roles_form.' . $labelKey); ?></span>
                            </label>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Database Specific Permissions -->
        <section class="glass-card">
            <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 translate-y-[-1px]"></span>
                <?php echo \App\Core\Lang::get('roles_form.node_granularity'); ?></h3>
            <div class="space-y-6">
                <?php foreach ($databases as $db):
                    $dbPerms = $role['permissions']['databases'][$db['id']] ?? [];
                    ?>
                    <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-xs font-black">
                                #<?php echo $db['id']; ?></div>
                            <h4 class="text-sm font-black text-p-title uppercase">
                                <?php echo htmlspecialchars($db['name']); ?>
                            </h4>
                            <label
                                class="flex items-center gap-2 cursor-pointer ml-auto bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                                <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]" value="view" <?php echo in_array('view', $dbPerms) ? 'checked' : ''; ?> class="checkbox-custom !w-4 !h-4">
                                <span
                                    class="text-[9px] font-black uppercase text-emerald-400"><?php echo \App\Core\Lang::get('roles_form.enable_access'); ?></span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Table Management -->
                            <div class="space-y-3">
                                <p class="text-[9px] font-black text-p-muted uppercase tracking-widest pl-1">
                                    <?php echo \App\Core\Lang::get('roles_form.structural_permissions'); ?></p>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php
                                    $struct = [
                                        'view_tables' => 'perm_view_tables',
                                        'create_table' => 'perm_create_table',
                                        'delete_table' => 'perm_drop_table',
                                        'manage_fields' => 'perm_fields_config'
                                    ];
                                    foreach ($struct as $k => $langKey): ?>
                                        <label
                                            class="flex items-center gap-3 p-3 bg-p-bg dark:bg-white/5 rounded-xl hover:bg-p-border dark:hover:bg-white/10 transition-all cursor-pointer">
                                            <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]"
                                                value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?>
                                                class="checkbox-custom">
                                            <span
                                                class="text-[10px] font-bold text-p-muted dark:text-slate-300"><?php echo \App\Core\Lang::get('roles_form.' . $langKey); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Data Management -->
                            <div class="space-y-3">
                                <p class="text-[9px] font-black text-p-muted uppercase tracking-widest pl-1">
                                    <?php echo \App\Core\Lang::get('roles_form.content_permissions'); ?></p>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php
                                    $content = [
                                        'crud_view' => 'perm_read_data',
                                        'crud_create' => 'perm_insert_data',
                                        'crud_edit' => 'perm_update_data',
                                        'crud_delete' => 'perm_delete_data'
                                    ];
                                    foreach ($content as $k => $langKey): ?>
                                        <label
                                            class="flex items-center gap-3 p-3 bg-p-bg dark:bg-white/5 rounded-xl hover:bg-p-border dark:hover:bg-white/10 transition-all cursor-pointer">
                                            <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]"
                                                value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?>
                                                class="checkbox-custom">
                                            <span
                                                class="text-[10px] font-bold text-p-muted dark:text-slate-300"><?php echo \App\Core\Lang::get('roles_form.' . $langKey); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="flex justify-center gap-4">
        <a href="<?php echo $baseUrl; ?>admin/roles"
            class="btn-primary !bg-slate-800 !text-slate-300"><?php echo \App\Core\Lang::get('common.cancel'); ?></a>
        <button type="submit" class="btn-primary"><?php echo \App\Core\Lang::get('common.save_changes'); ?></button>
    </div>
</form>

<script>
    function toggleMaster() {
        const isMaster = document.getElementById('master-toggle').checked;
        document.getElementById('granular-perms').classList.toggle('hidden', isMaster);
    }
</script>