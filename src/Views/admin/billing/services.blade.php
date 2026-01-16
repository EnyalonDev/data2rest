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
                            <button onclick="openTemplatesModal({{ $service['id'] }}, '{{ $service['name'] }}')"
                                class="p-2 text-p-muted hover:text-purple-500 hover:bg-purple-500/10 rounded-lg transition-all"
                                title="Plantillas de Tareas">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </button>
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

    <!-- Templates Modal -->
    <div id="templatesModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card w-full max-w-2xl p-8 animate-in zoom-in-95 duration-300 flex flex-col max-h-[90vh]">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-p-title tracking-tight">Plantillas: <span id="tplServiceTitle"
                        class="text-primary"></span></h2>
                <div class="flex gap-2">
                    <button onclick="openExportModal()"
                        class="p-2 text-p-muted hover:text-green-500 hover:bg-green-500/10 rounded-lg transition-all"
                        title="Ver JSON / Copiar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m2 4h6m-6 4h6m-6 4h6M10 11a19 19 0 010 6" />
                        </svg>
                    </button>
                    <button onclick="openPasteModal()"
                        class="p-2 text-p-muted hover:text-purple-500 hover:bg-purple-500/10 rounded-lg transition-all"
                        title="Pegar Código JSON">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </button>
                    <!-- Hidden file input removed as requested to rely on copy/paste -->
                    <div class="h-6 w-px bg-gray-700 mx-2"></div>
                    <button onclick="closeTemplatesModal()" class="p-2 text-p-muted hover:text-p-title transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto mb-6 pr-2" id="templatesList">
                <!-- Templates Rendered Here -->
                <div class="text-center text-p-muted py-8">Cargando plantillas...</div>
            </div>

            <form id="templateForm" class="pt-4 border-t border-white/10">
                <input type="hidden" name="id" id="tplId">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-bold text-white" id="tplFormTitle">Agregar Nueva Tarea a Plantilla</h4>
                    <button type="button" id="btnCancelEditTpl" onclick="cancelEditTemplate()"
                        class="text-xs text-red-400 hover:text-red-300 hidden">Cancelar Edición</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <input type="text" name="title" class="form-input text-sm" placeholder="Título de la tarea"
                            required>
                    </div>
                    <div class="md:col-span-3">
                        <select name="priority" class="form-input text-sm text-white bg-dark">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" id="btnSaveTemplate"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg text-sm transition-all shadow-lg hover:shadow-blue-500/20">
                            Agregar
                        </button>
                    </div>
                    <div class="md:col-span-12">
                        <input type="text" name="description" class="form-input text-sm"
                            placeholder="Descripción opcional (breve)">
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Existing Scripts...
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

                        // Check if it was a create or update
                        const isUpdate = !!serviceId; // serviceId comes from scope above
                        const newId = data.id || serviceId;

                        if (!isUpdate && newId) {
                            // Prompt to manage templates
                            showModal({
                                title: 'Servicio Creado',
                                message: 'El servicio se creó exitosamente. ¿Quieres configurar las plantillas de tareas ahora?',
                                type: 'confirm',
                                confirmText: 'Sí, configurar plantillas',
                                cancelText: 'No, finalizar',
                                onConfirm: () => {
                                    // Manually add the new card logic or just reload?
                                    // Reloading is safer to ensure state consistency.
                                    // If we reload, we lose the intention to open modal.
                                    // We can just set a sessionStorage flag or query param.
                                    // Or simpler: Open modal now, but the background grid is stale. 
                                    // Let's open modal. User can refresh later.
                                    // We need the service name. It's in formData.
                                    openTemplatesModal(newId, formData.get('name'));
                                },
                                onCancel: () => location.reload()
                            });
                        } else {
                            showModal({
                                title: 'Éxito',
                                message: 'Servicio actualizado correctamente',
                                type: 'success',
                                onConfirm: () => location.reload()
                            });
                        }
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

        // --- Template Management Scripts ---

        let currentServiceId = null;

        function openTemplatesModal(serviceId, serviceName) {
            currentServiceId = serviceId;
            document.getElementById('tplServiceTitle').innerText = serviceName;
            document.getElementById('templatesModal').classList.remove('hidden');
            document.getElementById('templatesModal').classList.add('flex');

            // Clear form
            document.getElementById('templateForm').reset();

            loadTemplates(serviceId);
        }

        function closeTemplatesModal() {
            document.getElementById('templatesModal').classList.add('hidden');
            document.getElementById('templatesModal').classList.remove('flex');
        }

        function loadTemplates(serviceId) {
            const list = document.getElementById('templatesList');
            list.innerHTML = '<div class="text-center text-p-muted py-8">Cargando plantillas...</div>';

            fetch(`{{ $baseUrl }}api/billing/services/${serviceId}/templates`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderTemplates(data.data);
                    } else {
                        list.innerHTML = `<div class="text-red-500 text-center py-4">Error: ${data.error}</div>`;
                    }
                })
                .catch(err => {
                    list.innerHTML = `<div class="text-red-500 text-center py-4">Error de conexión</div>`;
                });
        }

        function renderTemplates(templates) {
            const list = document.getElementById('templatesList');
            if (templates.length === 0) {
                list.innerHTML = '<div class="text-center text-p-muted py-8 italic">No hay tareas de plantilla definidas.</div>';
                return;
            }

            list.innerHTML = `<div class="space-y-2">
                                            ${templates.map(t => `
                                                <div class="flex items-center justify-between p-3 bg-white/5 border border-white/10 rounded-lg group hover:border-white/20 transition-all">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <span class="font-bold text-gray-200 text-sm">${t.title}</span>
                                                            <span class="text-xs uppercase px-1.5 py-0.5 rounded font-bold ${getPriorityClass(t.priority)}">${t.priority}</span>
                                                        </div>
                                                        ${t.description ? `<p class="text-xs text-gray-400">${t.description}</p>` : ''}
                                                    </div>
                                                    <div class="flex gap-1">
                                                        <button onclick='editTemplate(${JSON.stringify(t)})' class="p-2 text-gray-500 hover:text-blue-500 transition-colors" title="Editar">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                        </button>
                                                        <button onclick="deleteTemplate(${t.id})" class="p-2 text-gray-500 hover:text-red-500 transition-colors" title="Eliminar">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>`;
        }

        function getPriorityClass(p) {
            switch (p) {
                case 'high': return 'bg-red-500/20 text-red-500';
                case 'medium': return 'bg-yellow-500/20 text-yellow-500';
                case 'low': return 'bg-blue-500/20 text-blue-500';
                default: return 'bg-gray-500/20 text-gray-500';
            }
        }

        document.getElementById('templateForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const id = document.getElementById('tplId').value;
            const isEdit = !!id;

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = '...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            const url = isEdit
                ? `{{ $baseUrl }}api/billing/services/templates/${id}`
                : `{{ $baseUrl }}api/billing/services/${currentServiceId}/templates`;

            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ $csrf_token }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        cancelEditTemplate(); // Reset form
                        loadTemplates(currentServiceId);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                });
        });

        function editTemplate(tpl) {
            document.getElementById('tplId').value = tpl.id;
            document.getElementById('tplFormTitle').innerText = 'Editar Tarea de Plantilla';
            document.getElementById('btnSaveTemplate').innerText = 'Actualizar';
            document.getElementById('btnCancelEditTpl').classList.remove('hidden');

            const form = document.getElementById('templateForm');
            form.querySelector('[name="title"]').value = tpl.title;
            form.querySelector('[name="description"]').value = tpl.description || '';
            form.querySelector('[name="priority"]').value = tpl.priority;
        }

        function cancelEditTemplate() {
            document.getElementById('tplId').value = '';
            document.getElementById('tplFormTitle').innerText = 'Agregar Nueva Tarea a Plantilla';
            document.getElementById('btnSaveTemplate').innerText = 'Agregar';
            document.getElementById('btnCancelEditTpl').classList.add('hidden');
            document.getElementById('templateForm').reset();
        }

        function openExportModal() {
            const btn = document.querySelector('button[title="Ver JSON / Copiar"]');
            btn.classList.add('animate-pulse');

            fetch(`{{ $baseUrl }}api/billing/services/${currentServiceId}/templates/export-data`)
                .then(res => res.json())
                .then(data => {
                    btn.classList.remove('animate-pulse');
                    if (data.success) {
                        const jsonContent = JSON.stringify(data.data, null, 2);

                        Swal.fire({
                            title: 'Copiar Plantilla',
                            html: `
                                    <p class="mb-4 text-p-muted">Copia el siguiente código JSON para guardarlo o importarlo en otro servicio.</p>
                                    <div class="relative">
                                        <textarea id="exportJsonArea" class="w-full h-64 bg-black/30 border border-gray-700 rounded-lg p-4 font-mono text-sm text-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent resize-none" readonly>${jsonContent}</textarea>
                                        <button onclick="copyToClipboard()" class="absolute top-2 right-2 p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs text-white border border-gray-600">Copiar</button>
                                    </div>
                                `,
                            width: '600px',
                            showConfirmButton: false,
                            showCloseButton: true,
                            background: '#1f2937',
                            color: '#fff'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al exportar',
                            text: data.error,
                            background: '#1f2937',
                            color: '#fff'
                        });
                    }
                })
                .catch(err => {
                    btn.classList.remove('animate-pulse');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor. Verifica tu conexión.',
                        background: '#1f2937',
                        color: '#fff'
                    });
                });
        }

        function copyToClipboard() {
            const copyText = document.getElementById("exportJsonArea");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(() => {
                Swal.showValidationMessage('¡Copiado al portapapeles!');
                setTimeout(() => Swal.resetValidationMessage(), 2000);
            });
        }


        function openPasteModal() {
            Swal.fire({
                title: 'Importar Plantilla',
                html: `
                            <p class="mb-4 text-p-muted">Pega el código JSON de la plantilla que deseas importar.</p>
                            <div class="relative">
                                <textarea id="importJsonArea" class="w-full h-64 bg-black/30 border border-gray-700 rounded-lg p-4 font-mono text-sm text-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent resize-none" placeholder='[{"title":"Tarea ejemplo","priority":"medium","description":"Descripción"}]'></textarea>
                            </div>
                        `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Importar',
                cancelButtonText: 'Cancelar',
                background: '#1f2937',
                color: '#fff',
                preConfirm: () => {
                    const content = document.getElementById('importJsonArea').value.trim();
                    if (!content) {
                        Swal.showValidationMessage('Por favor, pega el contenido JSON');
                        return false;
                    }
                    try {
                        JSON.parse(content);
                        return content;
                    } catch (e) {
                        Swal.showValidationMessage('El contenido no es un JSON válido');
                        return false;
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    importFromContent(result.value);
                }
            });
        }

        function importFromContent(content) {
            fetch(`{{ $baseUrl }}api/billing/services/${currentServiceId}/templates/import`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ $csrf_token }}'
                },
                body: JSON.stringify({ content: content })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Importación exitosa!',
                            text: `Se importaron ${data.count} plantilla(s) correctamente.`,
                            background: '#1f2937',
                            color: '#fff',
                            confirmButtonText: 'Aceptar'
                        });
                        loadTemplates(currentServiceId);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al importar',
                            text: data.error,
                            background: '#1f2937',
                            color: '#fff'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor. Verifica tu conexión.',
                        background: '#1f2937',
                        color: '#fff'
                    });
                });
        }

        function importTemplates(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);

            fetch(`{{ $baseUrl }}api/billing/services/${currentServiceId}/templates/import`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf_token }}' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(`Se importaron ${data.count} plantillas correctamente.`);
                        loadTemplates(currentServiceId);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .finally(() => input.value = '');
        }

        function deleteTemplate(id) {
            Swal.fire({
                title: '¿Eliminar plantilla?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: '#1f2937',
                color: '#fff',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ $baseUrl }}api/billing/services/templates/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ $csrf_token }}'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminada!',
                                    text: 'La plantilla ha sido eliminada correctamente.',
                                    background: '#1f2937',
                                    color: '#fff',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                loadTemplates(currentServiceId);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al eliminar',
                                    text: data.error,
                                    background: '#1f2937',
                                    color: '#fff'
                                });
                            }
                        });
                }
            });
        }


    </script>
    <!-- SweetAlert2 for beautiful modals -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection