<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Api-Admin</title>
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
                @apply bg-primary text-dark font-bold py-3 px-6 rounded-xl transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] active:scale-95;
            }
            .glass-card {
                @apply bg-glass backdrop-blur-xl border border-glass-border rounded-[2rem] p-8 transition-all duration-300;
            }
            .nav-link {
                @apply text-slate-400 hover:text-primary transition-colors duration-200 font-medium;
            }
        }
        </style>
    <?php include __DIR__ . '/../partials/theme_engine.php'; ?>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans selection:bg-primary/30">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full h-20 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-dark text-2xl font-black">A</div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-bold text-white tracking-tight">Api-Admin</span>
                <span class="text-slate-500 font-medium">/</span>
                <span class="text-slate-400 font-medium">Dashboard</span>
            </div>
        </div>
        
        <div class="flex items-center gap-6">
            <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
            <span class="hidden md:block text-sm text-slate-400">Welcome back, <b class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
            <a href="<?php echo $baseUrl; ?>logout" class="bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white px-4 py-2 rounded-lg text-sm font-bold transition-all border border-red-500/20">Logout</a>
        </div>
    </nav>

    <main class="container mx-auto pt-32 pb-20 px-6">
        <!-- Header Section -->
        <header class="text-center mb-16 relative">
            <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 blur-[120px] rounded-full -z-10"></div>
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 tracking-tight">
                Control <span class="text-primary italic">Center</span>
            </h1>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Management bridge for authorized neural network nodes and data collections.
            </p>
        </header>

        <!-- Modules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Database Module -->
            <?php if (Auth::hasPermission('module:databases', 'view')): ?>
            <a href="<?php echo $baseUrl; ?>admin/databases" class="glass-card group hover:scale-[1.02] hover:border-primary/50">
                <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary"><svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7m-16 0c0-1.1.9-2 2-2h12c1.1 0 2 .9 2 2m-16 0h16m-16 4h16m-16 4h16"></path></svg></div>
                <h3 class="text-2xl font-bold text-white mb-4">Databases & Tables</h3>
                <p class="text-slate-400 mb-8 leading-relaxed">
                    Build your heart. Create SQLite stores, architect tables, and craft dynamic UI fields with surgical precision.
                </p>
                <div class="btn-primary w-full text-center flex items-center justify-center gap-2">
                    Enter Architect <span>&rarr;</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- API Module -->
            <?php if (Auth::hasPermission('module:api', 'view_keys')): ?>
            <a href="<?php echo $baseUrl; ?>admin/api" class="glass-card group hover:scale-[1.02] hover:border-primary/50">
                <div class="w-16 h-16 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary"><svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>
                <h3 class="text-2xl font-bold text-white mb-4">REST Endpoints</h3>
                <p class="text-slate-400 mb-8 leading-relaxed">
                    Expose and protect. Define secure gateways, browse generated schemas, and manage industrial-grade API keys.
                </p>
                <div class="btn-primary w-full text-center flex items-center justify-center gap-2">
                    Manage API <span>&rarr;</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- Users Module -->
            <?php if (Auth::hasPermission('module:users', 'view')): ?>
            <a href="<?php echo $baseUrl; ?>admin/users" class="glass-card group hover:scale-[1.02] hover:border-primary/50">
                <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary"><svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                <h3 class="text-2xl font-bold text-white mb-4">Team & Roles</h3>
                <p class="text-slate-400 mb-8 leading-relaxed">
                    Access control. Manage collaborators, assign specialized roles, and define granular database permissions.
                </p>
                <div class="btn-primary w-full text-center flex items-center justify-center gap-2">
                    Enter Team Control <span>&rarr;</span>
                </div>
            </a>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="mt-24 pt-12 border-t border-glass-border flex flex-col items-center gap-4">
            <p class="text-slate-500 text-sm">
                © 2026 Api-Admin Framework | Powered by <a href="https://nestorovallos.com" target="_blank" class="text-primary hover:underline font-bold">EnyalonDev</a>
            </p>
        </footer>
    </main>
</body>
</html>
