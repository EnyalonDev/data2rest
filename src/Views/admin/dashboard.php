<?php use App\Core\Auth;
use App\Core\Lang; ?>
<!-- Header Section -->
<header class="text-center mb-16 relative">
    <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 blur-[120px] rounded-full -z-10">
    </div>
    <div
        class="inline-block bg-primary text-dark px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 animate-pulse">
        <?php echo Lang::get('dashboard.title'); ?>
    </div>
    <h1 class="text-5xl md:text-7xl font-black text-white mb-6 tracking-tighter uppercase italic">
        <?php echo Lang::get('common.welcome'); ?>
    </h1>
    <p class="text-slate-500 font-medium max-w-2xl mx-auto"><?php echo Lang::get('dashboard.subtitle'); ?></p>
</header>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-primary/50">
        <span class="text-4xl font-black text-white mb-2"><?php echo $stats['total_databases']; ?></span>
        <span
            class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.dbs'); ?></span>
    </div>
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
        <span class="text-4xl font-black text-white mb-2"><?php echo number_format($stats['total_records']); ?></span>
        <span
            class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.records'); ?></span>
    </div>
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
        <span class="text-4xl font-black text-white mb-2"><?php echo $stats['storage_usage']; ?></span>
        <span
            class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.storage'); ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Modules -->
    <div class="lg:col-span-2 space-y-8">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> <?php echo Lang::get('common.actions'); ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Database Module -->
            <?php if (Auth::hasPermission('module:databases', 'view')): ?>
                <a href="<?php echo $baseUrl; ?>admin/databases"
                    class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                    <div
                        class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7m-16 0c0-1.1.9-2 2-2h12c1.1 0 2 .9 2 2m-16 0h16m-16 4h16m-16 4h16">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo Lang::get('dashboard.db_title'); ?></h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                        <?php echo Lang::get('dashboard.db_desc'); ?>
                    </p>
                    <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                        <?php echo Lang::get('dashboard.enter'); ?> <span>&rarr;</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- API Module -->
            <?php if (Auth::hasPermission('module:api', 'view_keys')): ?>
                <a href="<?php echo $baseUrl; ?>admin/api"
                    class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                    <div
                        class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2-2V12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo Lang::get('dashboard.api_title'); ?></h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                        <?php echo Lang::get('dashboard.api_desc'); ?>
                    </p>
                    <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                        <?php echo Lang::get('dashboard.enter'); ?> <span>&rarr;</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Users Module -->
            <?php if (Auth::hasPermission('module:users', 'view')): ?>
                <a href="<?php echo $baseUrl; ?>admin/users"
                    class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                    <div
                        class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo Lang::get('dashboard.team_title'); ?></h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                        <?php echo Lang::get('dashboard.team_desc'); ?>
                    </p>
                    <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                        <?php echo Lang::get('dashboard.enter'); ?> <span>&rarr;</span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <aside>
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> <?php echo Lang::get('dashboard.activity.title'); ?>
        </h2>
        <div class="glass-card !p-6 space-y-6">
            <?php if (empty($stats['recent_activity'])): ?>
                <div class="py-10 text-center text-slate-600">
                    <p class="text-[10px] font-black uppercase tracking-widest">
                        <?php echo Lang::get('dashboard.activity.empty'); ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($stats['recent_activity'] as $act): ?>
                    <div class="relative pl-6 border-l border-white/5">
                        <div class="absolute left-[-5px] top-1 w-2.5 h-2.5 rounded-full bg-primary border-4 border-dark"></div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                            <?php echo $act['date']; ?>
                        </p>
                        <p class="text-xs font-bold text-white mb-1"><?php echo htmlspecialchars($act['label']); ?></p>
                        <p class="text-[9px] font-black text-primary uppercase opacity-60">
                            <?php echo Lang::get('dashboard.activity.injected'); ?>         <?php echo $act['db']; ?> /
                            <?php echo $act['table']; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>