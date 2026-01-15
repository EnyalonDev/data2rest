@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-purple-500/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-purple-500 text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            📊 Financial Reports
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Reportes Financieros
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Análisis detallado de ingresos, comparativas y proyecciones
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Reportes</span>
        </nav>
    </div>

    <!-- Period Selector -->
    <div class="glass-card !p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div>
                <h3 class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Período de Análisis</h3>
                <p class="text-sm text-p-title">Selecciona el rango de fechas para el reporte</p>
            </div>
            <div class="flex gap-4">
                <select id="periodSelector" onchange="updatePeriod()"
                    class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                    <option value="current_month">Mes Actual</option>
                    <option value="last_month">Mes Anterior</option>
                    <option value="current_quarter">Trimestre Actual</option>
                    <option value="current_year" selected>Año Actual</option>
                    <option value="last_year">Año Anterior</option>
                    <option value="all_time">Todo el Tiempo</option>
                </select>
                <button onclick="exportReport()"
                    class="px-6 py-2 bg-purple-500/10 hover:bg-purple-500 hover:text-dark border border-purple-500/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-purple-500 transition-all">
                    Exportar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <!-- Total Income -->
        <div class="glass-card !p-6 border-t-4 border-emerald-500/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center text-emerald-500 text-2xl">
                    💰
                </div>
                <span
                    class="text-xs font-black text-emerald-500 uppercase tracking-widest">+{{ $summary['income_growth'] ?? 0 }}%</span>
            </div>
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Ingresos Totales</p>
            <p class="text-3xl font-black text-p-title">${{ number_format($summary['total_income'] ?? 0, 2) }}</p>
        </div>

        <!-- Pending Amount -->
        <div class="glass-card !p-6 border-t-4 border-amber-500/50">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center text-amber-500 text-2xl">
                    ⏳
                </div>
            </div>
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Por Cobrar</p>
            <p class="text-3xl font-black text-p-title">${{ number_format($summary['pending_amount'] ?? 0, 2) }}</p>
        </div>

        <!-- Active Projects -->
        <div class="glass-card !p-6 border-t-4 border-primary/50">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center text-primary text-2xl">
                    📁
                </div>
            </div>
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Proyectos Activos</p>
            <p class="text-3xl font-black text-p-title">{{ $summary['active_projects'] ?? 0 }}</p>
        </div>

        <!-- Average Ticket -->
        <div class="glass-card !p-6 border-t-4 border-purple-500/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center text-purple-500 text-2xl">
                    📈
                </div>
            </div>
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Ticket Promedio</p>
            <p class="text-3xl font-black text-p-title">${{ number_format($summary['average_ticket'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Monthly Income Comparison -->
        <div class="glass-card !p-8 border-t-4 border-emerald-500/30">
            <div class="mb-6">
                <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                    Comparativa de Ingresos
                </h3>
                <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                    Año Actual vs Anterior
                </p>
            </div>
            <div class="h-[350px] w-full">
                <canvas id="incomeComparisonChart"></canvas>
            </div>
        </div>

        <!-- Income by Client -->
        <div class="glass-card !p-8 border-t-4 border-primary/30">
            <div class="mb-6">
                <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                    Ingresos por Cliente
                </h3>
                <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                    Top 10 Clientes
                </p>
            </div>
            <div class="h-[350px] w-full">
                <canvas id="clientIncomeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Upcoming Installments Forecast -->
    <div class="glass-card !p-8 border-t-4 border-amber-500/30 mb-12">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                    Proyección de Ingresos
                </h3>
                <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                    Próximos 6 Meses
                </p>
            </div>
        </div>
        <div class="h-[300px] w-full">
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    <!-- Top Clients Table -->
    <div class="glass-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-white/5">
            <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                Clientes Principales
            </h3>
            <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                Por Ingresos Generados
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5 border-b border-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Ranking
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cliente
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Proyectos
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Total Pagado
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Pendiente
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topClients ?? [] as $index => $client)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center text-primary font-black">
                                    #{{ $index + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-p-title">{{ $client['name'] }}</p>
                                <p class="text-xs text-p-muted">{{ $client['email'] ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-primary/20 text-primary rounded-lg text-xs font-black">
                                    {{ $client['projects_count'] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-sm font-black text-emerald-500">
                                    ${{ number_format($client['total_paid'] ?? 0, 2) }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-sm font-black text-amber-500">
                                    ${{ number_format($client['total_pending'] ?? 0, 2) }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-lg font-black text-p-title">
                                    ${{ number_format(($client['total_paid'] ?? 0) + ($client['total_pending'] ?? 0), 2) }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-p-muted">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.weight = '600';

        // Income Comparison Chart
        const ctxComparison = document.getElementById('incomeComparisonChart').getContext('2d');
        new Chart(ctxComparison, {
            type: 'bar',
            data: {
                labels: {!! json_encode($incomeComparison['labels'] ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']) !!},
                datasets: [{
                    label: 'Año Actual',
                    data: {!! json_encode($incomeComparison['current_year'] ?? []) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 8
                }, {
                    label: 'Año Anterior',
                    data: {!! json_encode($incomeComparison['previous_year'] ?? []) !!},
                    backgroundColor: 'rgba(148, 163, 184, 0.4)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Client Income Chart
        const ctxClient = document.getElementById('clientIncomeChart').getContext('2d');
        new Chart(ctxClient, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_column($topClients ?? [], 'name')) !!},
                datasets: [{
                    data: {!! json_encode(array_map(function ($c) {
        return ($c['total_paid'] ?? 0) + ($c['total_pending'] ?? 0); }, $topClients ?? [])) !!},
                    backgroundColor: [
                        '#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444',
                        '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#84cc16'
                    ],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });

        // Forecast Chart
        const ctxForecast = document.getElementById('forecastChart').getContext('2d');
        const forecastGradient = ctxForecast.createLinearGradient(0, 0, 0, 400);
        forecastGradient.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
        forecastGradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

        new Chart(ctxForecast, {
            type: 'line',
            data: {
                labels: {!! json_encode($forecast['labels'] ?? []) !!},
                datasets: [{
                    label: 'Proyección',
                    data: {!! json_encode($forecast['amounts'] ?? []) !!},
                    borderColor: '#8b5cf6',
                    borderWidth: 3,
                    backgroundColor: forecastGradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        function updatePeriod() {
            const period = document.getElementById('periodSelector').value;
            window.location.href = `{{ $baseUrl }}admin/billing/reports?period=${period}`;
        }

        function exportReport() {
            showModal({
                title: '📄 Exportar Reporte',
                message: 'La funcionalidad de exportación a PDF estará disponible próximamente.',
                type: 'info'
            });
        }
    </script>
@endsection