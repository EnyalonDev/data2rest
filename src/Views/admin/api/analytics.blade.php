@extends('layouts.admin')

@section('title', 'API Analytics')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📊 API Analytics Dashboard</h2>
            <div class="btn-group">
                <a href="?range=1h" class="btn btn-{{ $range == '1h' ? 'primary' : 'outline-secondary' }}">1 Hour</a>
                <a href="?range=24h" class="btn btn-{{ $range == '24h' ? 'primary' : 'outline-secondary' }}">24 Hours</a>
                <a href="?range=7d" class="btn btn-{{ $range == '7d' ? 'primary' : 'outline-secondary' }}">7 Days</a>
                <a href="?range=30d" class="btn btn-{{ $range == '30d' ? 'primary' : 'outline-secondary' }}">30 Days</a>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Requests</h5>
                        <h2 class="display-4">{{ number_format($summary['total_requests']) }}</h2>
                        <p class="mb-0">In last {{ $range }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-{{ $summary['avg_latency'] > 500 ? 'warning' : 'success' }} text-white">
                    <div class="card-body">
                        <h5 class="card-title">Avg Latency</h5>
                        <h2 class="display-4">{{ $summary['avg_latency'] }} ms</h2>
                        <p class="mb-0">Response time</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-{{ $summary['error_rate'] > 5 ? 'danger' : 'secondary' }} text-white">
                    <div class="card-body">
                        <h5 class="card-title">Error Rate</h5>
                        <h2 class="display-4">{{ $summary['error_rate'] }}%</h2>
                        <p class="mb-0">Failed requests</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Requests Over Time</div>
                    <div class="card-body">
                        <canvas id="requestsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Status Codes</div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Endpoints Table -->
        <div class="card mb-4">
            <div class="card-header">Top Accessed Endpoints</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Requests</th>
                            <th>Bar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($endpoints_data as $endpoint)
                            <tr>
                                <td class="text-truncate" style="max-width: 300px;">{{ $endpoint['endpoint'] }}</td>
                                <td>{{ $endpoint['count'] }}</td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: {{ ($endpoint['count'] / max(1, $endpoints_data[0]['count'])) * 100 }}%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare Data
        const usageData = @json($usage_data);
        const statusData = @json($status_data);

        // Requests Chart
        const ctxRequests = document.getElementById('requestsChart').getContext('2d');
        new Chart(ctxRequests, {
            type: 'line',
            data: {
                labels: usageData.map(d => d.time_slot),
                datasets: [{
                    label: 'Requests',
                    data: usageData.map(d => d.count),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Status Chart
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        const colors = statusData.map(d => {
            if (d.status_code >= 500) return '#dc3545'; // red
            if (d.status_code >= 400) return '#ffc107'; // yellow
            if (d.status_code >= 300) return '#17a2b8'; // cyan
            return '#28a745'; // green
        });

        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status_code),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: colors
                }]
            }
        });
    </script>
@endsection