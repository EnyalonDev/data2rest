@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-primary/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-primary text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            👥 Clients Management
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Clientes
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Gestión de clientes y su información de facturación
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Clientes</span>
        </nav>
    </div>

    <!-- Actions Bar -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <button onclick="showCreateClientModal()"
                class="btn-primary !px-6 !py-3 font-black uppercase tracking-widest text-xs italic flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Cliente
            </button>
        </div>
        <div class="flex items-center gap-4 w-full md:w-auto">
            <input type="text" id="searchClients" placeholder="Buscar clientes..."
                class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder-p-muted focus:border-primary/50 outline-none text-sm w-full md:w-64">
        </div>
    </div>

    <!-- Clients Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8" id="clientsGrid">
        @forelse($clients as $client)
            <div class="glass-card !p-6 hover:border-primary/30 transition-all group client-card"
                data-client-name="{{ strtolower($client['name'] ?? '') }}">
                <!-- Client Header -->
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center text-primary text-xl font-black uppercase">
                            {{ substr($client['name'], 0, 2) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-p-title">{{ $client['name'] }}</h3>
                            <p class="text-xs text-p-muted">{{ $client['email'] }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editClient({{ $client['id'] }})"
                            class="w-8 h-8 rounded-lg bg-white/5 hover:bg-primary/20 flex items-center justify-center text-primary transition-all"
                            title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                        </button>
                        <button onclick="deleteClient({{ $client['id'] }}, '{{ addslashes($client['name']) }}')"
                            class="w-8 h-8 rounded-lg bg-white/5 hover:bg-red-500/20 flex items-center justify-center text-red-500 transition-all"
                            title="Eliminar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Client Stats -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white/5 p-3 rounded-xl">
                        <span class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">Proyectos</span>
                        <span class="text-xl font-black text-p-title">{{ $client['projects_count'] }}</span>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl">
                        <span class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-1">Pagado</span>
                        <span class="text-xl font-black text-emerald-500">${{ number_format($client['total_paid'], 0) }}</span>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="space-y-2 mb-6">
                    @if($client['total_overdue'] > 0)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-p-muted">Vencido</span>
                            <span class="font-bold text-red-500">${{ number_format($client['total_overdue'], 2) }}</span>
                        </div>
                    @endif
                    @if($client['total_pending'] > 0)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-p-muted">Pendiente</span>
                            <span class="font-bold text-amber-500">${{ number_format($client['total_pending'], 2) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Contact Info -->
                @if($client['phone'])
                    <div class="flex items-center gap-2 text-xs text-p-muted mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                            </path>
                        </svg>
                        {{ $client['phone'] }}
                    </div>
                @endif

                <!-- Action Button -->
                <a href="{{ $baseUrl }}admin/billing/projects?client_id={{ $client['id'] }}"
                    class="block w-full text-center px-4 py-2 bg-primary/10 hover:bg-primary hover:text-dark border border-primary/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary transition-all">
                    Ver Proyectos &rarr;
                </a>
            </div>
        @empty
            <div class="col-span-full glass-card !p-12 text-center">
                <div
                    class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center text-primary text-3xl mx-auto mb-6">
                    👥
                </div>
                <h3 class="text-xl font-bold text-p-title mb-2">No hay clientes</h3>
                <p class="text-p-muted mb-6">Comienza creando tu primer cliente</p>
                <button onclick="showCreateClientModal()"
                    class="btn-primary !px-8 !py-3 font-black uppercase tracking-widest text-xs italic">
                    Crear Cliente
                </button>
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        // Search functionality
        document.getElementById('searchClients').addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.client-card');

            cards.forEach(card => {
                const clientName = card.getAttribute('data-client-name');
                if (clientName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        function showCreateClientModal() {
            const html = `
                        <form id="createClientForm" class="space-y-6">
                            <div>
                                <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Nombre *</label>
                                <input type="text" name="name" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Email *</label>
                                <input type="email" name="email" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Teléfono</label>
                                <input type="text" name="phone"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Dirección</label>
                                <textarea name="address" rows="3"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none resize-none"></textarea>
                            </div>
                        </form>
                    `;

            showModal({
                title: 'Crear Nuevo Cliente',
                message: '',
                type: 'confirm',
                confirmText: 'Crear Cliente',
                maxWidth: 'max-w-2xl',
                onConfirm: () => createClient()
            });

            document.getElementById('modal-message').innerHTML = html;
        }

        function createClient() {
            const form = document.getElementById('createClientForm');
            const formData = new FormData(form);

            fetch('{{ $baseUrl }}api/billing/clients', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal({
                            title: '✅ Cliente Creado',
                            message: 'El cliente ha sido creado exitosamente',
                            type: 'success',
                            onConfirm: () => window.location.reload()
                        });
                    } else {
                        showModal({
                            title: '❌ Error',
                            message: data.message || 'Error al crear el cliente',
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

        function editClient(id) {
            // Fetch client data
            fetch(`{{ $baseUrl }}api/billing/clients/${id}`, {
                headers: {
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const client = data.data;
                        const html = `
                                <form id="editClientForm" class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Nombre *</label>
                                        <input type="text" name="name" value="${client.name}" required
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Email *</label>
                                        <input type="email" name="email" value="${client.email}" required
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Teléfono</label>
                                        <input type="text" name="phone" value="${client.phone || ''}"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Dirección</label>
                                        <textarea name="address" rows="3"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none resize-none">${client.address || ''}</textarea>
                                    </div>
                                </form>
                            `;

                        showModal({
                            title: 'Editar Cliente',
                            message: '',
                            type: 'confirm',
                            confirmText: 'Guardar Cambios',
                            maxWidth: 'max-w-2xl',
                            onConfirm: () => updateClient(id)
                        });

                        document.getElementById('modal-message').innerHTML = html;
                    }
                });
        }

        function updateClient(id) {
            const form = document.getElementById('editClientForm');
            const formData = new FormData(form);

            fetch(`{{ $baseUrl }}api/billing/clients/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal({
                            title: '✅ Cliente Actualizado',
                            message: 'Los cambios han sido guardados',
                            type: 'success',
                            onConfirm: () => window.location.reload()
                        });
                    } else {
                        showModal({
                            title: '❌ Error',
                            message: data.message || 'Error al actualizar el cliente',
                            type: 'error'
                        });
                    }
                });
        }

        function deleteClient(id, name) {
            showModal({
                title: '⚠️ Eliminar Cliente',
                message: `¿Estás seguro de que deseas eliminar al cliente "${name}"? Esta acción no se puede deshacer.`,
                type: 'confirm',
                confirmText: 'Eliminar',
                onConfirm: () => {
                    fetch(`{{ $baseUrl }}api/billing/clients/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showModal({
                                    title: '✅ Cliente Eliminado',
                                    message: 'El cliente ha sido eliminado exitosamente',
                                    type: 'success',
                                    onConfirm: () => window.location.reload()
                                });
                            } else {
                                showModal({
                                    title: '❌ Error',
                                    message: data.message || 'Error al eliminar el cliente',
                                    type: 'error'
                                });
                            }
                        });
                }
            });
        }
    </script>
@endsection