<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles - Api-Admin</title>
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
            .btn-primary { @apply bg-primary text-dark font-black py-2 px-6 rounded-xl transition-all hover:scale-[1.02] active:scale-95; }
            .glass-card { @apply bg-glass backdrop-blur-xl border border-glass-border rounded-2xl p-8; }
        }
    </style>
    <?php include __DIR__ . '/../../partials/theme_engine.php'; ?>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans border-t-4 border-primary">
    <nav class="fixed top-0 w-full h-16 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4 text-xs font-medium tracking-tight">
            <a href="<?php echo $baseUrl; ?>" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">DASHBOARD</a>
            <span class="text-slate-700">/</span>
            <a href="<?php echo $baseUrl; ?>admin/users" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">TEAM MANAGEMENT</a>
            <span class="text-slate-700">/</span>
            <span class="text-slate-200 font-black uppercase tracking-widest text-[9px] underline decoration-primary decoration-2 underline-offset-4">ROLES & POLICIES</span>
        </div>
        <a href="<?php echo $baseUrl; ?>admin/roles/new" class="btn-primary text-[10px] uppercase tracking-widest">NEW ROLE +</a>
            <?php include __DIR__ . '/../../partials/theme_toggle.php'; ?>
    </nav>

    <main class="container mx-auto pt-24 pb-20 px-6 max-w-5xl">
        <header class="mb-12">
            <h1 class="text-5xl font-black text-white italic tracking-tighter mb-2">Access <span class="text-primary">Policies</span></h1>
            <p class="text-slate-500 font-medium">Define complex permission sets for different job functions.</p>
        </header>

        <section class="glass-card overflow-hidden !p-0">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-white/5 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        <th class="px-8 py-4">Role Name</th>
                        <th class="px-8 py-4">Status</th>
                        <th class="px-8 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.03]">
                    <?php foreach ($roles as $r): 
                        $perms = json_decode($r['permissions'] ?? '[]', true);
                        $isAdmin = isset($perms['all']) && $perms['all'] === true;
                    ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary border border-primary/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white"><?php echo htmlspecialchars($r['name']); ?></p>
                                        <p class="text-[9px] text-slate-500 font-black uppercase">
                                            <?php echo $isAdmin ? 'Full Root Access' : 'Custom Defined Policy'; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <?php if ($isAdmin): ?>
                                    <span class="badge bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest">System Master</span>
                                <?php else: ?>
                                    <span class="badge bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest">Active Policy</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="<?php echo $baseUrl; ?>admin/roles/edit?id=<?php echo $r['id']; ?>" class="text-slate-400 hover:text-primary p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <a href="<?php echo $baseUrl; ?>admin/roles/delete?id=<?php echo $r['id']; ?>" class="text-slate-400 hover:text-red-500 p-2" onclick="return confirm('Delete this access policy?')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
