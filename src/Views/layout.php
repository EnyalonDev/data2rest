<!DOCTYPE html>
<html lang="<?php echo \App\Core\Lang::current(); ?>" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $title ?? 'Api-Admin'; ?>
    </title>
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
                @apply bg-primary text-dark font-bold py-3 px-6 rounded-xl transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed;
            }
            .glass-card {
                @apply bg-glass backdrop-blur-xl border border-glass-border rounded-[2rem] p-8 transition-all duration-300;
            }
            .nav-link {
                @apply text-slate-400 hover:text-primary transition-colors duration-200 font-medium;
            }
            .form-input {
                @apply w-full bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all font-medium;
            }
            .form-label {
                @apply block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 transition-colors;
            }
            .form-input-valid {
                @apply border-emerald-500/50 ring-4 ring-emerald-500/10 bg-emerald-500/5;
            }
            .form-input-error {
                @apply border-red-500/50 ring-4 ring-red-500/10 bg-red-500/5;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .animate-shake {
                animation: shake 0.2s ease-in-out 0s 2;
            }
        }
    </style>
    <?php include __DIR__ . '/partials/theme_engine.php'; ?>
</head>

<body class="bg-dark text-slate-200 min-h-screen font-sans selection:bg-primary/30">
    <!-- Navbar -->
    <nav
        class="fixed top-0 w-full h-20 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>" class="flex items-center gap-4">
                <div
                    class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-dark text-2xl font-black">
                    A</div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-white tracking-tight">Api-Admin</span>
                </div>
            </a>

            <span class="text-slate-600 font-medium">/</span>
            <a href="<?php echo $baseUrl; ?>"
                class="text-slate-500 hover:text-primary transition-colors font-medium"><?php echo \App\Core\Lang::get('common.dashboard'); ?></a>

            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $label => $link): ?>
                    <span class="text-slate-600 font-medium">/</span>
                    <?php if ($link): ?>
                        <a href="<?php echo $baseUrl . $link; ?>"
                            class="text-slate-500 hover:text-primary transition-colors font-medium"><?php echo $label; ?></a>
                    <?php else: ?>
                        <span
                            class="text-white font-bold italic tracking-tight underline decoration-primary/30 decoration-2 underline-offset-4"><?php echo $label; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php elseif (isset($breadcrumb)): ?>
                <span class="text-slate-600 font-medium">/</span>
                <span
                    class="text-white font-bold italic tracking-tight underline decoration-primary/30 decoration-2 underline-offset-4"><?php echo $breadcrumb; ?></span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-6">
            <!-- Language Switcher -->
            <div class="flex items-center bg-black/40 rounded-lg p-1 border border-glass-border">
                <a href="<?php echo $baseUrl; ?>lang/es"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'es' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">ES</a>
                <a href="<?php echo $baseUrl; ?>lang/en"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'en' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">EN</a>
                <a href="<?php echo $baseUrl; ?>lang/pt"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'pt' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">PT</a>
            </div>

            <?php include __DIR__ . '/partials/theme_toggle.php'; ?>
            <?php if (\App\Core\Auth::check()): ?>
                <span class="hidden md:block text-sm text-slate-400"><?php echo \App\Core\Lang::get('common.welcome'); ?>,
                    <b class="text-white">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </b></span>
                <a href="<?php echo $baseUrl; ?>logout"
                    class="bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white px-4 py-2 rounded-lg text-sm font-bold transition-all border border-red-500/20"><?php echo \App\Core\Lang::get('common.logout'); ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto pt-32 pb-20 px-6">
        <?php require_once $viewFile; ?>

        <!-- Footer -->
        <footer class="mt-24 pt-12 border-t border-glass-border flex flex-col items-center gap-4">
            <p class="text-slate-500 text-sm">
                © 2026 Api-Admin Framework | Powered by <a href="https://nestorovallos.com" target="_blank"
                    class="text-primary hover:underline font-bold">EnyalonDev</a>
            </p>
        </footer>
    </main>

    <?php include __DIR__ . '/partials/system_modal.php'; ?>
</body>

</html>