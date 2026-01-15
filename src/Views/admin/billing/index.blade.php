@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-emerald-500/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-emerald-500 text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            💰 Billing Management
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Financial Dashboard
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Gestión completa de pagos, cuotas y facturación por proyecto
        </p>
    </header>

    <!-- Financial Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <!-- Paid Installments -->
        <div
            class="glass-card py-8 flex flex-col items-center border-b-4 border-emerald-500/50 group relative overflow-hidden">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/5 blur-3xl rounded-full group-hover:bg-emerald-500/10 transition-all duration-700">
            </div>
            <div
                class="w-16 h-16 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-500 text-3xl mb-4 group-hover:scale-110 transition-transform">
                ✅
            </div>
            <span
                class="text-4xl font-black text-p-title mb-2">${{ number_format($financialSummary['paid_amount'], 2) }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">Pagado</span>
            <span class="text-xs font-bold text-emerald-500">{{ $financialSummary['paid_count'] }} cuotas</span>
        </div>

        <!-- Pending Installments -->
        <div
            class="glass-card py-8 flex flex-col items-center border-b-4 border-amber-500/50 group relative overflow-hidden">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-amber-500/5 blur-3xl rounded-full group-hover:bg-amber-500/10 transition-all duration-700">
            </div>
            <div
                class="w-16 h-16 rounded-2xl bg-amber-500/20 flex items-center justify-center text-amber-500 text-3xl mb-4 group-hover:scale-110 transition-transform">
                ⏳
            </div>
            <span
                class="text-4xl font-black text-p-title mb-2">${{ number_format($financialSummary['pending_amount'], 2) }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">Pendiente</span>
            <span class="text-xs font-bold text-amber-500">{{ $financialSummary['pending_count'] }} cuotas</span>
        </div>

        <!-- Overdue Installments -->
        <div class="glass-card py-8 flex flex-col items-center border-b-4 border-red-500/50 group relative overflow-hidden">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl rounded-full group-hover:bg-red-500/10 transition-all duration-700">
            </div>
            <div
                class="w-16 h-16 rounded-2xl bg-red-500/20 flex items-center justify-center text-red-500 text-3xl mb-4 group-hover:scale-110 transition-transform">
                ⚠️
            </div>
            <span
                class="text-4xl font-black text-p-title mb-2">${{ number_format($financialSummary['overdue_amount'], 2) }}</span>
            <span class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">Vencido</span>
            <span class="text-xs font-bold text-red-500">{{ $financialSummary['overdue_count'] }} cuotas</span>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
        <!-- Income by Month Chart -->
        <div class="glass-card !p-8 border-t-4 border-emerald-500/30 relative overflow-hidden group">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/5 blur-3xl rounded-full group-hover:bg-emerald-500/10 transition-all duration-700">
            </div>
            <div class="flex justify-between items-center mb-8 relative z-10">
                <div>
                    <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                        Ingresos Mensuales
                    </h3>
                    <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                        Últimos 6 Meses
                    </p>
                </div>
            </div>
            <div class="h-[300px] w-full">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <!-- Installments by Status Chart -->
        <div class="glass-card !p-8 border-t-4 border-primary/30 relative overflow-hidden group">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-primary/5 blur-3xl rounded-full group-hover:bg-primary/10 transition-all duration-700">
            </div>
            <div class="mb-8 relative z-10">
                <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                    Distribución de Cuotas
                </h3>
                <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                    Por Estado
                </p>
            </div>
            <div class="h-[300px] w-full flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Upcoming & Overdue Installments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
        <!-- Upcoming Installments -->
        <div class="glass-card !p-8 border-t-4 border-amber-500/30">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                        Próximas a Vencer
                    </h3>
                    <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                        Próximos 30 Días
                    </p>
                </div>
                <a href="{{ $baseUrl }}admin/billing/installments?filter=upcoming"
                    class="text-[9px] font-black text-primary uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                    Ver Todas &rarr;
                </a>
            </div>
            <div class="space-y-4">
                @forelse($upcomingInstallments as $inst)
                    <div class="bg-white/5 p-4 rounded-xl border border-white/5 hover:border-amber-500/30 transition-all group">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-bold text-p-title">{{ $inst['project_name'] }}</p>
                                <p class="text-xs text-p-muted">{{ $inst['client_name'] }}</p>
                            </div>
                            <span class="text-lg font-black text-amber-500">${{ number_format($inst['amount'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                            <span class="text-p-muted">Vence: {{ date('M d, Y', strtotime($inst['due_date'])) }}</span>
                            <span class="px-2 py-1 bg-amber-500/20 text-amber-500 rounded-lg">Cuota
                                #{{ $inst['installment_number'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-p-muted">
                        <p class="text-[10px] font-black uppercase tracking-widest">
                            No hay cuotas próximas a vencer
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Overdue Installments -->
        <div class="glass-card !p-8 border-t-4 border-red-500/30">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                        Cuotas Vencidas
                    </h3>
                    <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                        Requieren Atención
                    </p>
                </div>
                <a href="{{ $baseUrl }}admin/billing/installments?filter=overdue"
                    class="text-[9px] font-black text-red-500 uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                    Ver Todas &rarr;
                </a>
            </div>
            <div class="space-y-4">
                @forelse($overdueInstallments as $inst)
                    <div
                        class="bg-white/5 p-4 rounded-xl border border-red-500/20 hover:border-red-500/40 transition-all group">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-bold text-p-title">{{ $inst['project_name'] }}</p>
                                <p class="text-xs text-p-muted">{{ $inst['client_name'] }}</p>
                            </div>
                            <span class="text-lg font-black text-red-500">${{ number_format($inst['amount'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                            <span class="text-red-500">Vencida hace {{ (int) $inst['days_overdue'] }} días</span>
                            <span class="px-2 py-1 bg-red-500/20 text-red-500 rounded-lg">Cuota
                                #{{ $inst['installment_number'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-p-muted">
                        <p class="text-[10px] font-black uppercase tracking-widest">
                            ✅ No hay cuotas vencidas
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="glass-card !p-8 border-t-4 border-primary/30">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-1">
                    Actividad Reciente
                </h3>
                <p class="text-xl font-black text-p-title italic uppercase tracking-tighter">
                    Últimos Pagos
                </p>
            </div>
        </div>
        <div class="space-y-4">
            @forelse($recentActivity as $activity)
                <div class="relative pl-6 border-l border-white/5 group">
                    <div
                        class="absolute left-[-5px] top-1 w-2.5 h-2.5 rounded-full bg-emerald-500 border-4 border-dark group-hover:scale-125 transition-transform">
                    </div>
                    <p class="text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">
                        {{ date('M d, H:i', strtotime($activity['date'])) }}
                    </p>
                    <p class="text-xs font-bold text-p-title mb-1">
                        Pago de ${{ number_format($activity['amount'], 2) }} - {{ $activity['client_name'] }}
                    </p>
                    <p class="text-[9px] font-black text-primary uppercase opacity-60">
                        {{ $activity['project_name'] }} / Cuota #{{ $activity['installment_number'] }}
                    </p>
                </div>
            @empty
                <div class="py-10 text-center text-p-muted">
                    <p class="text-[10px] font-black uppercase tracking-widest">
                        No hay actividad reciente
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-16">
        <h2 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
            <span class="w-8 h-[1px] bg-slate-800"></span> Acciones Rápidas
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Clients -->
            <a href="{{ $baseUrl }}admin/billing/clients"
                class="glass-card group hover:scale-[1.02] hover:border-primary/50 !p-8">
                <div
                    class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Clientes</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Gestionar clientes y su información de facturación
                </p>
                <div class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                    Gestionar <span>&rarr;</span>
                </div>
            </a>

            <!-- Projects -->
            <a href="{{ $baseUrl }}admin/billing/projects"
                class="glass-card group hover:scale-[1.02] hover:border-emerald-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-emerald-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Proyectos</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Ver proyectos con planes de pago activos
                </p>
                <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Proyectos <span>&rarr;</span>
                </div>
            </a>

            <!-- Installments -->
            <a href="{{ $baseUrl }}admin/billing/installments"
                class="glass-card group hover:scale-[1.02] hover:border-amber-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Cuotas</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Administrar cuotas y registrar pagos
                </p>
                <div class="text-[10px] font-black text-amber-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Cuotas <span>&rarr;</span>
                </div>
            </a>

            <!-- Payment Plans -->
            <a href="{{ $baseUrl }}admin/billing/plans"
                class="glass-card group hover:scale-[1.02] hover:border-blue-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-blue-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Planes de Pago</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Gestionar planes y editar precios
                </p>
                <div class="text-[10px] font-black text-blue-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Planes <span>&rarr;</span>
                </div>
            </a>

            <!-- Services Catalog -->
            <a href="{{ $baseUrl }}admin/billing/services"
                class="glass-card group hover:scale-[1.02] hover:border-sky-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-sky-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Catálogo de Servicios</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Gestionar servicios y productos disponibles
                </p>
                <div class="text-[10px] font-black text-sky-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Catálogo <span>&rarr;</span>
                </div>
            </a>

            <!-- Reports -->
            <a href="{{ $baseUrl }}admin/billing/reports"
                class="glass-card group hover:scale-[1.02] hover:border-purple-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-purple-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Reportes</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Análisis financieros y proyecciones
                </p>
                <div class="text-[10px] font-black text-purple-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Reportes <span>&rarr;</span>
                </div>
            </a>

            <!-- Payments History -->
            <a href="{{ $baseUrl }}admin/billing/payments"
                class="glass-card group hover:scale-[1.02] hover:border-emerald-500/50 !p-8">
                <div
                    class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 text-emerald-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">Historial de Pagos</h3>
                <p class="text-xs text-p-muted mb-6 leading-relaxed">
                    Registro completo de pagos recibidos
                </p>
                <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest flex items-center gap-2">
                    Ver Historial <span>&rarr;</span>
                </div>
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart.js Default Styles
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.weight = '600';

        // Income by Month Chart
        const ctxIncome = document.getElementById('incomeChart').getContext('2d');
        const incomeGradient = ctxIncome.createLinearGradient(0, 0, 0, 400);
        incomeGradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
        incomeGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ctxIncome, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($chartData['income_by_month'], 'month')) !!},
                datasets: [{
                    label: 'Ingresos',
                    data: {!! json_encode(array_column($chartData['income_by_month'], 'total')) !!},
                    borderColor: '#10b981',
                    borderWidth: 4,
                    backgroundColor: incomeGradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
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

        // Installments by Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_map('ucfirst', array_column($chartData['installments_by_status'], 'status'))) !!},
                datasets: [{
                    data: {!! json_encode(array_column($chartData['installments_by_status'], 'count')) !!},
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    </script>
@endsection