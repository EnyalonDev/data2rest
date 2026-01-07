<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables - Api-Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#38bdf8',
                        dark: '#0b1120',
                        glass: 'rgba(30, 41, 59, 0.5)',
                        'glass-border': 'rgba(255, 255, 255, 0.1)',
                    },
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn-primary { @apply bg-primary text-dark font-black py-2 px-6 rounded-xl transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/20; }
            .glass-card { @apply bg-glass backdrop-blur-xl border border-glass-border rounded-2xl p-8 transition-all duration-300; }
            .input-field { @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 transition-all font-medium; }
        }
    </style>
    <?php include __DIR__ . '/../../partials/theme_engine.php'; ?>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans border-t-4 border-primary pb-20">
    <nav class="fixed top-0 w-full h-16 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4 text-xs font-medium tracking-tight">
            <a href="<?php echo $baseUrl; ?>" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">DASHBOARD</a>
            <span class="text-slate-700">/</span>
            <a href="<?php echo $baseUrl; ?>admin/databases" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">DATABASES</a>
            <span class="text-slate-700">/</span>
            <span class="text-slate-200 font-black uppercase tracking-widest text-[9px] underline decoration-primary decoration-2 underline-offset-4"><?php echo htmlspecialchars($database['name'] ?? 'Tables'); ?></span>
        </div>
        <a href="<?php echo $baseUrl; ?>admin/api/docs?db_id=<?php echo $id; ?>" class="text-[10px] font-black uppercase text-primary border border-primary/20 px-4 py-2 rounded-xl bg-primary/5 hover:bg-primary/10 transition-all">View API Docs &rarr;</a>
            <?php include __DIR__ . '/../../partials/theme_toggle.php'; ?>
    </nav>

    <main class="container mx-auto pt-24 px-6 md:px-10">
        <header class="mb-12 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-5xl font-black text-white italic tracking-tighter uppercase">Node <span class="text-primary">Schema</span></h1>
                    <p class="text-slate-500 font-medium tracking-tight">Managing structural data segments for <b><?php echo htmlspecialchars($database['name']); ?></b>.</p>
                </div>
                <div>
                    <a href="<?php echo $baseUrl; ?>admin/databases/sync?id=<?php echo $id; ?>" 
                       class="inline-flex items-center gap-2 group text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/5 px-4 py-2 rounded-lg border border-emerald-500/20 hover:bg-emerald-500/10 transition-all">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Synchronize Tables & Fields
                    </a>
                </div>
            </div>
            <div class="glass-card !p-4 !px-6 border-primary/20 bg-primary/5 w-full md:w-auto">
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-3">Initialize Table</h2>
                <form action="<?php echo $baseUrl; ?>admin/databases/table/create" method="POST" class="flex gap-2">
                    <input type="hidden" name="db_id" value="<?php echo $id; ?>">
                    <input type="text" name="table_name" placeholder="table_name" required class="input-field !py-2 !px-3 text-sm">
                    <button type="submit" class="btn-primary !py-2">Create +</button>
                </form>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tables as $table): ?>
                <div class="glass-card group hover:scale-[1.02] hover:shadow-2xl hover:shadow-primary/5">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-2xl group-hover:text-primary transition-colors"></div>
                        <div class="flex gap-1 group-hover:opacity-100 opacity-20 transition-opacity">
                            <a href="<?php echo $baseUrl; ?>admin/databases/fields?db_id=<?php echo $id; ?>&table=<?php echo $table; ?>" class="p-2 text-slate-400 hover:text-primary" title="Edit Fields">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                            </a>
                            <button onclick="confirmDeleteTable('<?php echo $table; ?>')" class="p-2 text-slate-400 hover:text-red-500" title="Drop Table">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-white mb-6 uppercase tracking-tight"><?php echo htmlspecialchars($table); ?></h3>
                    <div class="flex gap-4">
                        <a href="<?php echo $baseUrl; ?>admin/crud/list?db_id=<?php echo $id; ?>&table=<?php echo $table; ?>" class="btn-primary flex-1 text-center font-bold italic tracking-wider">ENTER SEGMENT</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function confirmDeleteTable(table) {
            showModal({
                title: 'Structural Purge',
                message: `Are you absolutely certain you want to erase the '${table}' table and all its stored data? This action is non-reversible.`,
                type: 'confirm',
                typeLabel: 'NODAL DESTRUCT SEQUENCE',
                onConfirm: () => {
                    window.location.href = `<?php echo $baseUrl; ?>admin/databases/table/delete?db_id=<?php echo $id; ?>&table=${table}`;
                }
            });
        }
    </script>
    <?php require_once __DIR__ . '/../../partials/system_modal.php'; ?>
</body>
</html>
