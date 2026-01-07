<header class="mb-12 text-center">
    <h1 class="text-4xl font-black text-white italic tracking-tighter mb-2"><?php echo $id ? 'Verify' : 'Manifest'; ?>
        <span class="text-primary">Human Node</span>
    </h1>
    <p class="text-slate-500 font-medium">Connect a new node to the cluster with specific access policies.</p>
</header>

<form action="<?php echo $baseUrl; ?>admin/users/save" method="POST" class="space-y-8 max-w-4xl mx-auto">
    <input type="hidden" name="id" value="<?php echo $user['id'] ?? ''; ?>">

    <section class="glass-card space-y-6">
        <div>
            <label class="form-label">Username (Node ID)</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required
                class="form-input" placeholder="e.g. neuro_nexus">
        </div>
        <div>
            <label class="form-label">Credential Token (Password)
                <?php echo $user ? '<span class="text-amber-500/50 italic">(Leave empty to maintain current)</span>' : ''; ?></label>
            <input type="password" name="password" <?php echo $user ? '' : 'required'; ?> class="form-input"
                placeholder="••••••••">
        </div>
        <div>
            <label class="form-label">Assigned Access Policy (Role)</label>
            <select name="role_id" required class="form-input">
                <?php foreach ($roles as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo ($user['role_id'] ?? '') == $r['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pt-4 flex items-center justify-between">
            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Node Status</span>
            <label class="flex items-center gap-4 cursor-pointer">
                <input type="checkbox" name="status" value="1" <?php echo ($user['status'] ?? 1) ? 'checked' : ''; ?>
                    class="w-6 h-6 rounded bg-black/40 text-primary border-glass-border">
                <span class="text-xs font-black uppercase tracking-widest">Active</span>
            </label>
        </div>
    </section>

    <div class="flex justify-center pt-8 gap-4">
        <a href="<?php echo $baseUrl; ?>admin/users" class="btn-primary !bg-slate-800 !text-slate-300">ABORT</a>
        <button type="submit" class="btn-primary">Connect Node</button>
    </div>
</form>