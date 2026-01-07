<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user ? 'Edit' : 'Create'; ?> User - Api-Admin</title>
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
            .btn-primary { @apply bg-primary text-dark font-black py-3 px-10 rounded-xl transition-all hover:scale-[1.05] active:scale-95; }
            .glass-card { @apply bg-glass backdrop-blur-xl border border-glass-border rounded-[2rem] p-10; }
            .form-input { @apply w-full bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 transition-all font-medium; }
            .form-label { @apply block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2; }
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
            <span class="text-slate-200 font-black uppercase tracking-widest text-[9px] underline decoration-primary decoration-2 underline-offset-4"><?php echo $user ? 'VERIFY NODE' : 'MANIFEST NODE'; ?></span>
        </div>
        <a href="<?php echo $baseUrl; ?>admin/users" class="btn-outline text-[10px] uppercase tracking-widest">&larr; ABORT</a>
            <?php include __DIR__ . '/../../partials/theme_toggle.php'; ?>
    </nav>

    <main class="container mx-auto pt-24 pb-20 px-6 max-w-2xl">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-black text-white italic tracking-tighter mb-2"><?php echo $user ? 'Verify' : 'Manifest'; ?> <span class="text-primary">Human Node</span></h1>
            <p class="text-slate-500 font-medium">Connect a new node to the cluster with specific access policies.</p>
        </header>

        <form action="<?php echo $baseUrl; ?>admin/users/save" method="POST" class="space-y-8">
            <input type="hidden" name="id" value="<?php echo $user['id'] ?? ''; ?>">
            
            <section class="glass-card space-y-6">
                <div>
                    <label class="form-label">Username (Node ID)</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required class="form-input" placeholder="e.g. neuro_nexus">
                </div>
                <div>
                    <label class="form-label">Credential Token (Password) <?php echo $user ? '<span class="text-amber-500/50 italic">(Leave empty to maintain current)</span>' : ''; ?></label>
                    <input type="password" name="password" <?php echo $user ? '' : 'required'; ?> class="form-input" placeholder="••••••••">
                </div>
                <div>
                    <label class="form-label">Assigned Access Policy (Role)</label>
                    <select name="role_id" required class="form-input">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo ($user['role_id'] ?? '') == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pt-4 flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Node Status</span>
                    <label class="flex items-center gap-4 cursor-pointer">
                        <input type="checkbox" name="status" value="1" <?php echo ($user['status'] ?? 1) ? 'checked' : ''; ?> class="w-6 h-6 rounded bg-black/40 text-primary border-glass-border">
                        <span class="text-xs font-black uppercase tracking-widest">Active</span>
                    </label>
                </div>
            </section>

            <div class="flex justify-center pt-8">
                <button type="submit" class="btn-primary">Connect Node</button>
            </div>
        </form>
    </main>
</body>
</html>
