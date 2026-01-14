@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-emerald-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.backups') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            Copias de Seguridad
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            Gestiona backups de la base de datos del sistema
        </p>
    </header>

    <!-- Create Backup Button -->
    <div class="mb-12 flex justify-center">
        <form method="POST" action="<?= $baseUrl ?>admin/system-database/backup/create" onsubmit="return confirm('¿Crear un nuevo backup del sistema?');">
            <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
            <button type="submit" class="btn-primary !px-8 !py-4 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span class="font-black uppercase tracking-wider">{{ \App\Core\Lang::get('system_database.create_backup') }}</span>
            </button>
        </form>
    </div>

    <!-- Backups List -->
    <div class="glass-card !p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                        Archivo</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                        {{ \App\Core\Lang::get('system_database.backup_size') }}</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                        {{ \App\Core\Lang::get('system_database.backup_date') }}</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">
                        {{ \App\Core\Lang::get('system_database.backup_type') }}</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest text-right">
                        Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($backups as $backup)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center text-emerald-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                </div>
                                <span class="text-xs font-bold text-p-title">{{ $backup['filename'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-xs font-black text-p-title">{{ $backup['size'] }}</span>
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-[10px] font-black text-p-muted uppercase italic">
                                {{ date('M d, Y H:i', strtotime($backup['date'])) }}
                            </span>
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-2 py-0.5 bg-{{ $backup['type'] == 'manual' ? 'blue' : 'emerald' }}-500/20 text-{{ $backup['type'] == 'manual' ? 'blue' : 'emerald' }}-500 rounded text-[9px] font-black uppercase tracking-widest border border-{{ $backup['type'] == 'manual' ? 'blue' : 'emerald' }}-500/30">
                                {{ \App\Core\Lang::get('system_database.' . $backup['type']) }}
                            </span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Download -->
                                <a href="<?= $baseUrl ?>admin/system-database/backup/download?file=<?= urlencode($backup['filename']) ?>" 
                                   class="p-2 bg-blue-500/10 text-blue-500 rounded-lg hover:bg-blue-500/20 transition-colors"
                                   title="{{ \App\Core\Lang::get('system_database.download_backup') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>

                                <!-- Restore -->
                                <form method="POST" action="<?= $baseUrl ?>admin/system-database/backup/restore" class="inline" onsubmit="return confirm('⚠️ ADVERTENCIA: Esto restaurará la base de datos del sistema. Se creará un backup de seguridad antes de restaurar. ¿Continuar?');">
                                    <input type="hidden" name="_token" value="<?= \App\Core\Csrf::getToken() ?>">
                                    <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                    <button type="submit" class="p-2 bg-amber-500/10 text-amber-500 rounded-lg hover:bg-amber-500/20 transition-colors"
                                            title="{{ \App\Core\Lang::get('system_database.restore_backup') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                                    </button>
                                </form>

                                <!-- Delete -->
                                <a href="<?= $baseUrl ?>admin/system-database/backup/delete?file=<?= urlencode($backup['filename']) ?>" 
                                   class="p-2 bg-red-500/10 text-red-500 rounded-lg hover:bg-red-500/20 transition-colors"
                                   onclick="return confirm('¿Eliminar este backup permanentemente?')"
                                   title="{{ \App\Core\Lang::get('system_database.delete_backup') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                                {{ \App\Core\Lang::get('system_database.no_backups') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Info Note -->
    <div class="mt-12 glass-card !p-6 border-blue-500/20">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center text-blue-500 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div>
                <h4 class="text-sm font-black text-p-title mb-2">Información sobre Backups</h4>
                <ul class="text-[11px] text-p-muted space-y-1 font-medium">
                    <li>• Los backups manuales se crean cuando presionas el botón "Crear Backup"</li>
                    <li>• Los backups automáticos se crean según la configuración del sistema</li>
                    <li>• Antes de restaurar, se crea automáticamente un backup de seguridad</li>
                    <li>• Los backups se almacenan en: <code class="text-blue-400">data/backups/system/</code></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
