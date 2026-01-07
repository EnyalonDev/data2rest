<?php use App\Core\Lang; ?>
<header class="mb-12 flex justify-between items-end">
    <div>
        <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
            <?php echo Lang::get('common.groups'); ?>
        </h1>
        <p class="text-p-muted font-medium">Manage user groups and organization hierarchy.</p>
    </div>
    <a href="<?php echo $baseUrl; ?>admin/groups/new" class="btn-primary">
        NEW GROUP +
    </a>
</header>

<section class="glass-card !p-0 overflow-hidden shadow-2xl">
    <div class="px-8 py-5 bg-white/[0.03] border-b border-glass-border flex justify-between items-center">
        <h3 class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
            Registered Groups
        </h3>
        <span
            class="text-[10px] font-black bg-primary/10 text-primary px-3 py-1 rounded-full border border-primary/20 tracking-widest">
            <?php echo count($groups); ?> GROUPS
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="bg-black/5 dark:bg-black/40 text-[10px] font-black text-p-muted uppercase tracking-widest border-b border-p-border">
                    <th class="px-8 py-5">Group Name</th>
                    <th class="px-8 py-5">Description</th>
                    <th class="px-8 py-5">Members</th>
                    <th class="px-8 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                <?php foreach ($groups as $group): ?>
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-8 py-6">
                            <div class="font-bold text-p-title text-sm">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-xs text-p-muted max-w-sm truncate">
                                <?php echo htmlspecialchars($group['description']); ?>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-8 h-8 rounded-full bg-white/5 border border-glass-border flex items-center justify-center text-[10px] font-black text-primary">
                                    <?php echo $group['user_count']; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="<?php echo $baseUrl; ?>admin/groups/edit?id=<?php echo $group['id']; ?>"
                                    class="text-p-muted hover:text-primary p-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </a>
                                <button
                                    onclick="confirmDelete(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')"
                                    class="text-p-muted hover:text-red-500 p-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center opacity-30">
                            <p class="text-xs font-black uppercase tracking-widest">No groups found</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    function confirmDelete(id, name) {
        showModal({
            title: 'Delete Group',
            message: `Are you sure you want to delete the group "${name}"? Users in this group will be detached.`,
            type: 'confirm',
            typeLabel: 'DELETE GROUP',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/groups/delete?id=${id}`;
            }
        });
    }
</script>