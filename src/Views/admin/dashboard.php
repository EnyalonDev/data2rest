<?php use App\Core\Auth;
use App\Core\Lang; ?>
<!-- Header Section -->
<header class="text-center mb-16 relative">
    <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 blur-[120px] rounded-full -z-10">
    </div>
    <div
        class="inline-block bg-primary text-dark px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 animate-pulse">
        <?php echo (isset($project) && $project) ? Lang::get('dashboard.active_project') : Lang::get('dashboard.title'); ?>
    </div>
    <h1 class="text-5xl md:text-8xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
        <?php if (isset($project) && $project): ?>
            <?php echo htmlspecialchars($project['name']); ?>
        <?php else: ?>
            <?php echo Lang::get('common.welcome'); ?>
        <?php endif; ?>
    </h1>
    <p class="text-p-muted font-medium max-w-2xl mx-auto">
        <?php if (isset($project) && $project): ?>
            <?php echo Lang::get('common.welcome'); ?>. <?php echo Lang::get('dashboard.isolated_env'); ?>
        <?php else: ?>
            <?php echo Lang::get('dashboard.subtitle'); ?>
        <?php endif; ?>
    </p>
</header>

<!-- Project Context Section -->
<?php if (isset($project) && $project): ?>
    <div class="animate-in fade-in slide-in-from-top-4 duration-700 glass-card mb-12 bg-primary/5 border-primary/20">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6 text-left">
                <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center text-primary text-3xl">
                    📁
                </div>
                <div>
                    <h2 class="text-2xl font-black text-p-title tracking-tight">
                        <?php echo htmlspecialchars($project['name'] ?? Lang::get('dashboard.untitled_project')); ?>
                    </h2>
                    <p class="text-xs text-p-muted font-medium max-w-md">
                        <?php echo htmlspecialchars($project['description'] ?? Lang::get('dashboard.active_isolation_desc')); ?>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center md:justify-end gap-6">
                <div class="text-center md:text-right">
                    <span
                        class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2"><?php echo Lang::get('dashboard.subscription'); ?></span>
                    <span
                        class="px-4 py-1.5 bg-primary/20 text-primary rounded-xl text-[10px] font-black uppercase tracking-widest border border-primary/30">
                        <?php echo $project['plan_type'] ?? 'Free'; ?>
                    </span>
                </div>
                <div class="hidden md:block w-[1px] h-12 bg-glass-border"></div>
                <div class="text-center md:text-right">
                    <span
                        class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2"><?php echo Lang::get('dashboard.billing_cycle'); ?></span>
                    <span class="text-sm font-black text-p-title italic uppercase tracking-tighter">
                        <?php echo date('M d, Y', strtotime($project['next_billing_date'] ?? 'now')); ?>
                    </span>
                </div>
                <div class="hidden md:block w-[1px] h-12 bg-glass-border"></div>
                <a href="<?php echo $baseUrl; ?>admin/projects/select"
                    class="px-6 py-3 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary hover:text-dark transition-all">
                    <?php echo Lang::get('dashboard.change_project'); ?>
                </a>
            </div>
        </div>
    </div>
<?php elseif (!\App\Core\Auth::isAdmin()): ?>
    <div class="glass-card mb-12 bg-red-500/5 border-red-500/20 text-center py-12">
        <div
            class="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 text-3xl mx-auto mb-6">
            ⚠️
        </div>
        <h2 class="text-2xl font-black text-p-title mb-2"><?php echo Lang::get('dashboard.no_active_project'); ?></h2>
        <p class="text-p-muted font-medium mb-8"><?php echo Lang::get('dashboard.select_project_msg'); ?></p>
        <a href="<?php echo $baseUrl; ?>admin/projects"
            class="btn-primary !px-10 !py-4 font-black uppercase tracking-widest text-xs italic"><?php echo Lang::get('common.select_project'); ?></a>
    </div>
<?php endif; ?>

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
            <?php if (Auth::hasPermission('module:databases', 'view_tables') || Auth::hasPermission('module:databases', 'create_db')): ?>
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

            <!-- Media Library Module -->
            <?php if (Auth::hasPermission('module:media', 'view_files')): ?>
                <a href="<?php echo $baseUrl; ?>admin/media"
                    class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                    <div
                        class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-p-title mb-2"><?php echo Lang::get('media.explorer'); ?></h3>
                    <p class="text-xs text-p-muted mb-6 leading-relaxed">
                        <?php echo Lang::get('media.system'); ?>
                    </p>
                    <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                        <?php echo Lang::get('dashboard.enter'); ?> <span>&rarr;</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Users Module -->
            <?php if (Auth::hasPermission('module:users', 'view_users')): ?>
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

<div class="mt-16 pt-16 border-t border-glass-border">
    <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-8 flex items-center gap-3 justify-center">
        <span class="w-12 h-[1px] bg-slate-800"></span> <?php echo Lang::get('common.system'); ?> <span
            class="w-12 h-[1px] bg-slate-800"></span>
    </h2>
    <div class="max-w-2xl mx-auto flex flex-wrap justify-center gap-6">
        <button onclick="showSystemInfo()"
            class="px-8 py-4 rounded-xl border border-primary/30 text-primary text-[10px] font-black uppercase tracking-[0.2em] hover:bg-primary hover:text-dark transition-all duration-300">
            📊 <?php echo Lang::get('dashboard.system_info'); ?>
        </button>
        <?php if (Auth::isAdmin()): ?>
            <?php $isDev = Auth::isDevMode(); ?>
            <button onclick="toggleDevMode()" id="btn-dev-mode"
                class="px-8 py-4 rounded-xl border <?php echo $isDev ? 'border-amber-500 bg-amber-500 text-white' : 'border-amber-500/30 text-amber-500'; ?> text-[10px] font-black uppercase tracking-[0.2em] hover:bg-amber-500 hover:text-white transition-all duration-300">
                <?php echo Lang::get('dashboard.dev_mode'); ?>: <?php echo $isDev ? 'ON' : 'OFF'; ?>
            </button>
            <button onclick="triggerResetSystem()"
                class="px-8 py-4 rounded-xl border border-red-500/30 text-red-500 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-red-500 hover:text-white transition-all duration-300">
                ⚡ <?php echo Lang::get('dashboard.reset_system'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleDevMode() {
        fetch('<?php echo $baseUrl; ?>admin/system/dev-mode', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('btn-dev-mode');
                    const isActive = data.dev_mode === 'on';
                    btn.className = `px-8 py-4 rounded-xl border ${isActive ? 'border-amber-500 bg-amber-500 text-white' : 'border-amber-500/30 text-amber-500'} text-[10px] font-black uppercase tracking-[0.2em] hover:bg-amber-500 hover:text-white transition-all duration-300`;
                    btn.innerHTML = `🛠️ DEV MODE: ${isActive ? 'ON' : 'OFF'}`;

                    // Show a quick notification or just reload to see the change in layout
                    showModal({
                        title: '<?php echo addslashes(Lang::get('dashboard.dev_mode_modal_title')); ?>',
                        message: '<?php echo addslashes(Lang::get('dashboard.dev_mode_msg')); ?>'.replace(':status', isActive ? '<?php echo addslashes(Lang::get('dashboard.dev_mode_on')); ?>' : '<?php echo addslashes(Lang::get('dashboard.dev_mode_off')); ?>'),
                        type: 'success',
                        onConfirm: () => window.location.reload()
                    });
                }
            });
    }

    function showSystemInfo() {
        fetch('<?php echo $baseUrl; ?>admin/system/info')
            .then(res => res.json())
            .then(data => {
                const helpMap = {
                    'upload_max_filesize': '<?php echo addslashes(Lang::get('dashboard.help_upload')); ?>',
                    'post_max_size': '<?php echo addslashes(Lang::get('dashboard.help_post')); ?>',
                    'memory_limit': '<?php echo addslashes(Lang::get('dashboard.help_memory')); ?>',
                    'max_execution_time': '<?php echo addslashes(Lang::get('dashboard.help_time')); ?>',
                    'max_input_vars': '<?php echo addslashes(Lang::get('dashboard.help_vars')); ?>'
                };

                let html = `
                        <div class="mb-8 p-6 rounded-2xl bg-primary/5 border border-primary/10">
                            <p class="text-sm md:text-base text-slate-200 leading-relaxed font-medium">
                                <?php echo addslashes(Lang::get('dashboard.system_intro')); ?>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left mb-10">
                    `;

                for (const [key, value] of Object.entries(data)) {
                    const label = key.replace(/_/g, ' ').toUpperCase();
                    const help = helpMap[key] || '';
                    let extraWarning = '';

                    if (key === 'upload_max_filesize') {
                        const warningText = '<?php echo addslashes(Lang::get('dashboard.file_size_warning')); ?>'.replace(':value', value);
                        extraWarning = `<div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl"><p class="text-xs font-bold text-amber-500 uppercase tracking-tighter italic">${warningText}</p></div>`;
                    }

                    html += `
                            <div class="bg-white/5 p-5 rounded-2xl border border-white/5 hover:border-primary/20 transition-all group">
                                <span class="block text-[10px] font-black text-primary tracking-widest mb-2">${label}</span>
                                <span class="text-lg font-black text-white block mb-2">${value}</span>
                                ${help ? `<p class="text-xs text-p-muted font-medium italic opacity-70 group-hover:opacity-100 transition-opacity">${help}</p>` : ''}
                                ${extraWarning}
                            </div>
                        `;
                }

                html += `
                        </div>
                        <div class="p-8 rounded-3xl bg-black/40 border border-white/5">
                            <h4 class="text-xs font-black text-white uppercase tracking-widest mb-4 flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 
                                <?php echo addslashes(Lang::get('dashboard.needs_more_capacity')); ?>
                            </h4>
                            <p class="text-sm text-p-muted leading-relaxed mb-6">
                                <?php echo addslashes(Lang::get('dashboard.modify_values_text')); ?>
                            </p>
                            <div class="p-4 bg-emerald-500/5 border border-emerald-500/10 rounded-2xl">
                                <p class="text-xs md:text-sm text-p-muted leading-relaxed">
                                    <?php echo addslashes(Lang::get('dashboard.recommendation')); ?>
                                </p>
                            </div>
                        </div>
                    `;

                showModal({
                    title: '<?php echo addslashes(Lang::get('dashboard.server_config')); ?>',
                    message: '',
                    type: 'modal',
                    typeLabel: '<?php echo addslashes(Lang::get('dashboard.system_env_nodes')); ?>',
                    maxWidth: 'max-w-4xl'
                });

                const msgContainer = document.getElementById('modal-message');
                msgContainer.innerHTML = html;
                msgContainer.classList.remove('text-slate-400'); // Let our styled HTML shine
            });
    }
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