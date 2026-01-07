<?php use App\Core\Auth; ?>
<style type="text/tailwindcss">
    .input-field {
        @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 transition-all font-medium;
    }
</style>

<header class="mb-12 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-5xl font-black text-white italic tracking-tighter uppercase">Node <span
                    class="text-primary">Schema</span></h1>
            <p class="text-slate-500 font-medium tracking-tight">Managing structural data segments for
                <b><?php echo htmlspecialchars($database['name']); ?></b>.
            </p>
        </div>
        <div class="flex gap-4">
            <a href="<?php echo $baseUrl; ?>admin/databases" class="btn-primary !bg-slate-800 !text-slate-300">
                &larr; BACK
            </a>
            <a href="<?php echo $baseUrl; ?>admin/databases/sync?id=<?php echo $database['id']; ?>"
                class="inline-flex items-center gap-2 group text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/5 px-4 py-2 rounded-lg border border-emerald-500/20 hover:bg-emerald-500/10 transition-all">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Synchronize Tables & Fields
            </a>
            <a href="<?php echo $baseUrl; ?>admin/api/docs?db_id=<?php echo $database['id']; ?>"
                class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all">View
                API Docs &rarr;</a>
        </div>
    </div>
    <div class="glass-card !p-4 !px-6 border-primary/20 bg-primary/5 w-full md:w-auto">
        <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-3">Initialize Table</h2>
        <form action="<?php echo $baseUrl; ?>admin/databases/table/create" method="POST" class="flex gap-2">
            <input type="hidden" name="db_id" value="<?php echo $database['id']; ?>">
            <input type="text" name="table_name" placeholder="table_name" required
                class="input-field !py-2 !px-3 text-sm">
            <button type="submit" class="btn-primary !py-2">Create +</button>
        </form>
    </div>
</header>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($tables as $table): ?>
        <div class="glass-card group hover:scale-[1.02] hover:shadow-2xl hover:shadow-primary/5">
            <div class="flex items-center justify-between mb-6">
                <div
                    class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-2xl group-hover:text-primary transition-colors">
                    📄</div>
                <div class="flex gap-1 group-hover:opacity-100 opacity-20 transition-opacity">
                    <a href="<?php echo $baseUrl; ?>admin/databases/fields?db_id=<?php echo $database['id']; ?>&table=<?php echo $table; ?>"
                        class="p-2 text-slate-400 hover:text-primary" title="Edit Fields">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                        </svg>
                    </a>
                    <button onclick="confirmDeleteTable('<?php echo $table; ?>')"
                        class="p-2 text-slate-400 hover:text-red-500" title="Drop Table">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <h3 class="text-2xl font-black text-white mb-6 uppercase tracking-tight"><?php echo htmlspecialchars($table); ?>
            </h3>
            <div class="flex gap-4">
                <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=<?php echo $database['id']; ?>&table=<?php echo $table; ?>"
                    class="btn-primary flex-1 text-center font-bold italic tracking-wider !py-2">ENTER SEGMENT</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function confirmDeleteTable(table) {
        showModal({
            title: 'Structural Purge',
            message: `Are you absolutely certain you want to erase the '${table}' table and all its stored data? This action is non-reversible.`,
            type: 'confirm',
            typeLabel: 'NODAL DESTRUCT SEQUENCE',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/databases/table/delete?db_id=<?php echo $database['id']; ?>&table=${table}`;
            }
        });
    }
</script>