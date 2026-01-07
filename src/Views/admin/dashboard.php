<?php use App\Core\Auth; ?>
<!-- Header Section -->
<header class="text-center mb-16 relative">
    <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 blur-[120px] rounded-full -z-10">
    </div>
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
        <a href="<?php echo $baseUrl; ?>admin/databases"
            class="glass-card group hover:scale-[1.02] hover:border-primary/50">
            <div
                class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 7v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7m-16 0c0-1.1.9-2 2-2h12c1.1 0 2 .9 2 2m-16 0h16m-16 4h16m-16 4h16">
                    </path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-4">Databases & Tables</h3>
            <p class="text-slate-400 mb-8 leading-relaxed">
                Build your heart. Create SQLite stores, architect tables, and craft dynamic UI fields with surgical
                precision.
            </p>
            <div class="btn-primary w-full text-center flex items-center justify-center gap-2">
                Enter Architect <span>&rarr;</span>
            </div>
        </a>
    <?php endif; ?>

    <!-- API Module -->
    <?php if (Auth::hasPermission('module:api', 'view_keys')): ?>
        <a href="<?php echo $baseUrl; ?>admin/api" class="glass-card group hover:scale-[1.02] hover:border-primary/50">
            <div
                class="w-16 h-16 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
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
            <div
                class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
            </div>
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