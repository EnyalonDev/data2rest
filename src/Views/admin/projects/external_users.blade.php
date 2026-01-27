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

    <!-- Modal: Configurar Permisos -->
    <div id="configModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
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
            } elseif(role === 'staff') {
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