@extends('layouts.main')

@section('title', 'Usuarios Web - ' . $project['name'])

@section('content')
    <div class="container mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">{{ $project['name'] }}</h1>
            <p class="text-gray-600">Gestión de usuarios del sitio web (Autenticación Externa)</p>
        </div>

        <!-- Pestañas -->
        <div class="border-b mb-6">
            <nav class="flex gap-4">
                <a href="/admin/projects?edit={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">General</a>
                <a href="/admin/databases?project_id={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">Bases de
                    Datos</a>
                <a href="/admin/api?project_id={{ $project['id'] }}" class="px-4 py-2 hover:text-blue-600">API</a>
                <a href="/admin/projects/{{ $project['id'] }}/logs" class="px-4 py-2 hover:text-blue-600">Logs</a>
                <a href="#" class="px-4 py-2 border-b-2 border-blue-600 font-semibold text-blue-600">Usuarios Web</a>
            </nav>
        </div>

        <!-- Botón agregar usuario -->
        <div class="mb-6 flex justify-between items-center bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div>
                <h3 class="font-medium text-gray-700">Agregar Usuario Existente</h3>
                <p class="text-sm text-gray-500">Busca usuarios registrados en el sistema y dales acceso a este sitio web.
                </p>
            </div>
            <button onclick="openAddUserModal()"
                class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Agregar Usuario
            </button>
        </div>

        <!-- Usuarios pendientes -->
        @if(count($pendingUsers) > 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-yellow-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pendientes de Aprobación ({{ count($pendingUsers) }})
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full bg-white rounded-lg border border-yellow-100">
                        <thead>
                            <tr class="border-b bg-yellow-50/50 text-left text-sm font-semibold text-yellow-800">
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Solicitado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($pendingUsers as $user)
                                <tr class="hover:bg-yellow-50 transition-colors">
                                    <td class="px-4 py-3 font-medium">{{ $user['username'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $user['email'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">Recién</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            onclick="openConfigModal({{ $user['id'] }}, '{{ $user['username'] }}', '{{ $user['email'] }}', true)"
                                            class="bg-green-600 text-white px-3 py-1.5 rounded text-sm hover:bg-green-700 shadow-sm transition">
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
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">👥 Usuarios Activos ({{ count($activeUsers) }})</h2>
                <div class="text-sm text-gray-500">
                    Usuarios con acceso habilitado
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Usuario</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Rol Externo</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($activeUsers as $user)
                                            <?php
                            // Decodificar permisos de forma segura
                            $perms = !empty($user['external_permissions']) ? json_decode($user['external_permissions'], true) : [];
                            $role = $perms['role'] ?? 'client';

                            $roleConfig = [
                                'admin' => ['label' => '👑 Admin', 'class' => 'bg-purple-100 text-purple-800'],
                                'staff' => ['label' => '👨‍⚕️ Staff', 'class' => 'bg-blue-100 text-blue-800'],
                                'client' => ['label' => '👤 Cliente', 'class' => 'bg-gray-100 text-gray-800']
                            ];

                            $currentRole = $roleConfig[$role] ?? $roleConfig['client'];
                                                                    ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $user['username'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $user['email'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentRole['class'] }}">
                                                        {{ $currentRole['label'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <button
                                                        onclick="openConfigModal({{ $user['id'] }}, '{{ $user['username'] }}', '{{ $user['email'] }}', false, '{{ $role }}', {{ json_encode($perms['pages'] ?? []) }})"
                                                        class="text-blue-600 hover:text-blue-900 font-medium hover:underline flex items-center justify-end gap-1 w-full">
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
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                    No hay usuarios activos aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Modal: Configurar Permisos (Styled) -->
    <div id="configModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-filter backdrop-blur-sm" aria-hidden="true" onclick="closeConfigModal()"></div>

            <!-- Modal panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-100">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-white flex items-center gap-2" id="modalUserTitle">
                        <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Configurar Usuario
                    </h3>
                    <button onclick="closeConfigModal()" class="text-blue-100 hover:text-white transition-colors focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 bg-white">
                    <p class="text-sm text-gray-500 mb-6 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        <span id="modalUserEmail" class="font-medium text-blue-800">email@example.com</span>
                    </p>

                    <form id="configForm">
                        <input type="hidden" id="configUserId">
                        <input type="hidden" id="configProjectId" value="{{ $project['id'] }}">

                        <!-- Switch Habilitado -->
                        <div class="mb-6 flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-sm font-medium text-gray-900">Acceso Habilitado</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="userEnabled" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none ring-0 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                            </label>
                        </div>

                        <!-- Roles Grid -->
                        <div class="mb-6">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Rol Asignado</label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative border-2 rounded-lg p-3 cursor-pointer hover:border-blue-300 transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="role" value="admin" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center group-has-[:checked]:border-blue-500">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-gray-900 group-has-[:checked]:text-blue-700">Administrador</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">Control total del sitio y configuraciones.</span>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative border-2 rounded-lg p-3 cursor-pointer hover:border-blue-300 transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="role" value="staff" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center group-has-[:checked]:border-blue-500">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-gray-900 group-has-[:checked]:text-blue-700">Staff</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">Gestión operativa sin acceso a configuración.</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="relative border-2 rounded-lg p-3 cursor-pointer hover:border-blue-300 transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="role" value="client" class="sr-only" onchange="updatePermissionsUI()">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center group-has-[:checked]:border-blue-500">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></span>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-gray-900 group-has-[:checked]:text-blue-700">Cliente</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">Acceso limitado a sus propios datos.</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Paginas -->
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Páginas Permitidas</label>
                            <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-2 gap-3 border border-gray-100" id="pagesContainer">
                                <label class="flex items-center space-x-2 bg-white p-2 rounded border border-gray-200 shadow-sm opacity-50 cursor-not-allowed">
                                    <input type="checkbox" checked disabled class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Dashboard</span>
                                </label>
                                <label class="flex items-center space-x-2 bg-white p-2 rounded border border-gray-200 shadow-sm opacity-50 cursor-not-allowed">
                                    <input type="checkbox" checked disabled class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Perfil</span>
                                </label>
                                <label class="flex items-center space-x-2 bg-white p-2 rounded border border-gray-200 shadow-sm hover:border-blue-400 transition-colors">
                                    <input type="checkbox" name="pages" value="reports" class="page-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <span class="text-sm font-medium text-gray-700">Reportes</span>
                                </label>
                                <label class="flex items-center space-x-2 bg-white p-2 rounded border border-gray-200 shadow-sm hover:border-blue-400 transition-colors">
                                    <input type="checkbox" name="pages" value="settings" class="page-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <span class="text-sm font-medium text-gray-700">Configuración</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-gray-100">
                    <button type="button" onclick="closeConfigModal()" class="w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                        Cancelar
                    </button>
                    <button type="button" onclick="savePermissions()" id="btnSave" class="w-full sm:w-auto px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-white hover:bg-blue-700 font-medium shadow-md transition-all transform active:scale-95 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex justify-center items-center">
                        <span id="btnSaveText">Guardar Cambios</span>
                        <svg id="btnSaveSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
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
        // --- Toast Logic ---
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastContent = document.getElementById('toastContent');
            const icon = document.getElementById('toastIcon');
            const titleEl = document.getElementById('toastTitle');
            const msgEl = document.getElementById('toastMessage');

            // Config colors
            const colors = {
                success: 'border-green-500 text-green-500',
                error: 'border-red-500 text-red-500',
                info: 'border-blue-500 text-blue-500'
            };
            const icons = {
                success: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                info: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };

            // Reset classes
            toastContent.className = `bg-white border-l-4 rounded shadow-2xl p-4 flex items-center min-w-[300px] ${colors[type]}`;
            icon.innerHTML = icons[type];
            titleEl.innerText = title;
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
            document.getElementById('modalUserTitle').innerHTML = isApproval 
                ? '<svg class="w-5 h-5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Aprobar: ' + username 
                : '<svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg> Configurar: ' + username;
            document.getElementById('modalUserEmail').innerText = email;
            document.getElementById('configUserId').value = userId;
            document.getElementById('userEnabled').checked = true;

            // Set role radio
            const radios = document.getElementsByName('role');
            for (const radio of radios) {
                 radio.parentElement.classList.remove('bg-blue-50', 'border-blue-500'); // Reset visuals
                 if (radio.value === currentRole) {
                     radio.checked = true;
                     radio.parentElement.classList.add('bg-blue-50', 'border-blue-500'); // Active visual
                 }
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
            
            // Visual Update for Radios
            document.getElementsByName('role').forEach(r => {
                if(r.checked) r.parentElement.classList.add('bg-blue-50', 'border-blue-500');
                else r.parentElement.classList.remove('bg-blue-50', 'border-blue-500');
            });

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
                    headers: { 'Content-Type': 'application/json' },
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
                showToast('Error de Conexión', e.message, 'error');
                resetBtn();
            }

            function resetBtn() {
                btn.disabled = false;
                txt.innerText = 'Guardar Cambios';
                spin.classList.add('hidden');
            }
        }

        // --- Add User Logic (Simplified for brevity but functional) ---
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
             // Reuse generic logic, keeping it simple
             const pid = document.getElementById('configProjectId').value;
             try {
                const res = await fetch('/admin/projects/external-users/add', { method: 'POST', body: JSON.stringify({project_id: pid, user_id: uid}) });
                const d = await res.json();
                if(d.success) { showToast('Usuario Agregado', 'Actualizando...'); setTimeout(() => location.reload(), 1000); }
                else showToast('Error', d.error, 'error');
             } catch(e) { showToast('Error', 'Fallo al agregar', 'error'); }
        }
    </script>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeConfigModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalUserTitle">Configurar Usuario
                            </h3>
                            <p class="text-sm text-gray-500 mb-6" id="modalUserEmail">email@example.com</p>

                            <form id="configForm">
                                <input type="hidden" id="configUserId">
                                <input type="hidden" id="configProjectId" value="{{ $project['id'] }}">

                                <!-- Habilitado -->
                                <div class="mb-4 flex items-center">
                                    <input type="checkbox" id="userEnabled"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                    <label for="userEnabled" class="ml-2 block text-sm text-gray-900 font-medium">Habilitar
                                        acceso al sitio web</label>
                                </div>

                                <!-- Roles -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol en el Sitio</label>
                                    <div class="grid grid-cols-1 gap-2">
                                        <label
                                            class="border p-3 rounded-lg flex items-center cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="role" value="admin"
                                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                onchange="updatePermissionsUI()">
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-900">Administrador</span>
                                                <span class="block text-xs text-gray-500">Acceso total, gestión de contenido
                                                    y configuraciones.</span>
                                            </div>
                                        </label>
                                        <label
                                            class="border p-3 rounded-lg flex items-center cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="role" value="staff"
                                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                onchange="updatePermissionsUI()">
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-900">Staff</span>
                                                <span class="block text-xs text-gray-500">Gestión operativa (citas,
                                                    historial), sin acceso a configuración.</span>
                                            </div>
                                        </label>
                                        <label
                                            class="border p-3 rounded-lg flex items-center cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="role" value="client"
                                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                onchange="updatePermissionsUI()">
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-900">Cliente</span>
                                                <span class="block text-xs text-gray-500">Solo puede ver y gestionar sus
                                                    propios datos.</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Páginas -->
                                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Páginas
                                        Permitidas</label>
                                    <div class="space-y-2" id="pagesContainer">
                                        <label class="inline-flex items-center w-full">
                                            <input type="checkbox" name="pages" value="dashboard"
                                                class="form-checkbox h-4 w-4 text-blue-600" checked disabled>
                                            <span class="ml-2 text-sm text-gray-700">Dashboard (Obligatorio)</span>
                                        </label>
                                        <label class="inline-flex items-center w-full">
                                            <input type="checkbox" name="pages" value="profile"
                                                class="form-checkbox h-4 w-4 text-blue-600" checked disabled>
                                            <span class="ml-2 text-sm text-gray-700">Perfil (Obligatorio)</span>
                                        </label>
                                        <label class="inline-flex items-center w-full">
                                            <input type="checkbox" name="pages" value="reports"
                                                class="form-checkbox h-4 w-4 text-blue-600 page-checkbox">
                                            <span class="ml-2 text-sm text-gray-700">Reportes</span>
                                        </label>
                                        <label class="inline-flex items-center w-full">
                                            <input type="checkbox" name="pages" value="settings"
                                                class="form-checkbox h-4 w-4 text-blue-600 page-checkbox">
                                            <span class="ml-2 text-sm text-gray-700">Configuración</span>
                                        </label>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mt-2">* El alcance de datos se ajustará automáticamente
                                    según el rol seleccionado.</p>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="savePermissions()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Guardar Cambios
                    </button>
                    <button type="button" onclick="closeConfigModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Agregar Usuario -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeAddUserModal()"></div>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Agregar Usuario al Proyecto</h3>
                    <div class="relative mb-4">
                        <input type="text" id="userSearch" placeholder="Buscar por nombre o email..."
                            class="w-full border border-gray-300 rounded-md p-2 pl-4 focus:ring-blue-500 focus:border-blue-500"
                            onkeyup="debounceSearch()">
                    </div>
                    <div id="searchResults" class="max-h-60 overflow-y-auto border-t border-gray-100">
                        <p class="text-center text-gray-500 py-4 text-sm">Empieza a escribir para buscar...</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeAddUserModal()"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Config Modal Logic ---
        function openConfigModal(userId, username, email, isApproval = false, currentRole = 'client', currentPages = []) {
            document.getElementById('configModal').classList.remove('hidden');
            document.getElementById('modalUserTitle').innerText = isApproval ? 'Aprobar y Configurar: ' + username : 'Configurar: ' + username;
            document.getElementById('modalUserEmail').innerText = email;
            document.getElementById('configUserId').value = userId;
            document.getElementById('userEnabled').checked = true;

            // Set role radio
            const radios = document.getElementsByName('role');
            for (const radio of radios) {
                radio.checked = (radio.value === currentRole);
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

            if (role === 'admin') {
                // Admin gets everything checked and disabled (implied)
                pageCheckboxes.forEach(cb => {
                    cb.checked = true;
                    cb.disabled = true;
                });
            } else if (role === 'staff') {
                // Staff default checks but editable
                pageCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
            } else {
                // Client usually restricted
                pageCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = false;
                });
            }
        }

        async function savePermissions() {
            const projectId = document.getElementById('configProjectId').value;
            const userId = document.getElementById('configUserId').value;
            const enabled = document.getElementById('userEnabled').checked;
            const role = document.querySelector('input[name="role"]:checked').value;

            // Get pages
            const pages = ['dashboard', 'profile']; // Always included
            document.querySelectorAll('.page-checkbox:checked').forEach(cb => pages.push(cb.value));

            // Auto-set data access based on role
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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al guardar: ' + (data.error || 'Desconocido'));
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión');
            }
        }

        // --- Add User Logic ---
        let searchTimeout;
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
            document.getElementById('userSearch').value = '';
            document.getElementById('userSearch').focus();
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(doSearch, 300);
        }

        async function doSearch() {
            const query = document.getElementById('userSearch').value;
            const projectId = document.getElementById('configProjectId').value; // Reuse hidden input
            if (query.length < 2) return;

            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '<p class="text-center text-gray-500 py-4 text-sm">Buscando...</p>';

            try {
                const res = await fetch(`/admin/projects/external-users/search?project_id=${projectId}&q=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (!data.users || data.users.length === 0) {
                    resultsDiv.innerHTML = '<p class="text-center text-gray-500 py-4 text-sm">No se encontraron usuarios.</p>';
                    return;
                }

                let html = '<ul class="divide-y divide-gray-100">';
                data.users.forEach(u => {
                    html += `
                            <li class="p-3 hover:bg-gray-50 flex justify-between items-center group">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                        ${u.username.substring(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${u.username}</p>
                                        <p class="text-xs text-gray-500">${u.email}</p>
                                    </div>
                                </div>
                                <button onclick="addUser(${u.id})" class="text-blue-600 border border-blue-600 rounded px-2 py-1 text-xs hover:bg-blue-50 opacity-0 group-hover:opacity-100 transition-opacity">
                                    + Agregar
                                </button>
                            </li>
                        `;
                });
                html += '</ul>';
                resultsDiv.innerHTML = html;
            } catch (e) {
                resultsDiv.innerHTML = '<p class="text-center text-red-500 py-4 text-sm">Error al buscar.</p>';
            }
        }

        async function addUser(userId) {
            const projectId = document.getElementById('configProjectId').value;
            if (!confirm('¿Agregar este usuario al proyecto?')) return;

            try {
                const res = await fetch('/admin/projects/external-users/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ project_id: projectId, user_id: userId })
                });
                const data = await res.json();

                if (data.success) {
                    closeAddUserModal();
                    // Opcional: abrir modal de config inmediatamente
                    // openConfigModal(userId, '', '', true); -> Necesitaríamos datos completos
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Error al agregar usuario');
            }
        }
    </script>
@endsection