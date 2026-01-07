<?php use App\Core\Auth; ?>
<style type="text/tailwindcss">
    .field-row {
        @apply bg-white/[0.02] border border-glass-border rounded-2xl p-6 mb-6 transition-all hover:bg-white/[0.04] hover:border-primary/20;
    }

    .custom-select {
        @apply bg-black/40 border border-glass-border rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50 transition-all cursor-pointer;
    }

    .checkbox-custom {
        @apply w-4 h-4 rounded border-glass-border bg-black/40 text-primary focus:ring-primary/20 cursor-pointer;
    }
</style>

<header class="mb-12">
    <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Schema Architect</h1>
    <p class="text-slate-500 mt-2">Design UI components and validate data types for <b
            class="text-primary"><?php echo htmlspecialchars($table_name); ?></b> collection.</p>
</header>

<div class="flex flex-col lg:flex-row gap-8 items-start">
    <!-- Left: Configurations -->
    <div class="w-full lg:flex-1">
        <section class="glass-card">
            <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-8 flex items-center gap-4">
                Field Matrix <span class="h-[1px] flex-1 bg-glass-border"></span>
            </h3>

            <?php foreach ($configFields as $field): ?>
                <form action="<?php echo $baseUrl; ?>admin/databases/fields/update" method="POST" class="field-row">
                    <input type="hidden" name="config_id" value="<?php echo $field['id']; ?>">

                    <div
                        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-6 pb-4 border-b border-white/5">
                        <div class="flex items-center gap-4">
                            <div
                                class="bg-primary/10 text-primary px-3 py-1 rounded-lg font-mono text-xs border border-primary/20">
                                <?php echo $field['data_type']; ?>
                            </div>
                            <h4 class="text-xl font-bold text-white tracking-tight">
                                <?php echo htmlspecialchars($field['field_name']); ?>
                            </h4>
                            <?php if ($field['field_name'] == 'id' || $field['field_name'] == 'fecha_de_creacion' || $field['field_name'] == 'fecha_edicion'): ?>
                                <span
                                    class="bg-white/10 text-[10px] text-slate-400 px-2 py-0.5 rounded uppercase font-black tracking-widest">System
                                    Field</span>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary !py-1.5 !px-4 !text-[11px] uppercase tracking-wider">Sync
                            Changes</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">UI
                                Representation</label>
                            <select name="view_type" class="custom-select w-full" <?php echo ($field['field_name'] == 'id' || $field['field_name'] == 'fecha_de_creacion' || $field['field_name'] == 'fecha_edicion') ? 'disabled' : ''; ?>>
                                <option value="text" <?php echo $field['view_type'] == 'text' ? 'selected' : ''; ?>>Simple
                                    Input Field</option>
                                <option value="textarea" <?php echo $field['view_type'] == 'textarea' ? 'selected' : ''; ?>>
                                    Multi-line Text</option>
                                <option value="wysiwyg" <?php echo $field['view_type'] == 'wysiwyg' ? 'selected' : ''; ?>>Rich
                                    HTML (WYSIWYG)</option>
                                <option value="image" <?php echo $field['view_type'] == 'image' ? 'selected' : ''; ?>>Media:
                                    Smart Image</option>
                                <option value="gallery" <?php echo $field['view_type'] == 'gallery' ? 'selected' : ''; ?>>
                                    Media: Smart Gallery</option>
                                <option value="file" <?php echo $field['view_type'] == 'file' ? 'selected' : ''; ?>>Media:
                                    Document/Binary</option>
                                <option value="boolean" <?php echo $field['view_type'] == 'boolean' ? 'selected' : ''; ?>>
                                    Status Toggle (Switcher)</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Constraints</label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_required" class="checkbox-custom" <?php echo ($field['is_required'] ?? false) ? 'checked' : ''; ?>>
                                <span
                                    class="text-[10px] font-bold text-slate-400 group-hover:text-white transition-colors uppercase">Required</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_visible" class="checkbox-custom" <?php echo ($field['is_visible'] ?? false) ? 'checked' : ''; ?>>
                                <span
                                    class="text-[10px] font-bold text-slate-400 group-hover:text-white transition-colors uppercase">Visible</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_editable" class="checkbox-custom" <?php echo ($field['is_editable'] ?? false) ? 'checked' : ''; ?>     <?php echo ($field['field_name'] == 'id' || $field['field_name'] == 'fecha_de_creacion' || $field['field_name'] == 'fecha_edicion') ? 'disabled' : ''; ?>>
                                <span
                                    class="text-[10px] font-bold text-slate-400 group-hover:text-white transition-colors uppercase">Editable</span>
                            </label>
                        </div>

                        <div class="lg:col-span-2 bg-black/20 p-4 rounded-xl border border-white/5">
                            <label class="flex items-center gap-2 mb-4 cursor-pointer group">
                                <input type="checkbox" name="is_foreign_key" class="checkbox-custom" <?php echo ($field['is_foreign_key'] ?? false) ? 'checked' : ''; ?>>
                                <span class="text-[10px] font-black text-primary uppercase tracking-widest">Foreign Key
                                    Relationship</span>
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase mb-2">Target
                                        Table</label>
                                    <select name="related_table" class="custom-select w-full !py-1 text-xs">
                                        <option value="">-- Select Table --</option>
                                        <?php foreach ($allTables as $t): ?>
                                            <option value="<?php echo $t; ?>" <?php echo $field['related_table'] == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase mb-2">Display
                                        Field</label>
                                    <input type="text" name="related_field"
                                        value="<?php echo htmlspecialchars($field['related_field'] ?? ''); ?>"
                                        placeholder="Ej: nombre"
                                        class="w-full bg-black/40 border border-glass-border rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-primary/50">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        </section>
    </div>

    <!-- Right: Add Field -->
    <aside class="w-full lg:w-[400px] sticky top-24">
        <section class="glass-card border-t-4 border-t-primary shadow-2xl">
            <h3 class="text-lg font-bold text-white mb-8 border-b border-glass-border pb-4">Inject New Field</h3>
            <form action="<?php echo $baseUrl; ?>admin/databases/fields/add" method="POST" class="space-y-6">
                <input type="hidden" name="db_id" value="<?php echo $database['id']; ?>">
                <input type="hidden" name="table_name" value="<?php echo $table_name; ?>">

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Technical
                        ID (SQL)</label>
                    <input type="text" name="field_name" placeholder="Ej: status" required
                        class="w-full bg-black/40 border border-glass-border rounded-xl px-4 py-3 text-white focus:border-primary/50 transition-all font-mono text-sm">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Core Data
                        Type</label>
                    <select name="data_type" class="custom-select w-full !py-3">
                        <option value="INTEGER">INTEGER (Recommended for Status/IDs)</option>
                        <option value="TEXT" selected>TEXT (Versatile String)</option>
                        <option value="REAL">REAL (Precision Decimals)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Initial
                        UI Component</label>
                    <select name="view_type" class="custom-select w-full !py-3">
                        <option value="text">Standard Text</option>
                        <option value="boolean">Status Toggle (Switcher)</option>
                        <option value="textarea">Large Text Area</option>
                        <option value="wysiwyg">Rich HTML Hub</option>
                        <option value="image">Media Imager</option>
                        <option value="gallery">Gallery Hub</option>
                        <option value="file">Binary Attacher</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary w-full !py-4 shadow-lg shadow-primary/20">Commit
                    Extension</button>
            </form>
        </section>
    </aside>
</div>