<?php use App\Core\Auth; $baseUrl = Auth::getBaseUrl(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Editor - Api-Admin</title>
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
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn-primary { @apply bg-primary text-dark font-black py-3 px-10 rounded-xl transition-all hover:shadow-[0_0_20px_rgba(56,189,248,0.3)] active:scale-95; }
            .glass-card { @apply bg-glass backdrop-blur-xl border border-glass-border rounded-2xl p-8; }
            .form-input { @apply w-full bg-black/40 border-2 border-glass-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary/50 transition-all font-medium; }
            .section-title { @apply text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-6 flex items-center gap-3; }
            .perm-grid { @apply grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3; }
            .perm-item { @apply flex items-center gap-3 p-3 bg-white/5 border border-glass-border rounded-xl hover:border-primary/30 transition-all cursor-pointer; }
            .checkbox-custom { @apply w-5 h-5 rounded bg-black/40 text-primary border-glass-border focus:ring-primary/20; }
        }
    </style>
    <?php include __DIR__ . '/../../partials/theme_engine.php'; ?>
</head>
<body class="bg-dark text-slate-200 min-h-screen font-sans border-t-4 border-primary pb-20">
    <nav class="fixed top-0 w-full h-16 bg-dark/80 backdrop-blur-lg border-b border-glass-border z-50 flex items-center justify-between px-8">
        <div class="flex items-center gap-4 text-xs font-medium tracking-tight">
            <a href="<?php echo $baseUrl; ?>" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">DASHBOARD</a>
            <span class="text-slate-700">/</span>
            <a href="<?php echo $baseUrl; ?>admin/users" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">TEAM MANAGEMENT</a>
            <span class="text-slate-700">/</span>
            <a href="<?php echo $baseUrl; ?>admin/roles" class="text-slate-500 hover:text-primary transition-colors uppercase font-black tracking-widest text-[9px]">ROLES & POLICIES</a>
            <span class="text-slate-700">/</span>
            <span class="text-slate-200 font-black uppercase tracking-widest text-[9px] underline decoration-primary decoration-2 underline-offset-4"><?php echo $role ? 'REFINE POLICY' : 'DESIGN POLICY'; ?></span>
        </div>
        <a href="<?php echo $baseUrl; ?>admin/roles" class="btn-outline text-[10px] uppercase tracking-widest">&larr; ABORT</a>
            <?php include __DIR__ . '/../../partials/theme_toggle.php'; ?>
    </nav>

    <main class="container mx-auto pt-24 px-6 max-w-6xl">
        <header class="mb-12">
            <h1 class="text-4xl font-black text-white italic tracking-tighter mb-2">Policy <span class="text-primary">Architect</span></h1>
            <p class="text-slate-500 font-medium">Define high-granularity permissions for system nodes.</p>
        </header>

        <form action="<?php echo $baseUrl; ?>admin/roles/save" method="POST" class="space-y-10">
            <input type="hidden" name="id" value="<?php echo $role['id'] ?? ''; ?>">
            
            <section class="glass-card">
                <div class="grid md:grid-cols-2 gap-8 items-end">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Policy Title</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($role['name'] ?? ''); ?>" placeholder="e.g. Content Editor" required class="form-input">
                    </div>
                    <div class="pb-3">
                        <label class="flex items-center gap-4 cursor-pointer p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <input type="checkbox" name="is_admin" value="1" onchange="toggleMaster()" <?php echo ($role['permissions']['all'] ?? false) ? 'checked' : ''; ?> id="master-toggle" class="checkbox-custom">
                            <span class="text-xs font-black uppercase tracking-widest text-primary">Master Administrator (Full Access)</span>
                        </label>
                    </div>
                </div>
            </section>

            <div id="granular-perms" class="<?php echo ($role['permissions']['all'] ?? false) ? 'hidden' : ''; ?> space-y-10">
                <!-- Module Permissions -->
                <section class="glass-card">
                    <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-primary translate-y-[-1px]"></span> Global Module Access</h3>
                    <div class="space-y-6">
                        <div class="perm-grid">
                            <label class="perm-item">
                                <input type="checkbox" name="modules[databases][]" value="view" <?php echo in_array('view', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Databases Hub</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[databases][]" value="create" <?php echo in_array('create', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Create Database</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[databases][]" value="delete" <?php echo in_array('delete', $role['permissions']['modules']['databases'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Delete Database</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[api][]" value="view_keys" <?php echo in_array('view_keys', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">View API Keys</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[api][]" value="manage_keys" <?php echo in_array('manage_keys', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Manage Keys</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[api][]" value="view_docs" <?php echo in_array('view_docs', $role['permissions']['modules']['api'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">View API Docs</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[users][]" value="view" <?php echo in_array('view', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">View Team</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[users][]" value="manage" <?php echo in_array('manage', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Manage Users</span>
                            </label>
                            <label class="perm-item">
                                <input type="checkbox" name="modules[users][]" value="manage_roles" <?php echo in_array('manage_roles', $role['permissions']['modules']['users'] ?? []) ? 'checked' : ''; ?> class="checkbox-custom">
                                <span class="text-[11px] font-bold">Manage Roles</span>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Database Specific Permissions -->
                <section class="glass-card">
                    <h3 class="section-title"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 translate-y-[-1px]"></span> Node-Specific Granularity (Table & Data Control)</h3>
                    <div class="space-y-6">
                        <?php foreach ($databases as $db): 
                            $dbPerms = $role['permissions']['databases'][$db['id']] ?? [];
                        ?>
                            <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-xs font-black">#<?php echo $db['id']; ?></div>
                                    <h4 class="text-sm font-black text-white uppercase"><?php echo htmlspecialchars($db['name']); ?></h4>
                                    <label class="flex items-center gap-2 cursor-pointer ml-auto bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                                        <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]" value="view" <?php echo in_array('view', $dbPerms) ? 'checked' : ''; ?> class="checkbox-custom !w-4 !h-4">
                                        <span class="text-[9px] font-black uppercase text-emerald-400">ENABLE ACCESS</span>
                                    </label>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Table Management -->
                                    <div class="space-y-3">
                                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest pl-1">Structural Permissions</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php 
                                            $struct = ['view_tables' => 'See Tables', 'create_table' => 'Create Table', 'delete_table' => 'Drop Table', 'manage_fields' => 'Fields Config'];
                                            foreach ($struct as $k => $v): ?>
                                                <label class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all cursor-pointer">
                                                    <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]" value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?> class="checkbox-custom">
                                                    <span class="text-[10px] font-bold text-slate-300"><?php echo $v; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <!-- Data Management -->
                                    <div class="space-y-3">
                                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest pl-1">Content Permissions (CRUD)</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php 
                                            $content = ['crud_view' => 'Read Data', 'crud_create' => 'Insert Data', 'crud_edit' => 'Update Data', 'crud_delete' => 'Delete Data'];
                                            foreach ($content as $k => $v): ?>
                                                <label class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all cursor-pointer">
                                                    <input type="checkbox" name="db_perms[<?php echo $db['id']; ?>][]" value="<?php echo $k; ?>" <?php echo in_array($k, $dbPerms) ? 'checked' : ''; ?> class="checkbox-custom">
                                                    <span class="text-[10px] font-bold text-slate-300"><?php echo $v; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="flex justify-center">
                <button type="submit" class="btn-primary">Safe Access Policy</button>
            </div>
        </form>
    </main>

    <script>
        function toggleMaster() {
            const isMaster = document.getElementById('master-toggle').checked;
            document.getElementById('granular-perms').classList.toggle('hidden', isMaster);
        }
    </script>
</body>
</html>
