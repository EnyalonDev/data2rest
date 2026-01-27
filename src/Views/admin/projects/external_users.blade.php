@extends('layouts.main')

@section('title', 'Usuarios Web - ' . $project['name'])

@section('content')
    <div class="container mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-p-title">{{ $project['name'] }}</h1>
            <p class="text-p-muted">Gestión de usuarios del sitio web (Autenticación Externa)</p>
        </div>

        <!-- Pestañas -->
        <div class="border-b border-white/10 mb-6">
            <nav class="flex gap-4">
                <a href="/admin/projects?edit={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">General</a>
                <a href="/admin/databases?project_id={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">Bases de Datos</a>
                <a href="/admin/api?project_id={{ $project['id'] }}" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">API</a>
                <a href="/admin/projects/{{ $project['id'] }}/logs" class="px-4 py-2 text-p-muted hover:text-primary transition-colors">Logs</a>
                <a href="#" class="px-4 py-2 border-b-2 border-primary font-semibold text-primary">Usuarios Web</a>
            </nav>
        </div>

        <!-- Botón agregar usuario -->
        <div class="mb-6 flex justify-between items-center glass-card p-4 rounded-lg">
            <div>
                <h3 class="font-medium text-p-title">Agregar Usuario Existente</h3>
                <p class="text-sm text-p-muted">Busca usuarios registrados en el sistema y dales acceso a este sitio web.
                </p>
            </div>
            <button onclick="openAddUserModal()"
                class="btn-primary px-4 py-2 rounded shadow flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Agregar Usuario
            </button>
        </div>

        <!-- Usuarios pendientes -->
        @if(count($pendingUsers) > 0)
            <div class="glass-card !border-yellow-500/30 p-4 mb-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-yellow-500"></div>
                <h2 class="text-xl font-semibold mb-4 text-yellow-400 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pendientes de Aprobación ({{ count($pendingUsers) }})
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 text-xs font-semibold text-yellow-500/80 uppercase tracking-wider">
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Solicitado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($pendingUsers as $user)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 font-medium text-p-title">{{ $user['username'] }}</td>
                                    <td class="px-4 py-3 text-p-muted">{{ $user['email'] }}</td>
                                    <td class="px-4 py-3 text-sm text-p-muted">Recién</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            onclick="openConfigModal({{ $user['id'] }}, '{{ $user['username'] }}', '{{ $user['email'] }}', true)"
                                            class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/50 px-3 py-1.5 rounded text-xs hover:bg-emerald-500/30 transition shadow-sm">
                                            ✓ Aprobar & Configurar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Usuarios activos -->
        <div class="glass-card rounded-lg overflow-hidden">
            <div class="p-4 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-p-title">👥 Usuarios Activos ({{ count($activeUsers) }})</h2>
                <div class="text-sm text-p-muted">
                    Usuarios con acceso habilitado
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-black/20 text-left text-xs font-bold text-p-muted uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Usuario</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Rol Externo</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($activeUsers as $user)
                                            <?php
                            // Decodificar permisos de forma segura
                            $perms = !empty($user['external_permissions']) ? json_decode($user['external_permissions'], true) : [];
                            $role = $perms['role'] ?? 'client';

                            $roleConfig = [
                                'admin' => ['label' => '👑 Admin', 'class' => 'bg-purple-500/20 text-purple-300 border border-purple-500/30'],
                                'staff' => ['label' => '👨‍⚕️ Staff', 'class' => 'bg-blue-500/20 text-blue-300 border border-blue-500/30'],
                                'client' => ['label' => '👤 Cliente', 'class' => 'bg-slate-500/20 text-slate-300 border border-slate-500/30']
                            ];

                            $currentRole = $roleConfig[$role] ?? $roleConfig['client'];
                                                                    ?>
                                            <tr class="hover:bg-white/5 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap font-medium text-p-title">{{ $user['username'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-p-muted">{{ $user['email'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentRole['class'] }}">
                                                        {{ $currentRole['label'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <button
                                                        onclick="openConfigModal({{ $user['id'] }}, '{{ $user['username'] }}', '{{ $user['email'] }}', false, '{{ $role }}', {{ json_encode($perms['pages'] ?? []) }})"
                                                        class="text-primary hover:text-white font-medium hover:underline flex items-center justify-end gap-1 w-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                                            </path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        Configurar
                                                    </button>
                                                </td>
                                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-p-muted">
                                    No hay usuarios activos aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>



    <!-- Modal: Configurar Permisos (Themed) -->
    <div id="configModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-dark/80 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeConfigModal()"></div>

            <!-- Modal panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom glass-card text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full !p-0">
                <!-- Header -->
                <div class="bg-white/5 border-b border-white/10 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-p-title flex items-center gap-2" id="modalUserTitle">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Configurar Usuario
                    </h3>
                    <button onclick="closeConfigModal()" class="text-p-muted hover:text-p-title transition-colors focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-6">
                    <p class="text-sm text-p-muted mb-6 bg-primary/10 p-3 rounded-lg border border-primary/20 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        <span id="modalUserEmail" class="font-medium text-p-title">email@example.com</span>
                    </p>

                    <form id="configForm">
                        <input type="hidden" id="configUserId">
                        <input type="hidden" id="configProjectId" value="{{ $project['id'] }}">

                        <!-- Switch Habilitado -->
                        <div class="mb-6 flex items-center justify-between bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-sm font-medium text-p-title">Acceso Habilitado</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="userEnabled" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none ring-0 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>

                        <!-- Roles Grid -->
                        <div class="mb-6">
                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-3">Rol Asignado</label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative border border-white/10 rounded-lg p-3 cursor-pointer hover:bg-white/5 transition-all group has-[:checked]:border-primary/50 has-[:checked]:bg-primary/5">
                                    <input type="radio" name="role" value="admin" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-white/20 flex items-center justify-center group-has-[:checked]:border-primary">
                                                <span class="w-2 h-2 rounded-full bg-primary opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-p-title group-has-[:checked]:text-primary">Administrador</span>
                                            <span class="block text-xs text-p-muted mt-0.5">Control total del sitio y configuraciones.</span>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative border border-white/10 rounded-lg p-3 cursor-pointer hover:bg-white/5 transition-all group has-[:checked]:border-primary/50 has-[:checked]:bg-primary/5">
                                    <input type="radio" name="role" value="staff" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-white/20 flex items-center justify-center group-has-[:checked]:border-primary">
                                                <span class="w-2 h-2 rounded-full bg-primary opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-p-title group-has-[:checked]:text-primary">Staff</span>
                                            <span class="block text-xs text-p-muted mt-0.5">Gestión operativa sin acceso a configuración.</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="relative border border-white/10 rounded-lg p-3 cursor-pointer hover:bg-white/5 transition-all group has-[:checked]:border-primary/50 has-[:checked]:bg-primary/5">
                                    <input type="radio" name="role" value="client" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-white/20 flex items-center justify-center group-has-[:checked]:border-primary">
                                                <span class="w-2 h-2 rounded-full bg-primary opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-p-title group-has-[:checked]:text-primary">Cliente</span>
                                            <span class="block text-xs text-p-muted mt-0.5">Acceso limitado a sus propios datos.</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Paginas -->
                        <div class="mb-4">
                            <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-3">Páginas Permitidas</label>
                            <div class="bg-white/5 rounded-lg p-4 grid grid-cols-2 gap-3 border border-white/10" id="pagesContainer">
                                <label class="flex items-center space-x-2 p-2 rounded border border-white/10 opacity-50 cursor-not-allowed">
                                    <input type="checkbox" checked disabled class="bg-black/20 border-white/10 rounded text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-p-muted">Dashboard</span>
                                </label>
                                <label class="flex items-center space-x-2 p-2 rounded border border-white/10 opacity-50 cursor-not-allowed">
                                    <input type="checkbox" checked disabled class="bg-black/20 border-white/10 rounded text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-p-muted">Perfil</span>
                                </label>
                                <label class="flex items-center space-x-2 p-2 rounded border border-white/10 hover:border-primary/50 transition-colors">
                                    <input type="checkbox" name="pages" value="reports" class="page-checkbox bg-black/20 border-white/10 rounded text-primary focus:ring-primary h-4 w-4">
                                    <span class="text-sm font-medium text-p-title">Reportes</span>
                                </label>
                                <label class="flex items-center space-x-2 p-2 rounded border border-white/10 hover:border-primary/50 transition-colors">
                                    <input type="checkbox" name="pages" value="settings" class="page-checkbox bg-black/20 border-white/10 rounded text-primary focus:ring-primary h-4 w-4">
                                    <span class="text-sm font-medium text-p-title">Configuración</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-white/5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-white/10">
                    <button type="button" onclick="closeConfigModal()" class="btn-outline w-full sm:w-auto">
                        Cancelar
                    </button>
                    <button type="button" onclick="savePermissions()" id="btnSave" class="btn-primary w-full sm:w-auto shadow-lg shadow-primary/20">
                        <span id="btnSaveText">Guardar Cambios</span>
                        <svg id="btnSaveSpinner" class="animate-spin ml-2 h-4 w-4 text-dark hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Toast Notification System -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 ease-out">
        <div id="toastContent" class="bg-white border-l-4 rounded shadow-2xl p-4 flex items-center min-w-[300px]">
            <div id="toastIcon" class="mr-3"></div>
            <div class="flex-1">
                <h4 id="toastTitle" class="font-bold text-sm">Notificación</h4>
                <p id="toastMessage" class="text-xs text-gray-600 mt-1">Mensaje de prueba</p>
            </div>
            <button onclick="hideToast()" class="ml-3 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>


    <!-- Modal: Agregar Usuario (Classic) -->

    <!-- Modal: Agregar Usuario (Classic) -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAddUserModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Agregar Usuario al Proyecto</h3>
                    <div class="relative mb-4">
                        <input type="text" id="userSearch" placeholder="Escribe para buscar..." class="w-full border border-gray-300 rounded-md p-2 pl-4 focus:ring-blue-500 focus:border-blue-500" onkeyup="debounceSearch()">
                    </div>
                    <div id="searchResults" class="max-h-60 overflow-y-auto border-t border-gray-100 bg-gray-50 rounded">
                        <p class="text-center text-gray-500 py-4 text-sm">Resultados aparecerán aquí...</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeAddUserModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- CSRF Token Setup ---
        const csrfToken = '{{ $csrf_token ?? "" }}';

        // --- Toast Logic ---
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastContent = document.getElementById('toastContent');
            const icon = document.getElementById('toastIcon');
            const titleEl = document.getElementById('toastTitle');
            const msgEl = document.getElementById('toastMessage');

            // Config colors
            const colors = {
                success: 'border-emerald-500 text-emerald-500',
                error: 'border-red-500 text-red-500',
                info: 'border-primary text-primary'
            };
            const icons = {
                success: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                info: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };

            // Using glass-card for background matching theme
            toastContent.className = `glass-card border-l-4 !p-4 flex items-center min-w-[300px] ${colors[type]}`;
            icon.innerHTML = icons[type];
            titleEl.className = "font-bold text-sm text-p-title";
            titleEl.innerText = title;
            msgEl.className = "text-xs text-p-muted mt-1";
            msgEl.innerText = message;

            // Show
            toast.classList.remove('translate-y-20', 'opacity-0');
            
            // Auto hide
            setTimeout(hideToast, 4000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('translate-y-20', 'opacity-0');
        }

        // --- Config Modal Logic ---
        function openConfigModal(userId, username, email, isApproval = false, currentRole = 'client', currentPages = []) {
            document.getElementById('configModal').classList.remove('hidden');
            // Theme-aware icons in title
            document.getElementById('modalUserTitle').innerHTML = isApproval 
                ? '<svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Aprobar: ' + username 
                : '<svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg> Configurar: ' + username;
            document.getElementById('modalUserEmail').innerText = email;
            document.getElementById('configUserId').value = userId;
            document.getElementById('userEnabled').checked = true;

            // Set role radio
            const radios = document.getElementsByName('role');
            for (const radio of radios) {
                 if (radio.value === currentRole) radio.checked = true;
            }

            // Set pages
            const checkboxes = document.querySelectorAll('.page-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = currentPages.includes(cb.value);
            });

            updatePermissionsUI();
        }

        function closeConfigModal() {
            document.getElementById('configModal').classList.add('hidden');
        }

        function updatePermissionsUI() {
            const role = document.querySelector('input[name="role"]:checked').value;
            const pageCheckboxes = document.querySelectorAll('.page-checkbox');
            
            // Manual styling logic removed - handled by CSS :has() selector now

            if (role === 'admin') {
                pageCheckboxes.forEach(cb => { cb.checked = true; cb.disabled = true; });
            } else if (role === 'staff') {
                pageCheckboxes.forEach(cb => { cb.disabled = false; });
            } else {
                pageCheckboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
            }
        }

        async function savePermissions() {
            const btn = document.getElementById('btnSave');
            const txt = document.getElementById('btnSaveText');
            const spin = document.getElementById('btnSaveSpinner');

            // Loading state
            btn.disabled = true;
            txt.innerText = 'Guardando...';
            spin.classList.remove('hidden');

            const projectId = document.getElementById('configProjectId').value;
            const userId = document.getElementById('configUserId').value;
            const enabled = document.getElementById('userEnabled').checked;
            const role = document.querySelector('input[name="role"]:checked').value;

            const pages = ['dashboard', 'profile'];
            document.querySelectorAll('.page-checkbox:checked').forEach(cb => pages.push(cb.value));

            let dataAccess = 'own';
            if (role === 'admin' || role === 'staff') dataAccess = 'all';

            const payload = {
                project_id: projectId,
                user_id: userId,
                enabled: enabled ? 1 : 0,
                role: role,
                pages: pages,
                data_access: dataAccess,
                actions: (role === 'admin') ? ['*'] : []
            };

            try {
                const res = await fetch('/admin/projects/external-users/update', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: JSON.stringify(payload)
                });

                // Handle non-JSON responses (HTML errors)
                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await res.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('El servidor devolvió un error inesperado (HTML). Revisa la consola.');
                }

                const data = await res.json();

                if (data.success) {
                    showToast('¡Guardado!', 'Permisos actualizados correctamente.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error', data.error || 'No se pudo guardar.', 'error');
                    resetBtn();
                }
            } catch (e) {
                console.error(e);
                showToast('Error', e.message, 'error');
                resetBtn();
            }

            function resetBtn() {
                btn.disabled = false;
                txt.innerText = 'Guardar Cambios';
                spin.classList.add('hidden');
            }
        }

        // --- Add User Logic ---
        let searchTimeout;
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
            document.getElementById('userSearch').value = '';
            document.getElementById('userSearch').focus();
        }
        function closeAddUserModal() { document.getElementById('addUserModal').classList.add('hidden'); }
        function debounceSearch() { clearTimeout(searchTimeout); searchTimeout = setTimeout(doSearch, 300); }
        async function doSearch() { 
            const q = document.getElementById('userSearch').value;
            const pid = document.getElementById('configProjectId').value;
            if(q.length < 2) return;
            const div = document.getElementById('searchResults');
            try {
                const res = await fetch(`/admin/projects/external-users/search?project_id=${pid}&q=${encodeURIComponent(q)}`);
                const d = await res.json();
                if(!d.users?.length) { div.innerHTML = '<p class="text-center p-3">No encontrado.</p>'; return; }
                div.innerHTML = d.users.map(u => `<div class="p-3 flex justify-between hover:bg-white cursor-pointer border-b" onclick="addUser(${u.id})"><span>${u.username} (${u.email})</span><button class="text-blue-600 font-bold">+</button></div>`).join('');
            } catch(e) { console.error(e); }
        }
        async function addUser(uid) {
             const pid = document.getElementById('configProjectId').value;
             try {
                const res = await fetch('/admin/projects/external-users/add', { 
                    method: 'POST', 
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({project_id: pid, user_id: uid}) 
                });
                const d = await res.json();
                if(d.success) { showToast('Usuario Agregado', 'Actualizando...'); setTimeout(() => location.reload(), 1000); }
                else showToast('Error', d.error, 'error');
             } catch(e) { showToast('Error', 'Fallo al agregar', 'error'); }
        }
    </script>
@endsection