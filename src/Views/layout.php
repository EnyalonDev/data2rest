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
            --p-bg: #f8fafc;
            --p-card: #ffffff;
            --p-border: rgba(15, 23, 42, 0.1);
            --p-nav: rgba(255, 255, 255, 0.8);
            --p-input: #ffffff;
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
        <footer class="mt-24 pt-12 border-t border-glass-border flex flex-col items-center gap-4">
            <p class="text-slate-500 text-sm">
                © 2026 Data2Rest Framework | Powered by <a href="https://nestorovallos.com" target="_blank"
                    class="text-primary hover:underline font-bold">EnyalonDev</a>
            </p>
        </footer>
    </main>

    <?php include __DIR__ . '/partials/system_modal.php'; ?>
</body>

</html>