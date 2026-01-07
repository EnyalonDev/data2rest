<?php use App\Core\Lang; ?>
<header class="mb-12 text-center">
    <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
        <?php echo $id ? 'Edit' : 'New'; ?> <span class="text-primary italic">Group</span>
    </h1>
    <p class="text-p-muted font-medium">Define a new user organization unit.</p>
</header>

<section class="max-w-2xl mx-auto">
    <form action="<?php echo $baseUrl; ?>admin/groups/save" method="POST" class="glass-card space-y-8">
        <input type="hidden" name="id" value="<?php echo $group['id'] ?? ''; ?>">

        <div>
            <label class="form-label">Group Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>" required
                placeholder="e.g. Sales Team" class="form-input">
        </div>

        <div>
            <label class="form-label">Description</label>
            <textarea name="description" rows="4" placeholder="Optional description of this group..."
                class="form-input"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
        </div>

        <div class="pt-6">
            <h3 class="text-sm font-black text-p-title uppercase mb-4">Group Permissions</h3>
            <?php
            $permissions = $group['permissions'] ?? [];
            include __DIR__ . '/../../partials/policy_architect.php';
            ?>
        </div>

        <div class="pt-8 border-t border-glass-border flex justify-end gap-6">
            <a href="<?php echo $baseUrl; ?>admin/groups" class="btn-outline">
                <?php echo Lang::get('common.cancel'); ?>
            </a>
            <button type="submit" class="btn-primary">
                <?php echo Lang::get('common.save'); ?>
            </button>
        </div>
    </form>
</section>