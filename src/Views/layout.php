<!DOCTYPE html>
<html lang="<?php echo \App\Core\Lang::current(); ?>" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $title ?? 'Data2Rest'; ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#38bdf8',
                        'p-text': 'var(--p-text)',
                        'p-muted': 'var(--p-muted)',
                        'p-title': 'var(--p-title)',
                        'glass-border': 'var(--p-border)',
                        'bg-glass': 'var(--p-card)',
                        dark: '#0b1120',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        :root {
            --p-text: #1e293b;
            --p-title: #0f172a;
            --p-muted: #64748b;
            --p-bg: #eff3f8;
            /* Darker, cool grey for better contrast with white cards */
            --p-card: #ffffff;
            --p-border: #e2e8f0;
            /* Clearer, solid border color */
            --p-nav: rgba(255, 255, 255, 0.85);
            /* Slightly more opaque nav */
            --p-input: #f8fafc;
            /* Subtle background for inputs */
            --p-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            /* Soft but visible shadow for depth */
        }

        .dark {
            --p-text: #cbd5e1;
            --p-title: #ffffff;
            --p-muted: #94a3b8;
            --p-bg: #0b1120;
            --p-card: rgba(30, 41, 59, 0.6);
            --p-border: rgba(255, 255, 255, 0.05);
            --p-nav: rgba(11, 17, 32, 0.8);
            --p-input: rgba(255, 255, 255, 0.05);
            --p-shadow: none;
        }

        body {
            background-color: var(--p-bg);
            color: var(--p-text);
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background-color: var(--p-card);
            border: 1px solid var(--p-border);
            backdrop-filter: blur(20px);
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: var(--p-shadow);
        }

        .form-input {
            background-color: var(--p-input);
            color: var(--p-text);
            border: 2px solid var(--p-border);
            width: 100%;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .form-label {
            color: var(--p-muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: block;
            margin-bottom: 0.5rem;
        }

        .custom-select {
            background-color: var(--p-input);
            color: var(--p-title);
            border: 2px solid var(--p-border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            width: 100%;
            outline: none;
            transition: all 0.2s;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        .custom-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        /* Custom UI */
        .btn-primary {
            background-color: #38bdf8;
            color: #0b1120;
            font-weight: 800;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
        }

        .btn-outline {
            border: 1px solid var(--p-border);
            color: var(--p-muted);
            font-weight: 800;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-transform: uppercase;
            font-size: 10px;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--p-text);
        }

        .animate-shake {
            animation: shake 0.2s ease-in-out 2;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }
    </style>
    <?php include __DIR__ . '/partials/theme_engine.php'; ?>
</head>

<body class="selection:bg-primary/30">
    <!-- Navbar -->
    <nav style="background-color: var(--p-nav);"
        class="fixed top-0 w-full h-20 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>" class="flex items-center gap-4">
                <div
                    class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-dark text-2xl font-black">
                    D</div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-p-title tracking-tight">Data2Rest</span>
                </div>
            </a>

            <span class="text-p-muted opacity-40 font-medium">/</span>
            <a href="<?php echo $baseUrl; ?>"
                class="text-p-muted hover:text-primary transition-colors font-medium"><?php echo \App\Core\Lang::get('common.dashboard'); ?></a>

            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $label => $link): ?>
                    <span class="text-p-muted opacity-40 font-medium">/</span>
                    <?php if ($link): ?>
                        <a href="<?php echo $baseUrl . $link; ?>"
                            class="text-p-muted hover:text-primary transition-colors font-medium"><?php echo $label; ?></a>
                    <?php else: ?>
                        <span
                            class="text-p-title font-bold italic tracking-tight underline decoration-primary/30 decoration-2 underline-offset-4"><?php echo $label; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php elseif (isset($breadcrumb)): ?>
                <span class="text-p-muted opacity-40 font-medium">/</span>
                <span
                    class="text-p-title font-bold italic tracking-tight underline decoration-primary/30 decoration-2 underline-offset-4"><?php echo $breadcrumb; ?></span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-6">
            <!-- Project Switcher -->
            <?php if (\App\Core\Auth::check() && isset($_SESSION['user_projects'])): ?>
                <div class="relative">
                    <a href="<?php echo $baseUrl; ?>admin/projects/select"
                        class="flex items-center gap-2 px-6 py-2 bg-p-input border border-glass-border rounded-xl hover:border-primary/50 transition-all group">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span
                            class="text-[11px] font-black uppercase tracking-widest text-p-muted group-hover:text-primary transition-colors">
                            <?php echo \App\Core\Lang::get('common.project', 'Project'); ?>:
                        </span>
                        <span class="text-[11px] font-black uppercase tracking-widest text-p-title whitespace-nowrap">
                            <?php
                            $activeId = \App\Core\Auth::getActiveProject();
                            $activeName = 'Select Project';
                            foreach ($_SESSION['user_projects'] as $p) {
                                if ($p['id'] == $activeId)
                                    $activeName = $p['name'];
                            }
                            echo htmlspecialchars($activeName);
                            ?>
                        </span>
                        <div class="w-[1px] h-4 bg-glass-border mx-2"></div>
                        <span
                            class="text-[9px] font-black text-primary uppercase tracking-widest opacity-60 group-hover:opacity-100 transition-opacity">Cambiar
                            &rarr;</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Language Switcher -->
            <div class="flex items-center bg-black/40 rounded-lg p-1">
                <a href="<?php echo $baseUrl; ?>lang/es"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'es' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">ES</a>
                <a href="<?php echo $baseUrl; ?>lang/en"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'en' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">EN</a>
                <a href="<?php echo $baseUrl; ?>lang/pt"
                    class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter transition-all <?php echo \App\Core\Lang::current() === 'pt' ? 'bg-primary text-dark' : 'text-slate-500 hover:text-white'; ?>">PT</a>
            </div>

            <?php include __DIR__ . '/partials/theme_toggle.php'; ?>
            <?php if (\App\Core\Auth::check()): ?>
                <span class="hidden md:block text-sm text-p-muted"><?php echo \App\Core\Lang::get('common.welcome'); ?>,
                    <b class="text-p-title">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </b></span>
                <a href="<?php echo $baseUrl; ?>logout"
                    class="bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white px-4 py-2 rounded-lg text-sm font-bold transition-all border border-red-500/20"><?php echo \App\Core\Lang::get('common.logout'); ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto pt-32 pb-20 px-6">
        <?php require_once $viewFile; ?>

        <!-- Footer -->
        <footer class="mt-24 pt-12 border-t border-glass-border flex flex-col items-center gap-6">
            <?php if (\App\Core\Auth::isAdmin() && \App\Core\Auth::isDevMode()): ?>
                <div
                    class="flex flex-wrap justify-center gap-4 p-4 bg-amber-500/5 border border-amber-500/20 rounded-2xl mb-4">
                    <div class="flex items-center gap-2 pr-4 border-r border-amber-500/20">
                        <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">🛠️ Dev Tools</span>
                    </div>
                    <button onclick="devClearCache()"
                        class="px-4 py-2 bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                        Limpiar Caché
                    </button>
                    <button onclick="devClearSessions()"
                        class="px-4 py-2 bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                        Limpiar Sesiones
                    </button>
                    <button onclick="window.location.reload(true)"
                        class="px-4 py-2 bg-primary/10 text-primary hover:bg-primary hover:text-dark rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                        Súper Refresh (F5+)
                    </button>
                </div>
                <script>
                    function devClearCache() {
                        fetch('<?php echo $baseUrl; ?>admin/system/clear-cache', { method: 'POST' })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showModal({ title: 'Cache Base de Datos/App', message: 'Variables de sistema y archivos temporales limpiados correctamente.', type: 'success' });
                                }
                            });
                    }
                    function devClearSessions() {
                        showModal({
                            title: 'Limpiar Sesiones',
                            message: 'Esto cerrará la sesión de todos los demás usuarios. ¿Continuar?',
                            type: 'confirm',
                            onConfirm: () => {
                                fetch('<?php echo $baseUrl; ?>admin/system/clear-sessions', { method: 'POST' })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            showModal({ title: 'Sesiones Limpias', message: `Se han eliminado ${data.cleared} sesiones inactivas con éxito.`, type: 'success' });
                                        }
                                    });
                            }
                        });
                    }
                </script>
            <?php endif; ?>

            <div class="flex flex-col items-center gap-2 text-center">
                <p class="text-slate-500 text-sm">
                    © 2026 Data2Rest Framework | Powered by <a href="https://nestorovallos.com" target="_blank"
                        class="text-primary hover:underline font-bold">EnyalonDev</a>
                </p>
                <p class="text-xs text-slate-600">
                    <a href="https://github.com/enyalondev/data2rest" target="_blank" rel="noopener noreferrer"
                        class="hover:text-primary transition-colors inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                        </svg>
                        View on GitHub
                    </a>
                </p>
            </div>
        </footer>
    </main>

    <?php include __DIR__ . '/partials/system_modal.php'; ?>
</body>

</html>