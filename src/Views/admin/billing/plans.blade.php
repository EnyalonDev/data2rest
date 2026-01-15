@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-blue-500/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-blue-500 text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            💎 Payment Plans
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Planes de Pago
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Gestión de planes de pago y precios
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Planes de Pago</span>
        </nav>
    </div>

    <!-- Actions Bar -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <button onclick="showCreatePlanModal()"
                class="btn-primary !px-6 !py-3 font-black uppercase tracking-widest text-xs italic flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Plan
            </button>
        </div>
        <div class="flex items-center gap-4 w-full md:w-auto">
            <select id="filterStatus" onchange="applyFilters()"
                class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                <option value="all">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8" id="plansGrid">
        @forelse($plans as $plan)
            @php
                $isActive = $plan['status'] === 'active';
                $borderColor = $isActive ? 'border-emerald-500/30' : 'border-slate-500/30';
                $bgColor = $isActive ? 'bg-emerald-500/5' : 'bg-slate-500/5';
            @endphp
            <div class="glass-card !p-6 hover:border-primary/30 transition-all group plan-card relative overflow-hidden"
                data-status="{{ $plan['status'] }}" data-plan-id="{{ $plan['id'] }}">

                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    <span
                        class="px-3 py-1 bg-{{ $isActive ? 'emerald' : 'slate' }}-500/20 text-{{ $isActive ? 'emerald' : 'slate' }}-500 rounded-lg text-[10px] font-black uppercase tracking-widest border border-{{ $isActive ? 'emerald' : 'slate' }}-500/30">
                        {{ ucfirst($plan['status']) }}
                    </span>
                </div>

                <!-- Plan Header -->
                <div class="mb-6 pt-8">
                    <h3 class="text-2xl font-black text-p-title mb-2">{{ $plan['name'] ?? 'Sin nombre' }}</h3>
                    @if(isset($plan['description']) && $plan['description'])
                        <p class="text-sm text-p-muted">{{ $plan['description'] }}</p>
                    @endif
                </div>

                <!-- Frecuencia -->
                <div class="mb-6 p-6 rounded-2xl {{ $bgColor }} border {{ $borderColor }}">
                    <div class="flex items-center gap-2 text-p-title font-black text-xl mb-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Frecuencia: {{ ucfirst($plan['frequency'] ?? 'mensual') }}</span>
                    </div>
                    <p class="text-xs text-p-muted italic">Monto determinado por los servicios contratados</p>
                </div>

                <!-- Plan Details -->
                <div class="space-y-3 mb-6">
                    @if(isset($plan['contract_duration_months']))
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-p-muted">Duración del contrato:</span>
                            <span class="font-bold text-p-title">{{ $plan['contract_duration_months'] }} meses</span>
                        </div>
                    @endif
                    @if(isset($plan['total_installments']))
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-p-muted">Cuotas totales:</span>
                            <span class="text-lg font-black text-primary italic text-xs">Total servicios</span>
                        </div>
                    @endif
                </div>

                <!-- Usage Stats -->
                @php
                    $projectsCount = isset($plan['projects_count']) ? $plan['projects_count'] : 0;
                @endphp
                @if($projectsCount > 0)
                    <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
                        <div class="flex items-center gap-2 text-xs text-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <span class="font-bold">{{ $projectsCount }} proyecto(s) activo(s)</span>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex gap-2">
                    <button onclick="editPlan({{ $plan['id'] }})"
                        class="flex-1 px-4 py-2 bg-primary/10 hover:bg-primary hover:text-dark border border-primary/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary transition-all">
                        Editar
                    </button>
                    <button
                        onclick="togglePlanStatus({{ $plan['id'] }}, '{{ $plan['status'] }}', '{{ addslashes($plan['name']) }}')"
                        class="flex-1 px-4 py-2 bg-{{ $isActive ? 'slate' : 'emerald' }}-500/10 hover:bg-{{ $isActive ? 'slate' : 'emerald' }}-500 hover:text-dark border border-{{ $isActive ? 'slate' : 'emerald' }}-500/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-{{ $isActive ? 'slate' : 'emerald' }}-500 transition-all">
                        {{ $isActive ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full glass-card !p-12 text-center">
                <div
                    class="w-20 h-20 bg-blue-500/10 rounded-full flex items-center justify-center text-blue-500 text-3xl mx-auto mb-6">
                    💎
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">No hay planes de pago</h3>
                <p class="text-p-muted mb-6">Comienza creando tu primer plan de pago</p>
                <button onclick="showCreatePlanModal()"
                    class="btn-primary !px-8 !py-3 font-black uppercase tracking-widest text-xs italic">
                    Crear Plan
                </button>
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        // Filter functionality
        function applyFilters() {
            const status = document.getElementById('filterStatus').value;
            const cards = document.querySelectorAll('.plan-card');

            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (status === 'all' || cardStatus === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function showCreatePlanModal() {
            const html = `
                                <form id="createPlanForm" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Nombre del Plan *</label>
                                            <input type="text" name="name" required
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none"
                                                placeholder="Ej: Plan Premium">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Descripción</label>
                                            <textarea name="description" rows="3"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none resize-none"
                                                placeholder="Descripción del plan..."></textarea>
                                        </div>


                                        <div>
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Frecuencia *</label>
                                            <select name="frequency" required
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                <option value="">Seleccionar...</option>
                                                <option value="mensual">Mensual</option>
                                                <option value="anual">Anual</option>
                                                <option value="unico">Pago Único / Especial</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Duración del Contrato (meses) *</label>
                                            <input type="number" name="contract_duration_months" required min="1"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none"
                                                placeholder="12">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Total de Cuotas *</label>
                                            <input type="number" name="total_installments" required min="1"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none"
                                                placeholder="12">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Estado</label>
                                            <select name="status"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                <option value="active">Activo</option>
                                                <option value="inactive">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            `;

            showModal({
                title: 'Crear Nuevo Plan de Pago',
                message: '',
                type: 'confirm',
                confirmText: 'Crear Plan',
                maxWidth: 'max-w-3xl',
                onConfirm: () => createPlan()
            });

            document.getElementById('modal-message').innerHTML = html;
        }

        function createPlan() {
            const form = document.getElementById('createPlanForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            fetch('{{ $baseUrl }}api/billing/payment-plans', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal({
                            title: '✅ Plan Creado',
                            message: 'El plan de pago ha sido creado exitosamente',
                            type: 'success',
                            onConfirm: () => window.location.reload()
                        });
                    } else {
                        showModal({
                            title: '❌ Error',
                            message: data.message || 'Error al crear el plan',
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

        function editPlan(id) {
            // Fetch plan data
            fetch(`{{ $baseUrl }}api/billing/payment-plans/${id}`, {
                headers: {
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const plan = data.data;
                        const html = `
                                        <form id="editPlanForm" class="space-y-6">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Nombre del Plan *</label>
                                                    <input type="text" name="name" value="${plan.name}" required
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Descripción</label>
                                                    <textarea name="description" rows="3"
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none resize-none">${plan.description || ''}</textarea>
                                                </div>


                                                <div>
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Frecuencia *</label>
                                                    <select name="frequency" required
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                        <option value="mensual" ${plan.frequency === 'mensual' ? 'selected' : ''}>Mensual</option>
                                                        <option value="anual" ${plan.frequency === 'anual' ? 'selected' : ''}>Anual</option>
                                                        <option value="unico" ${plan.frequency === 'unico' ? 'selected' : ''}>Pago Único / Especial</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Duración del Contrato (meses) *</label>
                                                    <input type="number" name="contract_duration_months" value="${plan.contract_duration_months}" required min="1"
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Total de Cuotas *</label>
                                                    <input type="number" name="total_installments" value="${plan.total_installments}" required min="1"
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Estado</label>
                                                    <select name="status"
                                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                                        <option value="active" ${plan.status === 'active' ? 'selected' : ''}>Activo</option>
                                                        <option value="inactive" ${plan.status === 'inactive' ? 'selected' : ''}>Inactivo</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </form>
                                    `;

                        showModal({
                            title: 'Editar Plan de Pago',
                            message: '',
                            type: 'confirm',
                            confirmText: 'Guardar Cambios',
                            maxWidth: 'max-w-3xl',
                            onConfirm: () => updatePlan(id)
                        });

                        document.getElementById('modal-message').innerHTML = html;
                    }
                });
        }

        function updatePlan(id) {
            const form = document.getElementById('editPlanForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            fetch(`{{ $baseUrl }}api/billing/payment-plans/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal({
                            title: '✅ Plan Actualizado',
                            message: 'Los cambios han sido guardados',
                            type: 'success',
                            onConfirm: () => window.location.reload()
                        });
                    } else {
                        showModal({
                            title: '❌ Error',
                            message: data.message || 'Error al actualizar el plan',
                            type: 'error'
                        });
                    }
                });
        }

        function togglePlanStatus(id, currentStatus, name) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activar' : 'desactivar';

            showModal({
                title: `⚠️ ${action.charAt(0).toUpperCase() + action.slice(1)} Plan`,
                message: `¿Estás seguro de que deseas ${action} el plan "${name}"?`,
                type: 'confirm',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1),
                onConfirm: () => {
                    fetch(`{{ $baseUrl }}api/billing/payment-plans/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showModal({
                                    title: '✅ Estado Actualizado',
                                    message: `El plan ha sido ${action}do exitosamente`,
                                    type: 'success',
                                    onConfirm: () => window.location.reload()
                                });
                            } else {
                                showModal({
                                    title: '❌ Error',
                                    message: data.message || 'Error al actualizar el estado',
                                    type: 'error'
                                });
                            }
                        });
                }
            });
        }
    </script>
@endsection