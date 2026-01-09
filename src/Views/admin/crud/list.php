<?php use App\Core\Auth;
use App\Core\Lang; ?>
<style>
    .custom-scrollbar::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(56, 189, 248, 0.2);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(56, 189, 248, 0.4);
    }

    .sticky-col {
        position: sticky;
        right: 0;
        z-index: 10;
        background-color: var(--p-card) !important;
        backdrop-filter: blur(12px);
        border-left: 1px solid var(--p-border);
        box-shadow: -10px 0 20px -10px rgba(0, 0, 0, 0.1);
    }

    .hover-preview-container {
        position: relative;
        display: inline-block;
    }

    .hover-preview-content {
        display: none;
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        z-index: 50;
        background: rgba(15, 23, 42, 0.98);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        padding: 12px;
        border-radius: 12px;
        width: max-content;
        max-width: 320px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.7);
        pointer-events: none;
        font-size: 12px;
        line-height: 1.5;
    }

    .hover-preview-container:hover .hover-preview-content {
        display: block;
        animation: scaleIn 0.2s ease-out;
    }

    .img-hover-preview {
        display: none;
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        z-index: 50;
        background: #0b1120;
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 4px;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.7);
        pointer-events: none;
    }

    .hover-preview-container:hover .img-hover-preview {
        display: block;
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: translateX(-50%) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateX(-50%) scale(1);
        }
    }
</style>

<header class="flex flex-col md:flex-row justify-between items-end gap-6 mb-10">
    <div>
        <div class="flex items-center gap-4 mb-2">
            <div
                class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:rotate-12 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
            <h1 class="text-4xl font-black text-p-title italic tracking-tighter uppercase">
                <?php echo str_replace(':table', htmlspecialchars(ucfirst($ctx['table'])), Lang::get('crud_list.title')); ?>
            </h1>
        </div>
        <p class="text-p-muted font-medium tracking-tight">
            <?php echo str_replace(':db', '<b>' . htmlspecialchars($ctx['database']['name']) . '</b>', Lang::get('crud_list.subtitle')); ?>
        </p>
    </div>
    <div class="flex flex-wrap gap-4">
        <a href="<?php echo $baseUrl; ?>admin/databases/view?id=<?php echo $ctx['db_id']; ?>"
            class="btn-primary !bg-slate-800 !underline !text-slate-300">
            &larr; <?php echo Lang::get('common.back'); ?>
        </a>
        <?php if (App\Core\Auth::hasPermission('module:databases.edit_table')): ?>
            <a href="<?php echo $baseUrl; ?>admin/databases/fields?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
                class="btn-primary !bg-slate-800 !text-slate-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <?php echo Lang::get('fields.title'); ?>
            </a>
        <?php endif; ?>
        <a href="<?php echo $baseUrl; ?>admin/api/docs?db_id=<?php echo $ctx['db_id']; ?>#table-<?php echo $ctx['table']; ?>"
            class="btn-primary !bg-emerald-500/10 !text-emerald-400 border border-emerald-500/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <?php echo Lang::get('tables.api_docs'); ?>
        </a>
        <a href="<?php echo $baseUrl; ?>admin/crud/export?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
            class="btn-primary !bg-emerald-600/20 !text-emerald-400 border border-emerald-500/30 flex items-center gap-2 hover:!bg-emerald-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Exportar Excel
        </a>
        <?php if (App\Core\Auth::hasPermission('module:databases.crud_create')): ?>
            <a href="<?php echo $baseUrl; ?>admin/crud/new?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>"
                class="btn-primary">
                <?php echo Lang::get('crud_list.new'); ?>
            </a>
        <?php endif; ?>
    </div>
</header>

<section class="glass-card !p-0 overflow-hidden shadow-2xl">
    <div class="px-8 py-5 bg-white/[0.03] border-b border-glass-border flex justify-between items-center">
        <h3 class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
            <?php echo Lang::get('crud_list.matrix'); ?>
        </h3>
        <span
            class="text-[10px] font-black bg-primary/10 text-primary px-3 py-1 rounded-full border border-primary/20 tracking-widest">
            <?php echo str_replace(':count', count($records), Lang::get('crud_list.active_records')); ?>
        </span>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="bg-black/5 dark:bg-black/40 text-[10px] font-black text-p-muted uppercase tracking-widest border-b border-p-border">
                    <?php foreach ($ctx['fields'] as $field): ?>
                        <?php if ($field['is_visible']): ?>
                            <th class="px-8 py-5"><?php echo htmlspecialchars($field['field_name']); ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <th class="px-8 py-5 text-right sticky-col !z-20">
                        <?php echo Lang::get('crud_list.ops'); ?>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="<?php echo count($ctx['fields']) + 1; ?>" class="px-8 py-32 text-center">
                            <div class="flex flex-col items-center opacity-20">
                                <svg class="w-16 h-16 mb-6 text-p-muted animate-pulse" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <p class="text-sm font-black uppercase tracking-[0.4em]">
                                    <?php echo Lang::get('crud_list.no_signal'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <?php foreach ($ctx['fields'] as $field): ?>
                                <?php if ($field['is_visible']): ?>
                                    <td class="px-8 py-6">
                                        <?php
                                        $val = $row[$field['field_name']] ?? '';
                                        if ($field['view_type'] === 'image' && !empty($val)):
                                            $imgUrl = (strpos($val, 'http') === 0) ? $val : $baseUrl . $val;
                                            ?>
                                            <div class="hover-preview-container">
                                                <div
                                                    class="relative w-12 h-12 rounded-xl overflow-hidden border border-glass-border hover:border-primary transition-all cursor-zoom-in">
                                                    <img src="<?php echo $imgUrl; ?>" class="w-full h-full object-cover">
                                                </div>
                                                <div class="img-hover-preview">
                                                    <img src="<?php echo $imgUrl; ?>" class="max-w-[280px] rounded-lg shadow-2xl">
                                                </div>
                                            </div>
                                        <?php elseif ($field['view_type'] === 'gallery' && !empty($val)):
                                            $images = explode(',', $val);
                                            ?>
                                            <div class="flex -space-x-4">
                                                <?php foreach (array_slice($images, 0, 3) as $img):
                                                    $imgUrl = (strpos($img, 'http') === 0) ? $img : $baseUrl . $img;
                                                    ?>
                                                    <div
                                                        class="relative w-10 h-10 rounded-lg overflow-hidden border-2 border-slate-900 shadow-xl group/gal">
                                                        <img src="<?php echo $imgUrl; ?>"
                                                            class="w-full h-full object-cover hover:scale-110 transition-transform">
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($images) > 3): ?>
                                                    <div
                                                        class="w-10 h-10 rounded-lg bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-[10px] font-black text-primary">
                                                        +<?php echo count($images) - 3; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($field['view_type'] === 'boolean'): ?>
                                            <span
                                                class="inline-flex px-3 py-1 rounded-full text-[9px] font-black border <?php echo $val ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'; ?> uppercase tracking-widest">
                                                <?php echo $val ? Lang::get('common.active') : Lang::get('common.offline'); ?>
                                            </span>
                                        <?php elseif ($field['view_type'] === 'datetime' && !empty($val)): ?>
                                            <div class="flex flex-col gap-1">
                                                <div class="flex items-center gap-2 text-p-title font-bold text-[11px]">
                                                    <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                    <?php echo date('d/m/Y', strtotime($val)); ?>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2 text-p-muted font-black text-[9px] uppercase tracking-tighter">
                                                    <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?php echo date('H:i:s', strtotime($val)); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="hover-preview-container">
                                                <div
                                                    class="text-[13px] font-medium text-p-muted dark:text-slate-300 truncate max-w-[200px]">
                                                    <?php echo mb_strimwidth(strip_tags((string) $val), 0, 50, "..."); ?>
                                                </div>
                                                <?php if (strlen(strip_tags((string) $val)) > 50): ?>
                                                    <div class="hover-preview-content">
                                                        <div
                                                            class="text-[9px] font-black text-primary uppercase mb-2 tracking-tighter opacity-50">
                                                            <?php echo Lang::get('crud_list.overflow'); ?>
                                                        </div>
                                                        <?php echo nl2br(htmlspecialchars(strip_tags((string) $val))); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td class="px-8 py-6 sticky-col group-hover:bg-p-bg/50 transition-colors">
                                <div class="flex justify-end gap-3 opacity-60 group-hover:opacity-100 transition-all">
                                    <?php if (App\Core\Auth::hasPermission('module:databases.crud_update')): ?>
                                        <a href="<?php echo $baseUrl; ?>admin/crud/edit?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>&id=<?php echo $row['id']; ?>"
                                            class="p-2 bg-p-bg dark:bg-white/5 rounded-lg text-p-muted hover:text-primary hover:bg-primary/10 transition-all shadow-sm hover:shadow-md"
                                            title="<?php echo Lang::get('common.edit'); ?>"><svg class="w-4 h-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg></a>
                                    <?php endif; ?>

                                    <?php if (App\Core\Auth::hasPermission('module:databases.crud_delete')): ?>
                                        <button onclick="confirmRecordDelete(<?php echo $row['id']; ?>)"
                                            class="p-2 bg-p-bg dark:bg-white/5 rounded-lg text-p-muted hover:text-red-500 hover:bg-red-500/10 transition-all shadow-sm hover:shadow-md"
                                            title="<?php echo Lang::get('common.delete'); ?>"><svg class="w-4 h-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    function confirmRecordDelete(id) {
        showModal({
            title: '<?php echo Lang::get('crud_list.delete_confirm_title'); ?>',
            message: '<?php echo Lang::get('crud_list.delete_confirm_msg'); ?>',
            type: 'confirm',
            typeLabel: '<?php echo Lang::get('crud_list.delete_confirm_btn'); ?>',
            onConfirm: () => {
                window.location.href = `<?php echo $baseUrl; ?>admin/crud/delete?db_id=<?php echo $ctx['db_id']; ?>&table=<?php echo $ctx['table']; ?>&id=${id}`;
            }
        });
    }
</script>