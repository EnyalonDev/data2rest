@extends('layouts.main')

@section('title', $title)

@section('styles')
    <style type="text/tailwindcss">
        .input-dark {
            @apply bg-black/40 border-2 border-glass-border rounded-xl px-4 py-2 text-p-title focus:outline-none focus:border-primary/50 transition-all font-medium;
        }
        .form-checkbox {
            @apply rounded border-glass-border bg-black/20 text-primary focus:ring-primary;
        }
    </style>
@endsection

@section('content')
    <div class="mb-8">
        <a href="{{ $baseUrl }}admin/api" class="text-p-muted hover:text-white flex items-center gap-2 mb-4 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to API Dashboard
        </a>
        <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
            Manage API Key
        </h1>
        <div class="flex items-center gap-2">
            <span class="bg-white/10 px-3 py-1 rounded text-sm font-mono text-primary font-bold">{{ $apiKey['name'] }}</span>
            <span class="text-p-muted text-sm">{{ $apiKey['key_value'] }}</span>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left: Global Settings -->
        <div class="space-y-8">
            <!-- Rate Limit Card -->
            <section class="glass-card">
                <h2 class="text-lg font-black text-p-title uppercase tracking-tight mb-6">
                    Global Rate Limit
                </h2>
                <div class="bg-black/20 rounded-xl p-4 mb-6">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-p-muted text-sm">Current Usage</span>
                        <span class="text-primary font-bold">{{ number_format($stats['requests']) }} / {{ number_format($apiKey['rate_limit']) }}</span>
                    </div>
                    <div class="w-full bg-white/5 rounded-full h-2">
                        @php $percent = $apiKey['rate_limit'] > 0 ? ($stats['requests'] / $apiKey['rate_limit']) * 100 : 0; @endphp
                        <div class="bg-primary h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $percent) }}%"></div>
                    </div>
                    <p class="text-xs text-p-muted mt-2">Resets in: {{ $stats['remaining_time'] ?? 'N/A' }}</p>
                </div>

                <form id="rateLimitForm" class="space-y-4">
                    <input type="hidden" name="api_key_id" value="{{ $apiKey['id'] }}">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-p-muted mb-2">Hourly Limit</label>
                        <input type="number" name="rate_limit" value="{{ $apiKey['rate_limit'] }}" class="input-dark w-full">
                    </div>
                    <button type="button" onclick="updateRateLimit()" class="btn-primary w-full">Update Limit</button>
                </form>
            </section>
        </div>

        <!-- Right: Granular Permissions -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Add Permission Form -->
            <section class="glass-card border-primary/20">
                <h2 class="text-lg font-black text-p-title uppercase tracking-tight mb-6">
                    Add Permission Rule
                </h2>
                <form action="{{ $baseUrl }}admin/api/permissions/save" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {!! $csrf_field !!}
                    <input type="hidden" name="api_key_id" value="{{ $apiKey['id'] }}">
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-p-muted mb-2">Database</label>
                        <select name="database_id" class="input-dark w-full" required onchange="loadTables(this.value)">
                            <option value="">Select Database...</option>
                            @foreach($databases as $db)
                                <option value="{{ $db['id'] }}">{{ $db['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-p-muted mb-2">Table (Optional)</label>
                        <input type="text" name="table_name" placeholder="* for all tables" class="input-dark w-full">
                        <p class="text-[10px] text-p-muted mt-1">Leave empty or * for all tables in database</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-p-muted mb-2">Allowed Actions</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="can_read" checked class="form-checkbox text-primary rounded bg-white/10 border-white/20">
                                <span class="text-sm font-bold text-p-title">Read (GET)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="can_create" class="form-checkbox text-primary rounded bg-white/10 border-white/20">
                                <span class="text-sm font-bold text-p-title">Create (POST)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="can_update" class="form-checkbox text-primary rounded bg-white/10 border-white/20">
                                <span class="text-sm font-bold text-p-title">Update (PUT)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="can_delete" class="form-checkbox text-red-500 rounded bg-white/10 border-white/20">
                                <span class="text-sm font-bold text-red-500">Delete (DEL)</span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                         <label class="block text-xs font-bold uppercase tracking-widest text-p-muted mb-2">Allowed IPs (Optional)</label>
                         <input type="text" name="allowed_ips" placeholder="192.168.1.1, 10.0.0.5" class="input-dark w-full">
                    </div>

                    <div class="md:col-span-2 text-right">
                        <button type="submit" class="btn-primary">Add Rule</button>
                    </div>
                </form>
            </section>

            <!-- Active Permissions List -->
            <section class="glass-card">
                <h2 class="text-lg font-black text-p-title uppercase tracking-tight mb-6">
                    Active Rules
                </h2>
                
                @if(empty($permissions))
                    <div class="text-center py-8 text-p-muted opacity-50">
                        <p>No specific rules defined.</p>
                        <p class="text-xs">Key has global access unless restricted.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-white/10 text-xs text-p-muted uppercase tracking-wider">
                                    <th class="p-3">Resource</th>
                                    <th class="p-3">Permissions</th>
                                    <th class="p-3">IPs</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($permissions as $perm)
                                    <tr class="group hover:bg-white/5 transition-colors">
                                        <td class="p-3">
                                            <div class="font-bold text-p-title">{{ $perm['database_id'] ? 'DB #' . $perm['database_id'] : 'All DBs' }}</div>
                                            <div class="text-xs text-primary font-mono">{{ $perm['table_name'] ?: '*' }}</div>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex gap-1">
                                                @if($perm['can_read']) <span class="px-1.5 py-0.5 rounded bg-green-500/20 text-green-400 text-[10px] font-bold">READ</span> @endif
                                                @if($perm['can_create']) <span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 text-[10px] font-bold">CREATE</span> @endif
                                                @if($perm['can_update']) <span class="px-1.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400 text-[10px] font-bold">UPDATE</span> @endif
                                                @if($perm['can_delete']) <span class="px-1.5 py-0.5 rounded bg-red-500/20 text-red-400 text-[10px] font-bold">DELETE</span> @endif
                                            </div>
                                        </td>
                                        <td class="p-3 text-sm text-p-muted">
                                            {{ $perm['allowed_ips'] ?: 'Any' }}
                                        </td>
                                        <td class="p-3 text-right">
                                            <button onclick="deletePermission({{ $perm['id'] }})" class="text-p-muted hover:text-red-500 transition-colors p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    async function updateRateLimit() {
        const form = document.getElementById('rateLimitForm');
        const formData = new FormData(form);
        
        try {
            const res = await fetch('{{ $baseUrl }}admin/api/permissions/rate-limit', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' }, // Assuming CSRF handled by router or excluded
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('Rate limit updated successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Update failed', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    async function deletePermission(id) {
        if(!confirm('Are you sure you want to remove this permission rule?')) return;

        const formData = new FormData();
        formData.append('permission_id', id);

        try {
            const res = await fetch('{{ $baseUrl }}admin/api/permissions/delete', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                showToast(data.error || 'Delete failed', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }
</script>
@endsection
