@extends('layouts.main')

@section('title', 'Catálogo de Servicios')

@section('content')
    <div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-black text-p-title tracking-tight mb-2">Catálogo de Servicios</h1>
                <p class="text-p-muted font-medium">Gestiona los servicios y productos disponibles para facturación</p>
            </div>
            <button onclick="openCreateModal()"
                class="px-6 py-3 bg-primary text-dark rounded-xl font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-1 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Servicio
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 border-l-4 border-l-primary">
                <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-1">Total Servicios</p>
                <p class="text-3xl font-black text-p-title">{{ count($services) }}</p>
            </div>
            <!-- More stats could go here -->
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div class="glass-card p-6 flex flex-col h-full group hover:border-primary/30 transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="p-3 bg-primary/10 rounded-xl text-primary group-hover:bg-primary group-hover:text-dark transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editService({{ json_encode($service) }})"
                                class="p-2 text-p-muted hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                                title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button onclick="deleteService({{ $service['id'] }})"
                                class="p-2 text-p-muted hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                                title="Eliminar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-p-title mb-2">{{ $service['name'] }}</h3>
                    <p class="text-sm text-p-muted mb-6 flex-grow">{{ $service['description'] ?: 'Sin descripción' }}</p>

                    <div class="space-y-3 pt-4 border-t border-white/5">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-p-muted">Mensual</span>
                            <span
                                class="font-bold text-primary">${{ number_format((float) ($service['price_monthly'] ?: 0), 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-p-muted">Anual</span>
                            <span
                                class="font-bold text-emerald-500">${{ number_format((float) ($service['price_yearly'] ?: 0), 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-p-muted">Pago Único</span>
                            <span
                                class="font-bold text-amber-500">${{ number_format((float) ($service['price_one_time'] ?: 0), 2) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="serviceModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card w-full max-w-lg p-8 animate-in zoom-in-95 duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 id="modalTitle" class="text-2xl font-black text-p-title tracking-tight">Nuevo Servicio</h2>
                <button onclick="closeServiceModal()" class="p-2 text-p-muted hover:text-p-title transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="serviceForm" class="space-y-4">
                <input type="hidden" name="_token" value="{{ $csrf_token }}">
                <input type="hidden" name="id" id="service_id">
                <div>
                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Nombre del
                        Servicio</label>
                    <input type="text" name="name" required class="form-input" placeholder="Ej: Mantenimiento Web Pro">
                </div>

                <div>
                    <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Descripción</label>
                    <textarea name="description" rows="3" class="form-input resize-none"
                        placeholder="Breve descripción del servicio..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Precio
                            Mes</label>
                        <input type="number" name="price_monthly" step="0.01" class="form-input" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Precio
                            Año</label>
                        <input type="number" name="price_yearly" step="0.01" class="form-input" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Pago
                            Único</label>
                        <input type="number" name="price_one_time" step="0.01" class="form-input" placeholder="0.00">
                    </div>
                </div>

                <div class="pt-6 flex gap-4">
                    <button type="submit" id="btnSaveService"
                        class="flex-1 px-6 py-4 bg-primary text-dark rounded-xl font-black uppercase tracking-widest hover:translate-y-[-2px] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span id="btnText">Guardar Servicio</span>
                        <div id="btnSpinner"
                            class="hidden w-4 h-4 border-2 border-dark/20 border-t-dark rounded-full animate-spin"></div>
                    </button>
                    <button type="button" onclick="closeServiceModal()"
                        class="px-6 py-4 bg-white/5 text-p-muted border border-white/10 rounded-xl font-black uppercase tracking-widest hover:bg-white/10 transition-all">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Nuevo Servicio';
            document.getElementById('service_id').value = '';
            document.getElementById('serviceForm').reset();
            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('serviceModal').classList.add('flex');
        }

        function editService(service) {
            document.getElementById('modalTitle').innerText = 'Editar Servicio';
            document.getElementById('service_id').value = service.id;

            const form = document.getElementById('serviceForm');
            form.querySelector('[name="name"]').value = service.name;
            form.querySelector('[name="description"]').value = service.description || '';
            form.querySelector('[name="price_monthly"]').value = service.price_monthly;
            form.querySelector('[name="price_yearly"]').value = service.price_yearly;
            form.querySelector('[name="price_one_time"]').value = service.price_one_time;

            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('serviceModal').classList.add('flex');
        }

        function closeServiceModal() {
            document.getElementById('serviceModal').classList.add('hidden');
            document.getElementById('serviceModal').classList.remove('flex');
            document.getElementById('serviceForm').reset();
        }

        document.getElementById('serviceForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveService');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            // Loading State
            btn.disabled = true;
            btnText.innerText = 'Guardando...';
            btnSpinner.classList.remove('hidden');

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const serviceId = data.id;

            const url = serviceId
                ? `{{ $baseUrl }}api/billing/services/${serviceId}`
                : '{{ $baseUrl }}api/billing/services';

            const method = serviceId ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}',
                    'X-CSRF-TOKEN': '{{ $csrf_token }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeServiceModal();
                        showModal({
                            title: 'Éxito',
                            message: serviceId ? 'Servicio actualizado correctamente' : 'Servicio creado exitosamente',
                            type: 'success',
                            onConfirm: () => location.reload()
                        });
                    } else {
                        showModal({
                            title: 'Error',
                            message: data.error || 'Error al procesar la solicitud',
                            type: 'error'
                        });
                        // Reset State on error
                        btn.disabled = false;
                        btnText.innerText = 'Guardar Servicio';
                        btnSpinner.classList.add('hidden');
                    }
                })
                .catch(err => {
                    showModal({
                        title: 'Error de Conexión',
                        message: 'No se pudo contactar con el servidor',
                        type: 'error'
                    });
                    btn.disabled = false;
                    btnText.innerText = 'Guardar Servicio';
                    btnSpinner.classList.add('hidden');
                });
        });

        function deleteService(id) {
            showModal({
                title: '¿Eliminar servicio?',
                message: 'Esta acción desactivará el servicio del catálogo. No afectará a proyectos existentes.',
                type: 'confirm',
                confirmText: 'Sí, eliminar',
                onConfirm: () => {
                    fetch(`{{ $baseUrl }}api/billing/services/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}',
                            'X-CSRF-TOKEN': '{{ $csrf_token }}'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showModal({
                                    title: 'Eliminado',
                                    message: 'El servicio ha sido desactivado del catálogo',
                                    type: 'success',
                                    onConfirm: () => location.reload()
                                });
                            } else {
                                showModal({
                                    title: 'Error',
                                    message: data.error || 'No se pudo eliminar el servicio',
                                    type: 'error'
                                });
                            }
                        })
                        .catch(err => {
                            showModal({
                                title: 'Error',
                                message: 'Error al intentar conectar con el servidor',
                                type: 'error'
                            });
                        });
                }
            });
        }
    </script>
@endsection