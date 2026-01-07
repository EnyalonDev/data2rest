<?php use App\Core\Auth;
$baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Data2Rest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#38bdf8',
                        'p-text': 'var(--p-text)',
                        'p-title': 'var(--p-title)',
                        'p-muted': 'var(--p-muted)',
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
            --p-input: #ffffff;
        }

        .dark {
            --p-text: #cbd5e1;
            --p-title: #ffffff;
            --p-muted: #94a3b8;
            --p-bg: #0b1120;
            --p-card: rgba(30, 41, 59, 0.4);
            --p-border: rgba(255, 255, 255, 0.1);
            --p-input: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--p-bg);
            color: var(--p-text);
            font-family: 'Outfit', sans-serif;
        }

        .form-input {
            background-color: var(--p-input);
            color: var(--p-text);
            border: 2px solid var(--p-border);
            width: 100%;
            border-radius: 1rem;
            padding: 0.75rem 1rem 0.75rem 3rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .glass-card {
            background-color: var(--p-card);
            backdrop-filter: blur(40px);
            border: 1px solid var(--p-border);
            border-radius: 2.5rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
    <?php include __DIR__ . '/../partials/theme_engine.php'; ?>
</head>

<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Animated background elements -->
    <div
        class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary/10 blur-[120px] rounded-full -z-10 animate-pulse">
    </div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-indigo-500/5 blur-[100px] rounded-full -z-10">
    </div>
    <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.03] pointer-events-none -z-20">
    </div>

    <div class="w-full max-w-md animate-in fade-in slide-in-from-bottom-10 duration-1000">
        <div class="text-center mb-10">
            <div class="relative inline-block mb-6">
                <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full"></div>
                <div
                    class="relative w-20 h-20 bg-gradient-to-br from-primary to-blue-600 rounded-2xl items-center justify-center flex text-dark text-4xl font-black shadow-xl">
                    D</div>
            </div>
            <h1 class="text-5xl font-black text-p-title tracking-tighter uppercase italic leading-none">Data<span
                    class="text-primary italic">2</span>Rest</h1>
            <p class="text-[10px] font-black tracking-[0.4em] text-p-muted mt-4 uppercase opacity-50">Industrial Grade
                Database Gateway</p>
        </div>

        <div class="glass-card border-t-4 border-primary">
            <?php if (isset($error)): ?>
                <div
                    class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-8 text-[10px] font-black uppercase tracking-widest text-center animate-bounce">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $baseUrl; ?>login" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] ml-1">Universal
                        Identifier</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-p-muted text-lg">👤</span>
                        <input type="text" name="username" placeholder="Username" required class="form-input pl-12">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-p-muted uppercase tracking-[0.2em] ml-1">Security
                        Token</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-p-muted text-lg">🔑</span>
                        <input type="password" name="password" placeholder="••••••••" required class="form-input pl-12">
                    </div>
                </div>
                <button type="submit"
                    class="group relative w-full bg-primary text-dark font-black py-4 rounded-xl transition-all duration-300 hover:scale-[1.02] active:scale-95 shadow-xl shadow-primary/20 uppercase tracking-widest text-sm mt-4 overflow-hidden">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        AUTHENTICATE CLUSTER <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </span>
                    <div
                        class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500">
                    </div>
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-glass-border text-center">
                <p class="text-[10px] font-bold text-p-muted uppercase tracking-[0.2em] mb-4">Environment: <span
                        class="text-emerald-500">SECURE_CLUSTER</span></p>
                <div class="flex justify-center gap-4">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                </div>
            </div>
        </div>

        <footer class="mt-12 text-center opacity-40 hover:opacity-100 transition-opacity">
            <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                © 2026 EnyalonDev Framework | <a href="https://nestorovallos.com" target="_blank"
                    class="text-primary hover:underline">Support Node</a>
            </p>
        </footer>
    </div>
</body>

</html>