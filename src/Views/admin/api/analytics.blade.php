@extends('layouts.main')

@section('title', 'API Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-p-title tracking-tight">📊 API Analytics</h2>
            <p class="text-p-muted text-sm font-medium">Insights and performance metrics for your API</p>
        </div>
        <div class="flex items-center bg-black/20 rounded-xl p-1">
            @foreach(['1h' => '1 Hour', '24h' => '24 Hours', '7d' => '7 Days', '30d' => '30 Days'] as $key => $label)
                <a href="?range={{ $key }}"
                    class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all {{ $range == $key ? 'bg-primary text-dark shadow-lg shadow-primary/20' : 'text-p-muted hover:text-p-text hover:bg-white/5' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Requests -->
        <div class="glass-card hover:border-primary/30 transition-colors group">
            <div class="flex flex-col h-full justify-between">
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Total Requests</h4>
                    <span class="text-3xl font-black text-p-title group-hover:text-primary transition-colors">
                        {{ number_format($summary['total_requests']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-glass-border">
                    <span class="text-xs font-bold text-p-muted">In last {{ $range }}</span>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="glass-card hover:border-emerald-500/30 transition-colors group">
            <div class="flex flex-col h-full justify-between">
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Success Rate</h4>
                    @php
                        $rate = $summary['total_requests'] > 0 ? ($summary['success_count'] / $summary['total_requests']) * 100 : 0;
                        $colorClass = $rate >= 98 ? 'text-emerald-500' : ($rate >= 90 ? 'text-emerald-400' : 'text-amber-500');
                    @endphp
                    <span class="text-3xl font-black {{ $colorClass }}">
                        {{ round($rate, 1) }}%
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-glass-border flex justify-between items-center">
                    <span class="text-xs font-bold text-p-muted">{{ number_format($summary['success_count']) }} successful</span>
                    <div class="w-16 h-1 bg-emerald-500/20 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500" style="width: {{ $rate }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Denied -->
        <div class="glass-card hover:border-amber-500/30 transition-colors group">
            <div class="flex flex-col h-full justify-between">
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Denied / Auth</h4>
                    <span class="text-3xl font-black text-amber-500">
                        {{ number_format($summary['denied_count']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-glass-border">
                    <span class="text-xs font-bold text-p-muted">401, 403, 429 Errors</span>
                </div>
            </div>
        </div>

        <!-- System Errors -->
        <div class="glass-card hover:border-red-500/30 transition-colors group">
            <div class="flex flex-col h-full justify-between">
                <div>
                    <h4 class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Server Errors</h4>
                    <span class="text-3xl font-black text-red-500">
                        {{ number_format($summary['server_error_count']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-glass-border">
                    <span class="text-xs font-bold text-p-muted">5xx System Failures</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Timeline -->
        <div class="lg:col-span-2 glass-card">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-p-title text-lg tracking-tight">Requests Over Time</h3>
                <span class="px-2 py-1 bg-primary/10 text-primary rounded text-xs font-black uppercase tracking-wider">Timeline</span>
            </div>
            <div class="relative w-full h-[300px]">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>

        <!-- Distribution -->
        <div class="glass-card flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-p-title text-lg tracking-tight">Response Codes</h3>
                <span class="px-2 py-1 bg-white/5 text-p-muted rounded text-xs font-black uppercase tracking-wider">Status</span>
            </div>
            <div class="relative w-full flex-1 min-h-[250px] flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-6 pt-6 border-t border-glass-border text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-p-muted mb-1">Average Latency</p>
                <p class="text-2xl font-black text-p-title">{{ $summary['avg_latency'] }} <span class="text-sm text-p-muted font-bold">ms</span></p>
            </div>
        </div>
    </div>

    <!-- Top Endpoints -->
    <div class="glass-card">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-p-title text-lg tracking-tight">Top Active Endpoints</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-glass-border">
                        <th class="text-left py-3 px-4 text-[10px] font-black uppercase tracking-widest text-p-muted">Endpoint</th>
                        <th class="text-right py-3 px-4 text-[10px] font-black uppercase tracking-widest text-p-muted">Requests</th>
                        <th class="text-left py-3 px-4 text-[10px] font-black uppercase tracking-widest text-p-muted w-1/3">Traffic Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-glass-border">
                    @forelse($endpoints_data as $endpoint)
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="py-3 px-4">
                                <code class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded border border-primary/20">{{ $endpoint['endpoint'] }}</code>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-p-title">
                                {{ number_format($endpoint['count']) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="w-full h-2 bg-p-border rounded-full overflow-hidden">
                                    <div class="h-full bg-primary group-hover:bg-primary/80 transition-all rounded-full"
                                         style="width: {{ ($endpoint['count'] / max(1, $endpoints_data[0]['count'])) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-p-muted font-bold italic">No data available for this range</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme Constants
        const style = getComputedStyle(document.body);
        const colorPrimary = '#38bdf8'; // Sky 400
        const colorSuccess = '#10b981'; // Emerald 500
        const colorError = '#ef4444';   // Red 500
        const colorWarning = '#f59e0b'; // Amber 500
        const colorText = style.getPropertyValue('--p-text').trim() || '#94a3b8';
        const colorBorder = style.getPropertyValue('--p-border').trim() || '#e2e8f0';

        // Defaults
        Chart.defaults.font.family = 'Outfit, sans-serif';
        Chart.defaults.color = colorText;
        Chart.defaults.borderColor = colorBorder;

        // Prepare Data
        const usageData = @json($usage_data);
        const statusData = @json($status_data);

        // Requests Chart (Multi-line)
        const ctxRequests = document.getElementById('requestsChart').getContext('2d');
        new Chart(ctxRequests, {
            type: 'line',
            data: {
                labels: usageData.map(d => {
                    const date = new Date(d.time_slot);
                    // Check if range is daily or hourly based on format
                    // If label already contains " ", it's likely YYYY-MM-DD HH:MM
                    const isHourly = d.time_slot.includes(' ');
                    return isHourly ? date.getHours() + ':00' : date.toLocaleDateString();
                }), 
                datasets: [
                    {
                        label: 'Success (2xx)',
                        data: usageData.map(d => d.success_count),
                        borderColor: colorSuccess,
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                            gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                            return gradient;
                        },
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: colorSuccess,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Errors (4xx/5xx)',
                        data: usageData.map(d => d.error_count),
                        borderColor: colorError,
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        tension: 0.4,
                        fill: true,
                        borderDash: [5, 5],
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { 
                        position: 'top', 
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 20,
                            font: { weight: 'bold', size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false } 
                    },
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [4, 4], color: 'rgba(200, 200, 200, 0.1)' } 
                    }
                }
            }
        });

        // Status Chart (Doughnut)
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        const colors = statusData.map(d => {
            if (d.status_code >= 500) return colorError; 
            if (d.status_code == 429) return colorWarning; 
            if (d.status_code >= 400) return colorWarning; 
            if (d.status_code >= 300) return colorPrimary; 
            return colorSuccess; 
        });

        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status_code),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { 
                        position: 'right', 
                        labels: { 
                            usePointStyle: true, 
                            boxWidth: 8,
                            padding: 15,
                            font: { weight: 'bold', size: 11 }
                        } 
                    }
                }
            }
        });
    });
</script>
@endsection