@extends('layouts.main')

@section('title', ($project ? 'Editar Proyecto' : 'Nuevo Proyecto'))

@section('content')
    <div class="max-w-6xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-700">
        <div class="mb-8">
            <h1 class="text-4xl font-black text-p-title tracking-tight mb-2">
                {{ $project ? 'Editar Proyecto' : 'Nuevo Proyecto' }}
            </h1>
            <p class="text-p-muted font-medium">Configura los detalles del proyecto, el equipo y la facturación</p>
        </div>

        <form action="{{ $baseUrl }}admin/projects/save" method="POST" id="projectForm" class="space-y-8">
            {!! $csrf_field !!}
            @if ($project)
                <input type="hidden" name="id" value="{{ $project['id'] }}">
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna Izquierda: Información General -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Información Básica -->
                    <div class="glass-card p-8">
                        <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                            <span class="w-8 h-[1px] bg-slate-800"></span> Información General
                        </h3>
                        <div class="space-y-6">
                            <div>
                                <label class="form-label mb-2">Nombre del Proyecto</label>
                                <input type="text" name="name" value="{{ $project['name'] ?? '' }}" class="form-input"
                                    placeholder="Ej: Rediseño Ecommerce 2024" required>
                            </div>
                            <div>
                                <label class="form-label mb-2">Descripción</label>
                                <textarea name="description" rows="3" class="form-input resize-none"
                                    placeholder="Breve resumen del proyecto...">{{ $project['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Selección de Equipo y Responsable de Pago -->
                    <div class="glass-card p-8">
                        <div class="flex items-center justify-between mb-8 border-b border-white/5 pb-4">
                            <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="w-8 h-[1px] bg-slate-800"></span> Equipo y Facturación
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Buscador de Usuarios -->
                            <div>
                                <label class="form-label mb-4">Añadir Miembros</label>
                                <div class="relative mb-4">
                                    <input type="text" id="userQuery" class="form-input !pl-10"
                                        placeholder="Buscar por nombre o correo...">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-p-muted" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <div id="searchResults" class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                                    <!-- JS populate -->
                                </div>
                            </div>

                            <!-- Lista de Asignados -->
                            <div>
                                <label class="form-label mb-4">Usuarios Asignados</label>
                                <div id="assignedList" class="space-y-3">
                                    <!-- JS populate -->
                                </div>
                                <input type="hidden" name="billing_user_id" id="billing_user_id_input"
                                    value="{{ $project['billing_user_id'] ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Servicios -->
                    <div class="glass-card p-8">
                        <div class="flex items-center justify-between mb-8 border-b border-white/5 pb-4">
                            <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="w-8 h-[1px] bg-slate-800"></span> Servicios Contratados
                            </h3>
                            <button type="button" onclick="openServicesModal()"
                                class="text-xs font-black text-primary uppercase tracking-widest hover:underline">+ Añadir
                                Servicio</button>
                        </div>

                        <div id="projectServicesList" class="space-y-4">
                            <div class="text-center py-8 text-p-muted italic text-sm empty-msg">No hay servicios añadidos
                                aún</div>
                        </div>

                        <div class="mt-8 pt-8 border-t border-white/5 flex justify-between items-center">
                            <span class="text-p-muted font-bold">Total Presupuestado:</span>
                            <span class="text-2xl font-black text-primary" id="grandTotalDisplay">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Configuración del Plan -->
                <div class="space-y-8">
                    <div class="glass-card p-8 sticky top-8">
                        <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                            <span class="w-8 h-[1px] bg-slate-800"></span> Ciclo de Facturación
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <label class="form-label mb-2">Frecuencia / Plan</label>
                                <select name="current_plan_id" class="form-input" id="planSelect">
                                    <option value="">-- Seleccionar Plan --</option>
                                    @foreach($billingPlans as $plan)
                                        <option value="{{ $plan['id'] }}" data-freq="{{ $plan['frequency'] }}" {{ ($project['current_plan_id'] ?? '') == $plan['id'] ? 'selected' : '' }}>
                                            {{ $plan['name'] }} ({{ $plan['frequency'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label mb-2">Fecha de Inicio</label>
                                <input type="date" name="start_date"
                                    value="{{ date('Y-m-d', strtotime($project['start_date'] ?? 'now')) }}"
                                    class="form-input">
                            </div>

                            @if(\App\Core\Auth::isAdmin())
                                <div>
                                    <label class="form-label mb-2">Cuota de Almacenamiento (MB)</label>
                                    <input type="number" name="storage_quota" value="{{ $project['storage_quota'] ?? 300 }}"
                                        class="form-input">
                                </div>
                            @endif

                            <div class="pt-6">
                                <button type="submit"
                                    class="w-full px-8 py-4 bg-primary text-dark rounded-xl font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-1 transition-all">
                                    {{ $project ? 'Guardar Cambios' : 'Crear Proyecto' }}
                                </button>
                                <a href="{{ $baseUrl }}admin/projects"
                                    class="block text-center mt-4 text-xs font-black text-p-muted uppercase tracking-widest hover:text-red-500 transition-all">Cancelar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal de Selección de Servicios -->
    <div id="servicesModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card w-full max-w-2xl p-8 animate-in zoom-in-95 duration-300">
            <div class="flex justify-between items-center mb-6 border-b border-white/5 pb-4">
                <h2 class="text-2xl font-black text-p-title tracking-tight">Catálogo de Servicios</h2>
                <button onclick="closeServicesModal()" class="p-2 text-p-muted hover:text-p-title transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar p-1">
                <div id="catalogList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- JS populate -->
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        const allUsers = {!! json_encode($users) !!};
        const billingPlans = {!! json_encode($billingPlans) !!};
        let assignedUserIds = {!! json_encode($project['user_ids'] ?? []) !!};
        let billingUserId = '{{ $project['billing_user_id'] ?? '' }}';
        let projectServices = []; // Items: {service_id, name, price, quantity, period}

        // --- Initialization ---
        document.addEventListener('DOMContentLoaded', () => {
            renderUsers();
            fetchProjectServices();

            document.getElementById('userQuery').addEventListener('input', renderUsers);
            document.getElementById('planSelect').addEventListener('change', autoUpdateServicePricing);
        });

        function autoUpdateServicePricing() {
            const planSelect = document.getElementById('planSelect');
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            const frequency = selectedOption.getAttribute('data-freq');

            if (!frequency || frequency === '') return;

            // Map billing plan frequency to service period
            let targetPeriod = 'monthly';
            if (frequency.toLowerCase() === 'yearly' || frequency.toLowerCase() === 'anual') targetPeriod = 'yearly';
            if (frequency.toLowerCase() === 'monthly' || frequency.toLowerCase() === 'mensual') targetPeriod = 'monthly';
            if (frequency.toLowerCase() === 'unico' || frequency.toLowerCase() === 'one_time') targetPeriod = 'unico';

            projectServices.forEach((ps, index) => {
                ps.billing_period = targetPeriod;
                ps.custom_price = getDefaultPriceForPeriod(ps, targetPeriod);
            });

            renderProjectServices();
        }

        function getDefaultPriceForPeriod(ps, period) {
            switch (period) {
                case 'monthly': return ps.price_monthly;
                case 'yearly': return ps.price_yearly;
                case 'unico': return ps.price_one_time;
                default: return ps.price_monthly;
            }
        }

        // --- User Management ---
        function renderUsers() {
            const query = document.getElementById('userQuery').value.toLowerCase();
            const results = document.getElementById('searchResults');
            const assigned = document.getElementById('assignedList');

            results.innerHTML = '';
            assigned.innerHTML = '';

            allUsers.forEach(user => {
                const isAssigned = assignedUserIds.includes(user.id.toString()) || assignedUserIds.includes(parseInt(user.id));
                const matchesQuery = user.username.toLowerCase().includes(query) || (user.email && user.email.toLowerCase().includes(query)) || (user.public_name && user.public_name.toLowerCase().includes(query));

                if (isAssigned) {
                    const isPayer = billingUserId == user.id;
                    const hasBillingData = user.email && user.phone && user.tax_id;

                    const card = document.createElement('div');
                    card.className = `flex flex-col p-4 bg-white/5 border ${isPayer ? 'border-primary/50 bg-primary/5' : 'border-white/5'} rounded-xl transition-all group`;
                    card.innerHTML = `
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg ${isPayer ? 'bg-primary' : 'bg-emerald-500/10'} flex items-center justify-center text-xs font-black ${isPayer ? 'text-dark' : 'text-emerald-500'}">
                                                    ${user.username.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-p-title">${user.public_name || user.username}</p>
                                                    ${!hasBillingData && isPayer ? '<p class="text-[10px] text-amber-500 font-bold uppercase tracking-widest">⚠️ Falta info de factura</p>' : ''}
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                ${!isPayer ? `<button type="button" onclick="setAsPayer(${user.id})" class="text-[9px] font-black uppercase tracking-widest text-primary/50 hover:text-primary transition-all">Definir Pago</button>` : '<span class="text-[9px] font-black uppercase tracking-widest text-primary">RESPONSABLE</span>'}
                                                <button type="button" onclick="removeUser(${user.id})" class="text-p-muted hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="user_ids[]" value="${user.id}">
                                    `;
                    assigned.appendChild(card);
                } else if (matchesQuery && query.length > 0) {
                    const item = document.createElement('div');
                    item.className = 'p-3 bg-white/5 border border-white/5 rounded-xl flex items-center justify-between hover:border-primary/50 cursor-pointer transition-all hover:bg-white/10';
                    item.onclick = () => addUser(user.id);
                    item.innerHTML = `
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-bold text-p-title">${user.username}</span>
                                            <span class="text-xs text-p-muted">${user.email || ''}</span>
                                        </div>
                                        <span class="text-xs font-black text-primary">+ Añadir</span>
                                    `;
                    results.appendChild(item);
                }
            });

            if (assignedUserIds.length === 0) {
                assigned.innerHTML = '<div class="text-center py-4 text-p-muted italic text-xs">No hay usuarios asignados</div>';
            }
        }

        function addUser(id) {
            if (!assignedUserIds.includes(id)) {
                assignedUserIds.push(id);
                if (!billingUserId) billingUserId = id; // Auto-set first user as payer
                renderUsers();
                updateBillingInput();
            }
        }

        function removeUser(id) {
            assignedUserIds = assignedUserIds.filter(uid => uid != id);
            if (billingUserId == id) billingUserId = assignedUserIds.length > 0 ? assignedUserIds[0] : '';
            renderUsers();
            updateBillingInput();
        }

        function setAsPayer(id) {
            billingUserId = id;
            renderUsers();
            updateBillingInput();
        }

        function updateBillingInput() {
            document.getElementById('billing_user_id_input').value = billingUserId;
        }

        // --- Services Management ---
        function openServicesModal() {
            document.getElementById('servicesModal').classList.remove('hidden');
            document.getElementById('servicesModal').classList.add('flex');
            loadCatalog();
        }

        function closeServicesModal() {
            document.getElementById('servicesModal').classList.add('hidden');
            document.getElementById('servicesModal').classList.remove('flex');
        }

        function loadCatalog() {
            fetch('{{ $baseUrl }}api/billing/services', {
                headers: { 'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}' }
            })
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('catalogList');
                    list.innerHTML = '';
                    data.data.forEach(service => {
                        const item = document.createElement('div');
                        item.className = 'glass-card p-4 hover:border-primary/50 cursor-pointer transition-all';
                        item.onclick = () => addServiceToProject(service);
                        item.innerHTML = `
                                        <p class="font-bold text-p-title text-sm mb-1">${service.name}</p>
                                        <p class="text-[10px] text-p-muted mb-2">${service.description || ''}</p>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="text-[9px] font-black text-primary uppercase border border-primary/20 px-2 rounded-full">Mes: $${service.price_monthly}</span>
                                            <span class="text-[9px] font-black text-emerald-500 uppercase border border-emerald-500/20 px-2 rounded-full">Año: $${service.price_yearly}</span>
                                            <span class="text-[9px] font-black text-amber-500 uppercase border border-amber-500/20 px-2 rounded-full">Pago Único: $${service.price_one_time}</span>
                                        </div>
                                    `;
                        list.appendChild(item);
                    });
                });
        }

        function addServiceToProject(service) {
            const id = 'temp_' + Date.now();
            const planSelect = document.getElementById('planSelect');
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            const frequency = selectedOption ? selectedOption.getAttribute('data-freq') : 'monthly';

            let initialPeriod = 'monthly';
            if (frequency && frequency.toLowerCase().includes('anual')) initialPeriod = 'yearly';
            if (frequency && frequency.toLowerCase().includes('yearly')) initialPeriod = 'yearly';
            if (frequency && frequency.toLowerCase().includes('unico')) initialPeriod = 'unico';

            const ps = {
                id: id,
                service_id: service.id,
                name: service.name,
                price_monthly: parseFloat(service.price_monthly || 0),
                price_yearly: parseFloat(service.price_yearly || 0),
                price_one_time: parseFloat(service.price_one_time || 0),
                billing_period: initialPeriod,
                quantity: 1
            };

            ps.custom_price = getDefaultPriceForPeriod(ps, initialPeriod);

            projectServices.push(ps);
            renderProjectServices();
            closeServicesModal();
        }

        function renderProjectServices() {
            const list = document.getElementById('projectServicesList');
            const emptyMsg = list.querySelector('.empty-msg');
            if (emptyMsg) emptyMsg.remove();

            const containers = list.querySelectorAll('.service-item');
            containers.forEach(c => c.remove());

            projectServices.forEach((ps, index) => {
                const el = document.createElement('div');
                el.className = 'service-item glass-card !bg-white/5 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 animate-in slide-in-from-right-2';
                el.innerHTML = `
                                    <div class="flex-grow">
                                        <p class="text-sm font-bold text-p-title mb-2">${ps.name}</p>
                                        <input type="hidden" name="services[${index}][service_id]" value="${ps.service_id}">

                                        <div class="flex flex-wrap gap-4 items-center">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-[9px] font-black text-p-muted uppercase tracking-widest leading-none">Periodo</span>
                                                <select name="services[${index}][billing_period]" onchange="updatePeriod(${index}, this.value)"
                                                    class="text-[10px] font-black uppercase tracking-widest bg-white/5 border border-white/10 rounded px-2 py-1.5 text-p-title focus:ring-0 outline-none">
                                                    <option value="monthly" ${ps.billing_period === 'monthly' ? 'selected' : ''}>Precio Mensual</option>
                                                    <option value="yearly" ${ps.billing_period === 'yearly' ? 'selected' : ''}>Precio Anual</option>
                                                    <option value="unico" ${ps.billing_period === 'unico' ? 'selected' : ''}>Pago Único</option>
                                                </select>
                                            </div>

                                            <div class="flex flex-col gap-1">
                                                <span class="text-[9px] font-black text-p-muted uppercase tracking-widest leading-none">Precio Unitario</span>
                                                <div class="flex items-center gap-1 bg-white/5 border border-white/10 rounded px-2 h-[29px]">
                                                    <span class="text-[10px] text-p-muted font-bold">$</span>
                                                    <input type="number" step="0.01" name="services[${index}][custom_price]" 
                                                        value="${ps.custom_price}" 
                                                        onchange="updateCustomPrice(${index}, this.value)"
                                                        class="w-20 bg-transparent text-[10px] font-bold text-p-title border-none p-0 focus:ring-0 outline-none">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-[9px] font-black text-p-muted uppercase tracking-widest leading-none">Cant.</span>
                                            <div class="flex items-center border border-white/10 rounded-lg overflow-hidden h-[29px]">
                                                <button type="button" onclick="updateQty(${index}, -1)" class="px-2 py-1 hover:bg-white/10 text-p-muted">-</button>
                                                <input type="number" name="services[${index}][quantity]" value="${ps.quantity}" class="w-10 bg-transparent text-center text-xs font-bold border-none focus:ring-0" readonly>
                                                <button type="button" onclick="updateQty(${index}, 1)" class="px-2 py-1 hover:bg-white/10 text-p-muted">+</button>
                                            </div>
                                        </div>
                                        <div class="text-right min-w-[100px]">
                                            <p class="text-[10px] text-p-muted uppercase font-black">Subtotal</p>
                                            <p class="text-sm font-black text-primary">$${(ps.custom_price * ps.quantity).toFixed(2)}</p>
                                        </div>
                                        <button type="button" onclick="removeService(${index})" class="text-p-muted hover:text-red-500 mt-3 md:mt-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                    </div>
                                `;
                list.appendChild(el);
            });

            if (projectServices.length === 0) {
                list.innerHTML = '<div class="text-center py-8 text-p-muted italic text-sm empty-msg">No hay servicios añadidos aún</div>';
            }

            calculateTotal();
        }

        function updateQty(index, delta) {
            projectServices[index].quantity = Math.max(1, projectServices[index].quantity + delta);
            renderProjectServices();
        }

        function removeService(index) {
            projectServices.splice(index, 1);
            renderProjectServices();
        }

        function updatePeriod(index, period) {
            projectServices[index].billing_period = period;
            projectServices[index].custom_price = getDefaultPriceForPeriod(projectServices[index], period);
            renderProjectServices();
        }

        function updateCustomPrice(index, value) {
            projectServices[index].custom_price = parseFloat(value) || 0;
            // No full render to keep focus, just update display
            calculateTotal();
            // Actually, for consistency let's update the subtotal in DOM
            const containers = document.querySelectorAll('.service-item');
            const subtotalEl = containers[index].querySelector('.text-primary');
            if (subtotalEl) {
                subtotalEl.innerText = '$' + (projectServices[index].custom_price * projectServices[index].quantity).toFixed(2);
            }
        }

        function getCurrentPrice(ps) {
            return (ps.custom_price || 0) * ps.quantity;
        }

        function calculateTotal() {
            let total = 0;
            projectServices.forEach(ps => {
                total += getCurrentPrice(ps);
            });
            document.getElementById('grandTotalDisplay').innerText = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function fetchProjectServices() {
            @if($project)
                fetch('{{ $baseUrl }}api/billing/projects/{{ $project['id'] }}/services', {
                    headers: { 'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}' }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            projectServices = data.data.map(s => ({
                                id: s.id,
                                service_id: s.service_id,
                                name: s.name,
                                price_monthly: parseFloat(s.price_monthly || 0),
                                price_yearly: parseFloat(s.price_yearly || 0),
                                price_one_time: parseFloat(s.price_one_time || 0),
                                custom_price: parseFloat(s.custom_price || 0),
                                billing_period: s.billing_period || 'monthly',
                                quantity: parseInt(s.quantity)
                            }));
                            renderProjectServices();
                        }
                    });
            @endif
                        }

        // --- Form Submission ---
        document.getElementById('projectForm').addEventListener('submit', function (e) {
            if (!billingUserId) {
                e.preventDefault();
                showModal({
                    title: 'Validación',
                    message: 'Debes definir un responsable de pago para el proyecto',
                    type: 'error'
                });
                return;
            }

            // Services are already in input fields, no need for extra JSON
        });

    </script>
@endsection