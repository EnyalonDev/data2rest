@extends('layouts.main')

@section('title', $title)

@section('content')
<header class="mb-8">
    <h1 class="text-3xl font-black text-p-title italic tracking-tighter mb-2">{{ $title }}</h1>
    <p class="text-p-muted font-medium">
        Audit trail for <b>{{ $ctx['table'] }}</b> ID: <span class="text-primary">#{{ $id }}</span>.
    </p>
    <p class="text-[10px] text-amber-500/80 font-black uppercase tracking-widest mt-2 flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        Retention Policy: Histories are automatically purged after {{ \App\Core\Config::getSetting('audit_retention_days', 30) }} days.
    </p>
</header>

<div class="space-y-6">
    @if(empty($versions))
        <div class="glass-card flex flex-col items-center justify-center py-12 text-center opacity-50">
            <svg class="w-16 h-16 text-p-muted mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-black text-p-title uppercase tracking-widest">No History Found</h3>
            <p class="text-xs text-p-muted mt-2">This record has no recorded changes in the audit trail.</p>
        </div>
    @else
        <div class="relative pl-8 before:absolute before:left-3 before:top-2 before:bottom-0 before:w-0.5 before:bg-white/10 space-y-8">
            @foreach($versions as $v)
                @php
                    $oldData = json_decode($v['old_data'], true);
                    $newData = json_decode($v['new_data'], true);
                    $diff = [];
                    
                    if ($newData) {
                        foreach ($newData as $k => $val) {
                            if (($oldData[$k] ?? null) != $val) {
                                $diff[$k] = [
                                    'old' => $oldData[$k] ?? null,
                                    'new' => $val
                                ];
                            }
                        }
                    } else {
                        // Deleted
                        $diff = $oldData;
                    }

                    $actionColors = [
                        'UPDATE' => 'bg-amber-500',
                        'DELETE' => 'bg-red-500',
                        'RESTORE' => 'bg-emerald-500' // If we add this type
                    ];
                    $bg = $actionColors[$v['action']] ?? 'bg-slate-500';
                @endphp

                <div class="relative glass-card !p-0 overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
                    <!-- Timeline Dot -->
                    <div class="absolute -left-12 top-6 w-8 h-8 rounded-full border-4 border-dark {{ $bg }} flex items-center justify-center shadow-[0_0_15px_rgba(0,0,0,0.5)] z-10">
                        @if($v['action'] === 'DELETE')
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        @elseif($v['action'] === 'UPDATE')
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        @endif
                    </div>

                    <!-- Header -->
                    <div class="px-6 py-4 bg-white/5 border-b border-white/5 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded {{ $bg }}/20 {{ str_replace('bg-', 'text-', $bg) }}">
                                {{ $v['action'] }}
                            </span>
                            <span class="text-xs text-p-muted font-mono">{{ $v['created_at'] }}</span>
                        </div>
                        <div class="text-xs font-bold text-p-title flex items-center gap-2">
                             <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary text-[10px]">
                                {{ substr($v['username'] ?? ($v['api_key_name'] ?? 'Sys'), 0, 1) }}
                             </div>
                             @if($v['api_key_name'])
                                 <span class="text-emerald-400">API: {{ $v['api_key_name'] }}</span>
                             @else
                                 {{ $v['username'] ?? 'System' }}
                             @endif
                        </div>
                    </div>

                    <!-- Diff Content -->
                    <div class="p-6">
                        @if($v['action'] === 'UPDATE')
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($diff as $field => $changes)
                                    <div class="grid grid-cols-[150px_1fr] items-start gap-4 p-2 hover:bg-white/5 rounded-lg transition-colors">
                                        <div class="text-xs font-bold text-p-muted uppercase tracking-widest pt-1">{{ $field }}</div>
                                        <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                                            <div class="text-red-400 break-all bg-red-400/5 p-1 rounded decoration-red-400/30 line-through opacity-70">
                                                {{ is_string($changes['old']) ? $changes['old'] : json_encode($changes['old']) }}
                                            </div>
                                            <div class="text-emerald-400 break-all bg-emerald-400/5 p-1 rounded">
                                                {{ is_string($changes['new']) ? $changes['new'] : json_encode($changes['new']) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($v['action'] === 'DELETE')
                             <div class="p-4 bg-red-500/5 rounded-lg border border-red-500/10">
                                <p class="text-xs text-red-300 mb-2 font-bold uppercase tracking-widest">Deleted Data Snapshot:</p>
                                <pre class="text-[10px] text-p-muted font-mono overflow-auto custom-scrollbar max-h-40">{{ json_encode(json_decode($v['old_data']), JSON_PRETTY_PRINT) }}</pre>
                             </div>
                        @endif
                    </div>

                    <!-- Footer / Restore -->
                    <div class="px-6 py-3 bg-black/20 border-t border-white/5 flex justify-end">
                        <form action="{{ $baseUrl }}admin/crud/restore" method="POST" onsubmit="return confirm('WARNING: This will overwrite the current live record with this historical version. Are you sure?');">
                            {!! $csrf_field !!}
                            <input type="hidden" name="version_id" value="{{ $v['id'] }}">
                            <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-p-muted hover:text-primary transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Restore to this version
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
