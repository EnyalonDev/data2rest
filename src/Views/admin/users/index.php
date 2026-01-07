<header class="mb-12 flex flex-col md:flex-row justify-between items-end gap-6">
    <div>
        <h1 class="text-5xl font-black text-white italic tracking-tighter mb-2">Team <span
                class="text-primary">Management</span></h1>
        <p class="text-slate-500 font-medium">Assign access policies to collaborators and manage their connection
            status.</p>
    </div>
    <div class="flex gap-4">
        <a href="<?php echo $baseUrl; ?>admin/roles" class="btn-primary !bg-slate-800 !text-slate-300 !py-2">ACCESS
            POLICIES</a>
        <a href="<?php echo $baseUrl; ?>admin/users/new" class="btn-primary !py-2">CREATE USER +</a>
    </div>
</header>

<section class="glass-card overflow-hidden !p-0 shadow-2xl">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-white/5 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <th class="px-8 py-5">User Identity</th>
                <th class="px-8 py-5">Assigned Policy (Role)</th>
                <th class="px-8 py-5">Status</th>
                <th class="px-8 py-5 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.03]">
            <?php foreach ($users as $u): ?>
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-blue-600 flex items-center justify-center text-dark font-black">
                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-bold text-white"><?php echo htmlspecialchars($u['username']); ?></p>
                                <p class="text-[10px] text-slate-500 uppercase font-black">NODE ID: #<?php echo $u['id']; ?>
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex flex-col">
                            <span
                                class="text-xs font-bold text-slate-300"><?php echo htmlspecialchars($u['role_name'] ?? 'Unassigned'); ?></span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest">Policy Level</span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <?php if ($u['status']): ?>
                            <span class="text-emerald-500 flex items-center gap-2 text-[10px] font-black uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Authorized
                            </span>
                        <?php else: ?>
                            <span class="text-red-500 flex items-center gap-2 text-[10px] font-black uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Revoked
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="<?php echo $baseUrl; ?>admin/users/edit?id=<?php echo $u['id']; ?>"
                                class="text-slate-400 hover:text-primary p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <button
                                    onclick="confirmDeleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')"
                                    class="text-slate-400 hover:text-red-500 p-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
    function confirmDeleteUser(id, name) {
        showModal({
            title: 'Personnel Purge',
            message: `Are you certain you want to revoke database access and delete user '${name}'? This action cannot be undone.`,
            type: 'confirm',
            typeLabel: 'NODAL DECOUPLING',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/users/delete?id=${id}`;
            }
        });
    }
</script>