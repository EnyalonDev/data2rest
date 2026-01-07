<header class="mb-12">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">Policy <span
            class="text-primary">Architect</span></h1>
    <p class="text-p-muted font-medium">Define high-granularity permissions for system nodes.</p>
</header>

<form action="<?php echo $baseUrl; ?>admin/roles/save" method="POST" class="space-y-10">
    <input type="hidden" name="id" value="<?php echo $role['id'] ?? ''; ?>">

    <section class="glass-card">
        <div class="grid md:grid-cols-2 gap-8 items-end">
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-3">Policy
                    Title</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($role['name'] ?? ''); ?>"
                    placeholder="e.g. Content Editor" required class="form-input">
            </div>
            <div class="pb-3">
                <label
                    class="flex items-center gap-4 cursor-pointer p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <input type="checkbox" name="is_admin" value="1" onchange="toggleMaster()" <?php echo ($role['permissions']['all'] ?? false) ? 'checked' : ''; ?> id="master-toggle"
                        class="checkbox-custom">
                    <span class="text-xs font-black uppercase tracking-widest text-primary">Master Administrator (Full
                        Access)</span>
                </label>
            </div>
        </div>
    </section>

    <div id="granular-perms" class="<?php echo ($role['permissions']['all'] ?? false) ? 'hidden' : ''; ?> space-y-10">
        <!-- Module Permissions -->
        <section class="glass-card">
            <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-primary translate-y-[-1px]"></span>
                Global Module Access</h3>
            <div class="space-y-6">
                <div class="perm-grid">
                    <label class="perm-item">
                        <input type="checkbox" name="modules[databases][]" value="view" <?php echo in_array('view', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">Databases Hub</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[databases][]" value="create" <?php echo in_array('create', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">Create Database</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[databases][]" value="delete" <?php echo in_array('delete', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">Delete Database</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[api][]" value="view_keys" <?php echo in_array('view_keys', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                        <span class="text-[11px] font-bold">View API Keys</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[api][]" value="manage_keys" <?php echo in_array('manage_keys', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">Manage Keys</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[api][]" value="view_docs" <?php echo in_array('view_docs', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                        <span class="text-[11px] font-bold">View API Docs</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[users][]" value="view" <?php echo in_array('view', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">View Team</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[users][]" value="manage" <?php echo in_array('manage', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?>
                            class="checkbox-custom">
                        <span class="text-[11px] font-bold">Manage Users</span>
                    </label>
                    <label class="perm-item">
                        <input type="checkbox" name="modules[users][]" value="manage_roles" <?php echo in_array('manage_roles', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                        <span class="text-[11px] font-bold">Manage Roles</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Database Specific Permissions -->
        <section class="glass-card">
            <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 translate-y-[-1px]"></span>
                Node-Specific Granularity (Table & Data Control)</h3>
            <div class="space-y-6">
                <?php foreach ($databases as $db):
                    $dbPerms = $role['permissions']['databases'][$db['id']] ?? [];
                    ?>
                    <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-xs font-black">
                                #<?php echo $db['id']; ?></div>
                            <h4 class="text-sm font-black text-p-title uppercase"><?php echo htmlspecialchars($db['name']); ?>
                            </h4>
                            <label
                                class="flex items-center gap-2 cursor-pointer ml-auto bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                                <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]" value="view" <?php echo in_array('view', $dbPerms) ? 'checked' : ''; ?> class="checkbox-custom !w-4 !h-4">
                                <span class="text-[9px] font-black uppercase text-emerald-400">ENABLE ACCESS</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Table Management -->
                            <div class="space-y-3">
                                <p class="text-[9px] font-black text-p-muted uppercase tracking-widest pl-1">Structural
                                    Permissions</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php
                                    $struct = ['view_tables' => 'See Tables', 'create_table' => 'Create Table', 'delete_table' => 'Drop Table', 'manage_fields' => 'Fields Config'];
                                    foreach ($struct as $k => $v): ?>
                                        <label
                                            class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all cursor-pointer">
                                            <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]"
                                                value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?>
                                                class="checkbox-custom">
                                            <span class="text-[10px] font-bold text-slate-300"><?php echo $v; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Data Management -->
                            <div class="space-y-3">
                                <p class="text-[9px] font-black text-p-muted uppercase tracking-widest pl-1">Content
                                    Permissions (CRUD)</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php
                                    $content = ['crud_view' => 'Read Data', 'crud_create' => 'Insert Data', 'crud_edit' => 'Update Data', 'crud_delete' => 'Delete Data'];
                                    foreach ($content as $k => $v): ?>
                                        <label
                                            class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all cursor-pointer">
                                            <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]"
                                                value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?>
                                                class="checkbox-custom">
                                            <span class="text-[10px] font-bold text-slate-300"><?php echo $v; ?></span>
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
        <a href="<?php echo $baseUrl; ?>admin/roles" class="btn-primary !bg-slate-800 !text-slate-300">ABORT</a>
        <button type="submit" class="btn-primary">Save Access Policy</button>
    </div>
</form>

<script>
    function toggleMaster() {
        const isMaster = document.getElementById('master-toggle').checked;
        document.getElementById('granular-perms').classList.toggle('hidden', isMaster);
    }
</script>