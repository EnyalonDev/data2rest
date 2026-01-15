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
            📁 Projects Management
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Proyectos
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Proyectos con planes de pago activos
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Proyectos</span>
        </nav>
    </div>

    <!-- Projects Table -->
    <div class="glass-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-white/5">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="text-xl font-black text-p-title uppercase tracking-tighter italic">Proyectos con Billing</h2>
                    <p class="text-xs text-p-muted">{{ count($projects) }} proyectos activos</p>
                </div>
                <input type="text" id="searchProjects" placeholder="Buscar proyectos..."
                    class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder-p-muted focus:border-primary/50 outline-none text-sm w-full md:w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5 border-b border-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Proyecto
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cliente
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Plan
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cuotas
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Estado
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    @forelse($projects as $project)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors project-row"
                            data-project-name="{{ strtolower($project['name']) }}"
                            data-client-name="{{ strtolower($project['client_name']) }}">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ $project['name'] }}</p>
                                    <p class="text-xs text-p-muted">ID: {{ $project['id'] }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-p-title font-bold">{{ $project['client_name'] }}</p>
                                @if(empty($project['client_email']) || empty($project['client_phone']) || empty($project['client_tax_id']))
                                    <div class="flex items-center gap-1 mt-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Datos
                                            incompletos</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ $project['plan_name'] }}</p>
                                    <p class="text-xs text-p-muted">{{ ucfirst($project['plan_frequency']) }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs text-emerald-500 font-bold">{{ $project['paid_installments'] }}</span>
                                        <span class="text-xs text-p-muted">/</span>
                                        <span class="text-xs text-p-muted">{{ $project['total_installments'] }}</span>
                                    </div>
                                    @if($project['overdue_installments'] > 0)
                                        <span class="text-[10px] font-black text-red-500 uppercase">
                                            {{ $project['overdue_installments'] }} vencidas
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $progress = $project['total_installments'] > 0
                                        ? ($project['paid_installments'] / $project['total_installments']) * 100
                                        : 0;

                                    $needsRenewal = ($project['paid_installments'] >= $project['total_installments']) && $project['total_installments'] > 0;
                                    $nearRenewal = !$needsRenewal && ($project['pending_installments'] == 1) && ($project['total_installments'] > 1);
                                @endphp
                                <div class="flex flex-col items-center gap-2">
                                    @if($needsRenewal)
                                        <span
                                            class="px-2 py-1 bg-amber-500/20 text-amber-500 rounded text-[9px] font-black uppercase tracking-widest border border-amber-500/30 mb-1 animate-pulse">
                                            Requiere Renovación
                                        </span>
                                    @elseif($nearRenewal)
                                        <span
                                            class="px-2 py-1 bg-blue-500/20 text-blue-500 rounded text-[9px] font-black uppercase tracking-widest border border-blue-500/30 mb-1">
                                            Última Cuota
                                        </span>
                                    @endif

                                    <div class="w-full max-w-[100px] h-2 bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full {{ $needsRenewal ? 'bg-amber-500' : 'bg-emerald-500' }} transition-all"
                                            style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-black text-p-muted">{{ round($progress) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ $baseUrl }}admin/billing/installments?project_id={{ $project['id'] }}"
                                        class="px-3 py-1.5 bg-primary/10 hover:bg-primary hover:text-dark border border-primary/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-primary transition-all"
                                        title="Ver Cuotas">
                                        Ver Cuotas
                                    </a>
                                    <button onclick="changePlan({{ $project['id'] }}, '{{ addslashes($project['name']) }}')"
                                        class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500 hover:text-dark border border-amber-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-amber-500 transition-all"
                                        title="Cambiar Plan">
                                        Cambiar Plan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div
                                    class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center text-emerald-500 text-3xl mx-auto mb-6">
                                    📁
                                </div>
                                <h3 class="text-xl font-bold text-p-title mb-2">No hay proyectos con billing</h3>
                                <p class="text-p-muted">Los proyectos con planes de pago aparecerán aquí</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Available Plans Reference -->
    <div class="mt-8 glass-card !p-6">
        <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-4">Planes Disponibles</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $plan)
                <div class="bg-white/5 p-4 rounded-xl border border-white/5">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="text-sm font-bold text-p-title">{{ $plan['name'] }}</p>
                            <p class="text-xs text-p-muted">{{ ucfirst($plan['frequency']) }}</p>
                        </div>
                        <span class="text-lg font-black text-primary italic text-[10px]">Total por servicios</span>
                    </div>
                    @if($plan['description'])
                        <p class="text-xs text-p-muted mt-2">{{ $plan['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Search functionality
        document.getElementById('searchProjects').addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.project-row');

            rows.forEach(row => {
                const projectName = row.getAttribute('data-project-name');
                const clientName = row.getAttribute('data-client-name');

                if (projectName.includes(searchTerm) || clientName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function changePlan(projectId, projectName) {
            // Fetch available plans
            fetch('{{ $baseUrl }}api/billing/payment-plans', {
                headers: {
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const plans = data.data;
                        let plansHtml = '';

                        plans.forEach(plan => {
                            plansHtml += `
                                            <div class="bg-white/5 p-4 rounded-xl border border-white/10 hover:border-primary/30 cursor-pointer transition-all plan-option"
                                                onclick="selectPlan(${projectId}, ${plan.id})">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <p class="text-sm font-bold text-p-title">${plan.name}</p>
                                                        <p class="text-xs text-p-muted">${plan.frequency.charAt(0).toUpperCase() + plan.frequency.slice(1)}</p>
                                                    </div>
                                                    <span class="text-lg font-black text-primary italic text-[10px]">Recurrente</span>
                                                </div>
                                                ${plan.description ? `<p class="text-xs text-p-muted">${plan.description}</p>` : ''}
                                            </div>
                                        `;
                        });

                        const html = `
                                        <div class="space-y-4">
                                            <p class="text-sm text-p-muted mb-4">Selecciona el nuevo plan para el proyecto "${projectName}"</p>
                                            ${plansHtml}
                                        </div>
                                    `;

                        showModal({
                            title: 'Cambiar Plan de Pago',
                            message: '',
                            type: 'modal',
                            maxWidth: 'max-w-2xl'
                        });

                        document.getElementById('modal-message').innerHTML = html;
                    }
                });
        }

        function selectPlan(projectId, planId) {
            showModal({
                title: '⚠️ Confirmar Cambio de Plan',
                message: 'Al cambiar el plan se cancelarán las cuotas futuras no pagadas y se generarán nuevas cuotas según el nuevo plan. ¿Deseas continuar?',
                type: 'confirm',
                confirmText: 'Cambiar Plan',
                onConfirm: () => {
                    fetch(`{{ $baseUrl }}api/billing/projects/${projectId}/change-plan`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                        },
                        body: JSON.stringify({ new_plan_id: planId })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showModal({
                                    title: '✅ Plan Actualizado',
                                    message: 'El plan ha sido cambiado exitosamente',
                                    type: 'success',
                                    onConfirm: () => window.location.reload()
                                });
                            } else {
                                showModal({
                                    title: '❌ Error',
                                    message: data.message || 'Error al cambiar el plan',
                                    type: 'error'
                                });
                            }
                        })
                        .catch(err => {
                            showModal({
                                title: '❌ Error',
                                message: 'Error de conexión',
                                type: 'error'
                            });
                        });
                }
            });
        }
    </script>
@endsection