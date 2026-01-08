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
    <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
        <?php echo Lang::get('common.welcome'); ?>
    </h1>
    <p class="text-p-muted font-medium max-w-2xl mx-auto"><?php echo Lang::get('dashboard.subtitle'); ?></p>
</header>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-primary/50">
        <span class="text-4xl font-black text-p-title mb-2"><?php echo $stats['total_databases']; ?></span>
        <span
            class="text-[10px] font-black text-p-muted uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.dbs'); ?></span>
    </div>
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50">
        <span class="text-4xl font-black text-p-title mb-2"><?php echo number_format($stats['total_records']); ?></span>
        <span
            class="text-[10px] font-black text-p-muted uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.records'); ?></span>
    </div>
    <div class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50">
        <span class="text-4xl font-black text-p-title mb-2"><?php echo $stats['storage_usage']; ?></span>
        <span
            class="text-[10px] font-black text-p-muted uppercase tracking-widest"><?php echo Lang::get('dashboard.stats.storage'); ?></span>
    </div>
</div>

<?php if ($stats['total_databases'] == 0): ?>
    <!-- Getting Started Banner -->
    <div class="glass-card mb-12 relative overflow-hidden group border-primary/30">
        <div
            class="absolute -right-20 -top-20 w-64 h-64 bg-primary/20 blur-[80px] rounded-full group-hover:bg-primary/30 transition-all duration-700">
        </div>
        <div class="relative z-10 p-8 md:p-12 flex flex-col md:flex-row items-center gap-10">
            <div
                class="w-24 h-24 bg-primary text-dark rounded-3xl flex items-center justify-center text-4xl shadow-2xl shadow-primary/40 animate-bounce">
                🚀
            </div>
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-3xl md:text-4xl font-black text-p-title mb-4 tracking-tight uppercase italic">
                    <?php echo Lang::get('dashboard.welcome_title'); ?>
                </h2>
                <p class="text-p-muted font-medium mb-8 max-w-xl leading-relaxed">
                    <?php echo Lang::get('dashboard.welcome_text'); ?>
                </p>
                <div class="flex flex-wrap justify-center md:justify-start gap-4">
                    <a href="<?php echo $baseUrl; ?>admin/demo/load"
                        class="btn-primary flex items-center gap-2 !py-4 !px-8 text-xs font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-105 transition-all">
                        <span>✨</span> <?php echo Lang::get('dashboard.load_demo'); ?>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/databases"
                        class="px-8 py-4 rounded-xl border border-glass-border text-xs font-black uppercase tracking-widest text-p-muted hover:text-white hover:bg-white/5 transition-all">
                        <?php echo Lang::get('databases.new_node'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Modules -->
    <div class="lg:col-span-2 space-y-8">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-p-title mb-2"><?php echo Lang::get('dashboard.db_title'); ?></h3>
                    <p class="text-xs text-p-muted mb-6 leading-relaxed">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-p-title mb-2"><?php echo Lang::get('dashboard.api_title'); ?></h3>
                    <p class="text-xs text-p-muted mb-6 leading-relaxed">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-p-title mb-2"><?php echo Lang::get('dashboard.team_title'); ?></h3>
                    <p class="text-xs text-p-muted mb-6 leading-relaxed">
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
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> <?php echo Lang::get('dashboard.activity.title'); ?>
        </h2>
        <div class="glass-card !p-6 space-y-6">
            <?php if (empty($stats['recent_activity'])): ?>
                <div class="py-10 text-center text-p-muted">
                    <p class="text-[10px] font-black uppercase tracking-widest">
                        <?php echo Lang::get('dashboard.activity.empty'); ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($stats['recent_activity'] as $act): ?>
                    <div class="relative pl-6 border-l border-white/5">
                        <div class="absolute left-[-5px] top-1 w-2.5 h-2.5 rounded-full bg-primary border-4 border-dark"></div>
                        <p class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">
                            <?php echo $act['date']; ?>
                        </p>
                        <p class="text-xs font-bold text-p-title mb-1"><?php echo htmlspecialchars($act['label']); ?></p>
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

<?php if (Auth::isAdmin()): ?>
    <!-- Danger Zone -->
    <div class="mt-16 pt-16 border-t border-glass-border">
        <h2 class="text-xs font-black text-red-500 uppercase tracking-[0.3em] mb-8 flex items-center gap-3 justify-center">
            <span class="w-12 h-[1px] bg-red-500/20"></span> ⚠️ DANGER ZONE <span class="w-12 h-[1px] bg-red-500/20"></span>
        </h2>
        <div class="max-w-xl mx-auto text-center">
            <button onclick="triggerResetSystem()"
                class="px-8 py-4 rounded-xl border border-red-500/30 text-red-500 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-red-500 hover:text-white transition-all duration-300">
                ⚡ <?php echo Lang::get('dashboard.reset_system'); ?>
            </button>
        </div>
    </div>

    <script>
        function triggerResetSystem() {
            // First Confirmation
            showModal({
                title: '<?php echo htmlspecialchars(addslashes(Lang::get('dashboard.reset_title')), ENT_QUOTES); ?>',
                message: '<?php echo htmlspecialchars(addslashes(Lang::get('dashboard.reset_msg_1')), ENT_QUOTES); ?>',
                type: 'confirm',
                stayOpen: true, // Prevent flickering by not closing before the next modal
                confirmText: '<?php echo htmlspecialchars(addslashes(Lang::get('common.confirm')), ENT_QUOTES); ?>',
                onConfirm: function () {
                    // Second Confirmation (The "Are you really sure?" step with checkbox)
                    showModal({
                        title: '☢️ <?php echo htmlspecialchars(addslashes(Lang::get('dashboard.reset_title')), ENT_QUOTES); ?>',
                        message: '<?php echo htmlspecialchars(addslashes(Lang::get('dashboard.reset_msg_2')), ENT_QUOTES); ?>',
                        type: 'confirm',
                        confirmText: '<?php echo htmlspecialchars(addslashes(Lang::get('dashboard.reset_confirm_btn')), ENT_QUOTES); ?>',
                        safetyCheck: '<?php echo htmlspecialchars(addslashes(Lang::get('dashboard.confirm_checkbox')), ENT_QUOTES); ?>',
                        onConfirm: function () {
                            window.location.href = '<?php echo $baseUrl; ?>admin/system/reset';
                        }
                    });
                }
            });
        }
    </script>
<?php endif; ?>