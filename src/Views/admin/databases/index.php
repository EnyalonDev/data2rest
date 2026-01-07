<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Databases - Api-Admin</title>
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
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn-primary {
                @apply bg-primary text-dark font-black py-2 px-6 rounded-xl transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] active:scale-95;
            }
            .glass-card {
                @apply bg-glass backdrop-blur-xl border border-glass-border rounded-2xl p-8 hover:border-primary/30 transition-all duration-500;
            }
            .input-field {
                @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 transition-all font-medium;
            }
        }
    </style>
    <?php include __DIR__ . '/../../partials/theme_engine.php'; ?>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans border-t-4 border-primary">
    <nav class="fixed top-0 w-full h-16 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4 text-xs font-medium tracking-tight">
            <a href="<?php echo $baseUrl; ?>" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">DASHBOARD</a>
            <span class="text-slate-700">/</span>
            <span class="text-slate-200 font-black uppercase tracking-widest text-[9px] underline decoration-primary decoration-2 underline-offset-4">DATABASES</span>
        </div>
            <?php include __DIR__ . '/../../partials/theme_toggle.php'; ?>
    </nav>

    <main class="container mx-auto pt-24 pb-20 px-6">
        <header class="mb-12">
            <h1 class="text-5xl font-black text-white italic tracking-tighter uppercase">Cluster <span class="text-primary">Storage</span></h1>
            <p class="text-slate-500 font-medium tracking-tight">Managing active SQLite data nodes within the current operation cluster.</p>
        </header>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="glass-card sticky top-24">
                    <h2 class="text-xl font-bold text-white mb-6 uppercase italic tracking-tighter">Initialize New Node</h2>
                    <form action="<?php echo $baseUrl; ?>admin/databases/create" method="POST" class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Node Identifier</label>
                            <input type="text" name="name" placeholder="e.g. Master Intelligence" required class="input-field">
                        </div>
                        <button type="submit" class="btn-primary w-full mt-2 font-black uppercase tracking-widest text-xs">Authorize Deployment +</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <?php foreach ($databases as $db): ?>
                    <div class="glass-card flex items-center justify-between group overflow-hidden relative">
                        <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex items-center gap-6 relative z-10">
                            <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-3xl group-hover:rotate-12 transition-transform duration-500 border border-primary/20">💾</div>
                            <div>
                                <h3 class="text-xl font-bold text-white mb-1 tracking-tight uppercase italic"><?php echo htmlspecialchars($db['name']); ?></h3>
                                <p class="text-[9px] text-slate-500 font-black uppercase tracking-widest"><?php echo htmlspecialchars($db['path']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 relative z-10">
                            <a href="<?php echo $baseUrl; ?>admin/databases/view?id=<?php echo $db['id']; ?>" class="btn-primary flex items-center gap-2 italic uppercase text-xs tracking-wider">
                                Interface &rarr;
                            </a>
                            <button onclick="confirmDeleteDB(<?php echo $db['id']; ?>, '<?php echo htmlspecialchars($db['name']); ?>')" class="p-3 text-slate-500 hover:text-red-500 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        function confirmDeleteDB(id, name) {
            showModal({
                title: 'Nodal Deletion',
                message: `Confirming the complete destruction of data node '${name}'. This action will terminate all associated tables and records permanently.`,
                type: 'confirm',
                typeLabel: 'SYSTEM PURGE SEQUENCE',
                onConfirm: () => {
                    window.location.href = `<?php echo $baseUrl; ?>admin/databases/delete?id=${id}`;
                }
            });
        }
    </script>
    <?php require_once __DIR__ . '/../../partials/system_modal.php'; ?>
</body>
</html>
