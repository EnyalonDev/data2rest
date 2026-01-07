<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Api-Admin</title>
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
                        glass: 'rgba(30, 41, 59, 0.4)',
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
            .form-input { @apply w-full bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all font-medium; }
            .glass-card { @apply bg-glass backdrop-blur-3xl border border-glass-border rounded-[2.5rem] p-10 shadow-2xl; }
        }
    </style>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Animated background element -->
    <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary/10 blur-[120px] rounded-full -z-10 animate-pulse"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-indigo-500/5 blur-[100px] rounded-full -z-10"></div>

    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="inline-flex w-16 h-16 bg-primary rounded-2xl items-center justify-center text-dark text-3xl font-black mb-6 shadow-xl shadow-primary/20">A</div>
            <h1 class="text-4xl font-black text-white tracking-tighter uppercase italic">Api-Admin</h1>
            <p class="text-slate-500 font-medium mt-2">Industrial Database Gateway</p>
        </div>

        <div class="glass-card">
            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-8 text-xs font-bold uppercase tracking-widest text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $baseUrl; ?>login" method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-1">Universal Identifier</label>
                    <input type="text" name="username" placeholder="Username" required class="form-input">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-1">Security Token (Password)</label>
                    <input type="password" name="password" placeholder="••••••••" required class="form-input">
                </div>
                <button type="submit" class="w-full bg-primary text-dark font-black py-4 rounded-xl transition-all duration-300 hover:scale-[1.02] active:scale-95 shadow-xl shadow-primary/20 uppercase tracking-widest text-sm mt-4">
                    Authenticate Cluster
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-glass-border text-center">
                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-[0.2em] mb-4">Credentials Environment: PRODUCTION</p>
                <div class="flex justify-center gap-4">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/40"></div>
                </div>
            </div>
        </div>

        <footer class="mt-12 text-center">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                © 2026 EnyalonDev Framework | <a href="https://nestorovallos.com" target="_blank" class="text-primary hover:underline">Support Node</a>
            </p>
        </footer>
    </div>
</body>
</html>
