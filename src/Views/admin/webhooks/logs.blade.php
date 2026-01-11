@extends('layouts.main')

@section('title', $title)

@section('content')
    <header class="mb-8">
        <a href="{{ \App\Core\Auth::getBaseUrl() }}admin/webhooks"
            class="text-xs font-black text-p-muted uppercase tracking-widest hover:text-white transition-colors mb-4 inline-block">
            &larr; Back to Webhooks
        </a>
        <div class="flex items-center gap-4">
            <h1 class="text-3xl font-black text-p-title tracking-tight">Webhook Logs</h1>
            <span class="px-3 py-1 bg-white/10 text-p-muted rounded-full text-xs font-bold">{{ $webhook['name'] }}</span>
        </div>
    </header>

    <div class="glass-card !p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Event</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest">Timestamp</th>
                    <th class="px-6 py-4 text-[10px] font-black text-p-muted uppercase tracking-widest text-right">Action
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($logs as $log)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-4">
                            @if($log['response_code'] >= 200 && $log['response_code'] < 300)
                                <span
                                    class="inline-flex items-center gap-2 px-2 py-1 rounded bg-emerald-500/10 text-emerald-400 text-[10px] font-black">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ $log['response_code'] }} OK
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-2 px-2 py-1 rounded bg-red-500/10 text-red-400 text-[10px] font-black">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> {{ $log['response_code'] }} ERROR
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-p-title">{{ $log['event'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-p-muted font-mono">
                            {{ $log['triggered_at'] }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="showDetails(this)" data-payload="{{ htmlspecialchars($log['payload']) }}"
                                data-response="{{ htmlspecialchars($log['response_body']) }}"
                                class="text-[10px] font-black text-primary hover:underline uppercase tracking-widest">
                                View Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center text-p-muted text-xs uppercase tracking-widest">No logs
                            recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Details Modal -->
    <div id="log-details-modal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-4xl max-h-[90vh] flex flex-col !p-0 shadow-2xl border-2 border-white/10">
            <div class="flex items-center justify-between p-6 border-b border-white/10 bg-white/5">
                <h3 class="text-lg font-bold text-p-title">Transaction Details</h3>
                <button onclick="document.getElementById('log-details-modal').classList.add('hidden')"
                    class="text-p-muted hover:text-white">&times;</button>
            </div>
            <div class="flex-grow overflow-y-auto p-6 grid md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-3">Request Payload</h4>
                    <pre id="modal-payload"
                        class="bg-black/50 p-4 rounded-xl text-[10px] text-emerald-400 font-mono overflow-auto max-h-[400px] border border-white/5"></pre>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-3">Response Body</h4>
                    <pre id="modal-response"
                        class="bg-black/50 p-4 rounded-xl text-[10px] text-amber-400 font-mono overflow-auto max-h-[400px] border border-white/5"></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDetails(btn) {
            const payload = btn.getAttribute('data-payload');
            const response = btn.getAttribute('data-response');

            try {
                document.getElementById('modal-payload').innerText = JSON.stringify(JSON.parse(payload), null, 2);
            } catch (e) {
                document.getElementById('modal-payload').innerText = payload;
            }

            try {
                document.getElementById('modal-response').innerText = JSON.stringify(JSON.parse(response), null, 2);
            } catch (e) {
                document.getElementById('modal-response').innerText = response;
            }

            document.getElementById('log-details-modal').classList.remove('hidden');
        }
    </script>
@endsection