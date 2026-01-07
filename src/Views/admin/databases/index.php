<?php use App\Core\Lang; ?>
<header class="mb-12">
    <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
        <?php echo Lang::get('databases.title'); ?>
    </h1>
    <p class="text-p-muted font-medium tracking-tight"><?php echo Lang::get('databases.subtitle'); ?></p>
</header>


<section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="glass-card sticky top-24">
            <h2 class="text-xl font-bold text-p-title mb-6 uppercase italic tracking-tighter">
                <?php echo Lang::get('databases.new_node'); ?>
            </h2>
            <form action="<?php echo $baseUrl; ?>admin/databases/create" method="POST" class="space-y-4">
                <div class="flex flex-col gap-2">
                    <label
                        class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1"><?php echo Lang::get('databases.node_name'); ?></label>
                    <input type="text" name="name" placeholder="<?php echo Lang::get('databases.node_placeholder'); ?>"
                        required class="input-glass w-full">
                </div>
                <button type="submit"
                    class="btn-primary w-full mt-2 font-black uppercase tracking-widest text-xs"><?php echo Lang::get('databases.create_node'); ?></button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-4">
        <?php foreach ($databases as $db): ?>
            <div class="glass-card flex items-center justify-between group overflow-hidden relative">
                <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex items-center gap-6 relative z-10">
                    <div
                        class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform duration-500 border border-primary/20 text-primary">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-p-title mb-1 tracking-tight uppercase italic">
                            <?php echo htmlspecialchars($db['name']); ?>
                        </h3>
                        <p class="text-[9px] text-p-muted font-black uppercase tracking-widest">
                            <?php echo htmlspecialchars($db['path']); ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 relative z-10 transition-opacity opacity-80 group-hover:opacity-100">
                    <a href="<?php echo $baseUrl; ?>admin/databases/view?id=<?php echo $db['id']; ?>"
                        class="btn-primary !bg-p-bg dark:!bg-white/5 !text-p-title dark:!text-slate-300 hover:!bg-primary/20 flex items-center gap-2 italic uppercase text-xs tracking-wider !py-2 shadow-sm">
                        <?php echo Lang::get('databases.interface'); ?> &rarr;
                    </a>
                    <button
                        onclick="confirmDeleteDB(<?php echo $db['id']; ?>, '<?php echo htmlspecialchars($db['name']); ?>')"
                        class="p-2 bg-p-bg dark:bg-white/5 rounded-lg text-p-muted hover:text-red-500 hover:bg-red-500/10 transition-all shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
    function confirmDeleteDB(id, name) {
        showModal({
            title: '<?php echo Lang::get('databases.delete_confirm_title'); ?>',
            message: `<?php echo Lang::get('databases.delete_confirm_msg'); ?>`.replace(':name', name),
            type: 'confirm',
            typeLabel: '<?php echo Lang::get('databases.delete_confirm_btn'); ?>',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/databases/delete?id=${id}`;
            }
        });
    }
</script>