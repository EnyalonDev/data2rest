@extends('layouts.main')

@section('title', $title)

@section('content')
<div class="max-w-3xl mx-auto">
    <header class="mb-8">
        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks" class="text-xs font-black text-p-muted uppercase tracking-widest hover:text-white transition-colors mb-4 inline-block">
            &larr; Back to Webhooks
        </a>
        <h1 class="text-3xl font-black text-p-title tracking-tight">{{ $title }}</h1>
    </header>

    <form action="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks/save" method="POST" class="glass-card p-8 space-y-8">
        @if($webhook)
            <input type="hidden" name="id" value="{{ $webhook['id'] }}">
        @endif

        <!-- Name & URL -->
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Name</label>
                <input type="text" name="name" value="{{ $webhook['name'] ?? '' }}" placeholder="e.g. Slack Integration" class="form-input w-full" required>
            </div>
            <div>
                <label class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Endpoint URL</label>
                <input type="url" name="url" value="{{ $webhook['url'] ?? '' }}" placeholder="https://api.example.com/webhook" class="form-input w-full font-mono text-sm" required>
            </div>
        </div>

        <!-- Secret -->
        <div>
            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2 block">Signing Secret (Optional)</label>
            <div class="relative">
                <input type="text" name="secret" id="secret-input" value="{{ $webhook['secret'] ?? '' }}" placeholder="Signing Key..." class="form-input w-full font-mono text-sm">
                 <button type="button" onclick="document.getElementById('secret-input').value = generateUUID()" class="absolute right-2 top-1.5 px-3 py-1 bg-white/10 hover:bg-white/20 rounded text-[10px] font-bold text-p-muted transition-all">GENERATE</button>
            </div>
            <p class="text-[10px] text-p-muted mt-2">
                If provided, we will send a <code>X-Data2Rest-Signature</code> header with an HMAC-SHA256 signature of the payload.
            </p>
        </div>

        <!-- Events -->
        <div>
            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-4 block">Trigger Events</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php 
                    $currentEvents = $webhook ? explode(',', $webhook['events']) : [];
                @endphp
                
                @foreach($availableEvents as $key => $label)
                <label class="flex items-center gap-3 p-4 bg-white/5 border border-transparent hover:border-primary/30 rounded-xl cursor-pointer transition-all group">
                    <input type="checkbox" name="events[]" value="{{ $key }}" 
                        {{ in_array($key, $currentEvents) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-white/20 bg-black/40 text-primary focus:ring-primary/20">
                    <span class="text-sm font-medium text-p-title group-hover:text-primary transition-colors">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Status -->
        <div class="flex items-center gap-4 pt-4 border-t border-white/5">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="status" value="1" class="sr-only peer" {{ ($webhook['status'] ?? 1) ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                <span class="ml-3 text-xs font-black text-p-muted uppercase tracking-widest">Active</span>
            </label>
        </div>

        <div class="flex justify-end gap-4 pt-4">
            <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks" class="px-6 py-3 font-black uppercase tracking-widest text-xs text-p-muted hover:text-white transition-all">Cancel</a>
            <button type="submit" class="btn-primary px-8">Save Webhook</button>
        </div>
    </form>
</div>

<script>
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
</script>
@endsection
