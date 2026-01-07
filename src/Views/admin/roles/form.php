<header class="mb-12">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
        <?php echo \App\Core\Lang::get('roles_form.policy_architect'); ?>
    </h1>
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

    <?php
    $permissions = $role['permissions'] ?? [];
    include __DIR__ . '/../../partials/policy_architect.php';
    ?>

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