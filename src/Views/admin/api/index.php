<style type="text/tailwindcss">
    .input-dark {
        @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-2 text-white focus:outline-none focus:border-primary/50 transition-all font-medium;
    }
</style>

<header class="mb-12">
    <h1 class="text-5xl font-black text-white italic tracking-tighter mb-2">API <span class="text-primary">Master</span>
        Control</h1>
    <p class="text-slate-500 font-medium">Generate keys and explore automated REST endpoints for your databases.</p>
</header>

<div class="grid lg:grid-cols-2 gap-8">
    <!-- Left: API Keys -->
    <section class="glass-card flex flex-col h-full">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-black text-white uppercase tracking-tight">Access Tokens</h2>
            <span
                class="text-[10px] bg-primary/10 text-primary border border-primary/20 px-3 py-1 rounded-full font-bold uppercase"><?php echo count($keys); ?>
                ACTIVE</span>
        </div>

        <form action="<?php echo $baseUrl; ?>admin/api/keys/create" method="POST" class="mb-8 flex gap-2">
            <input type="text" name="name" placeholder="Friendly name for this key..." required
                class="input-dark flex-1">
            <button type="submit" class="btn-primary">Generate +</button>
        </form>

        <div class="flex-1 space-y-4">
            <?php if (empty($keys)): ?>
                <div class="py-12 text-center opacity-30">
                    <span class="text-4xl block mb-2">🔑</span>
                    <p class="text-xs font-black uppercase tracking-widest">No keys generated yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($keys as $key): ?>
                    <div class="bg-white/5 border border-glass-border p-4 rounded-xl group">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-slate-300"><?php echo htmlspecialchars($key['name']); ?></h3>
                            <button
                                onclick="confirmRevokeKey(<?php echo $key['id']; ?>, '<?php echo htmlspecialchars($key['name']); ?>')"
                                class="text-red-500/50 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center gap-2 bg-black/40 p-2 rounded-lg border border-white/5">
                            <code
                                class="text-[10px] text-primary break-all flex-1 font-mono"><?php echo $key['key_value']; ?></code>
                            <button onclick="navigator.clipboard.writeText('<?php echo $key['key_value']; ?>')"
                                class="text-slate-500 hover:text-white transition-colors" title="Copy to clipboard">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Right: Database Documentation Selection -->
    <section class="glass-card">
        <h2 class="text-xl font-black text-white uppercase tracking-tight mb-8">Documentation Explorer</h2>
        <div class="grid gap-4">
            <?php foreach ($databases as $db): ?>
                <a href="<?php echo $baseUrl; ?>admin/api/docs?db_id=<?php echo $db['id']; ?>"
                    class="group bg-white/5 border border-glass-border p-6 rounded-2xl hover:border-primary/50 transition-all flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-primary transition-colors">
                            <?php echo htmlspecialchars($db['name']); ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 uppercase font-black tracking-widest">Connect to internal
                            schema</p>
                    </div>
                    <span class="text-2xl group-hover:translate-x-1 transition-transform">📘</span>
                </a>
            <?php endforeach; ?>
            <?php if (empty($databases)): ?>
                <div class="py-12 text-center opacity-30">
                    <span class="text-4xl block mb-2">📁</span>
                    <p class="text-xs font-black uppercase tracking-widest">No databases detected</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="mt-8 glass-card border-emerald-500/20">
    <h3 class="text-emerald-400 font-black text-[10px] uppercase tracking-[0.3em] mb-4">Quick Integration Guide</h3>
    <div class="prose prose-invert max-w-none text-sm text-slate-400 leading-relaxed font-medium">
        <p>To use this API, include your generated key in the headers of every request:</p>
        <div class="bg-black/40 p-4 rounded-xl border border-white/5 font-mono text-primary text-xs mt-3">
            X-API-KEY: your_generated_key_here
        </div>
        <p class="mt-4">All responses are returned in standard <b class="text-white">JSON</b> format with appropriate
            HTTP status codes.</p>
    </div>
</section>

<script>
    function confirmRevokeKey(id, name) {
        showModal({
            title: 'Credential Revocation',
            message: `Are you certain you want to revoke the access token '${name}'? Any external applications using this key will be immediately disconnected.`,
            type: 'confirm',
            typeLabel: 'NODAL PURGE',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/api/keys/delete?id=${id}`;
            }
        });
    }
</script>