@extends('layouts.main')

@section('title', $title)

@section('content')
    <header class="mb-12 text-center relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-red-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-red-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            Recycle Bin
        </div>
        <h1 class="text-5xl font-black text-p-title mb-4 tracking-tighter uppercase italic">
            Deleted Records
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto mb-8">
            Review and restore data that has been removed from your databases. Histories are kept for
            {{ \App\Core\Config::getSetting('audit_retention_days', 30) }} days.
        </p>

        @if(!empty($deletions))
            <form action="{{ $baseUrl }}admin/trash/empty" method="POST"
                onsubmit="return confirm('CRITICAL: This will PERMANENTLY delete all records in the Recycle Bin. This action cannot be undone. Are you sure?');">
                {!! $csrf_field !!}
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-xl text-[10px] font-black text-red-500 uppercase tracking-widest transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Empty Recycle Bin
                </button>
            </form>
        @endif
    </header>

    <div class="glass-card !p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Database / Table
                    </th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Record Info</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Deleted By</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest text-right">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($deletions as $v)
                    @php
                        $data = json_decode($v['old_data'], true);
                        $label = $data['nombre'] ?? $data['name'] ?? $data['title'] ?? '#' . $v['record_id'];
                    @endphp
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs font-black text-p-title uppercase tracking-tighter">{{ $v['db_name'] }}</span>
                                <span
                                    class="text-[10px] text-primary font-black uppercase tracking-widest opacity-60">{{ $v['table_name'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-p-title truncate max-w-xs">{{ $label }}</span>
                                <span class="text-[10px] text-p-muted font-black uppercase italic tracking-tighter">
                                    {{ date('M d, H:i', strtotime($v['created_at'])) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary text-[10px] font-bold">
                                    {{ substr($v['actor'] ?? ($v['api_key_name'] ?? 'S'), 0, 1) }}
                                </div>
                                <span class="text-xs font-bold text-p-muted">
                                    @if($v['api_key_name'])
                                        <span class="text-emerald-400">API: {{ $v['api_key_name'] }}</span>
                                    @else
                                        {{ $v['actor'] ?? 'System' }}
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick='previewDeletedData(@json($v))'
                                    class="text-[9px] font-black uppercase text-p-muted hover:text-white transition-colors">
                                    Preview
                                </button>
                                <form action="{{ $baseUrl }}admin/crud/restore" method="POST"
                                    onsubmit="return confirm('Restore this record to its original table?');">
                                    {!! $csrf_field !!}
                                    <input type="hidden" name="version_id" value="{{ $v['id'] }}">
                                    <button type="submit"
                                        class="text-[9px] font-black uppercase text-emerald-500 hover:text-emerald-400 transition-colors">
                                        Restore Record
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center opacity-30">
                                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em]">Recycle bin is empty</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function previewDeletedData(version) {
            const data = JSON.parse(version.old_data);
            let html = '<div class="grid grid-cols-1 gap-4 p-4">';

            for (const [key, val] of Object.entries(data)) {
                html += `
                        <div class="flex flex-col border-b border-white/5 pb-2">
                            <span class="text-[9px] font-black text-p-muted uppercase tracking-widest mb-1">${key}</span>
                            <span class="text-xs font-mono text-p-title break-all">${val !== null ? val : '<i class="opacity-30">NULL</i>'}</span>
                        </div>
                    `;
            }
            html += '</div>';

            showModal({
                title: 'Delete Preview: ' + version.table_name,
                message: html,
                type: 'modal',
                maxWidth: 'max-w-2xl'
            });
        }
    </script>
@endsection