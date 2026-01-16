@extends('layouts.main')

@section('title', 'Gestión de Tareas')

@section('styles')
    <style>
        .kanban-container {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1.5rem 0;
            min-height: calc(100vh - 300px);
            scrollbar-width: thin;
            scrollbar-color: var(--p-border) transparent;
        }

        .kanban-column {
            flex: 0 0 320px;
            display: flex;
            flex-direction: column;
        }

        .kanban-column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid;
        }

        .kanban-column-title {
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--p-title);
        }

        .status-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .task-count {
            background: var(--p-bg);
            color: var(--p-muted);
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .kanban-tasks {
            flex: 1;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .task-card {
            cursor: grab;
            transition: all 0.2s;
            position: relative;
        }

        .task-card:hover {
            transform: translateY(-2px);
            border-color: #38bdf8;
        }

        .task-card.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }

        .task-card.no-drag {
            cursor: not-allowed;
            opacity: 0.8;
        }

        .task-card.no-drag:hover {
            transform: none;
            border-color: var(--p-border);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--p-title);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .task-priority {
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .priority-high {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .priority-medium {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .priority-low {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .task-description {
            font-size: 0.85rem;
            color: var(--p-muted);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--p-muted);
            border-top: 1px solid var(--p-border);
            padding-top: 0.75rem;
        }

        .task-assigned {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #38bdf8 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .task-actions {
            display: flex;
            gap: 0.25rem;
        }

        .task-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            color: var(--p-muted);
            transition: color 0.2s;
            border-radius: 4px;
        }

        .task-action-btn:hover {
            color: #38bdf8;
            background: var(--p-bg);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--p-title);
            letter-spacing: -0.025em;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--p-muted);
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: var(--p-text);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .client-approval-section {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }

        .approval-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            cursor: pointer;
        }

        .history-item {
            padding: 1rem;
            background: var(--p-bg);
            border: 1px solid var(--p-border);
            border-radius: 12px;
            margin-bottom: 0.75rem;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .history-action {
            font-weight: 700;
            color: #38bdf8;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        .history-time {
            color: var(--p-muted);
            font-size: 0.75rem;
            font-style: italic;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
@endsection

@section('content')
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-p-title tracking-tight mb-2">
                    Gestión de Tareas
                </h1>
                <p class="text-p-muted font-medium">
                    Tablero Kanban para organizar y gestionar tareas del proyecto
                </p>
            </div>
            @if($canCreate)
                <a href="javascript:void(0)" onclick="openNewTaskModal()"
                    class="px-8 py-4 bg-primary text-white rounded-2xl font-bold shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 group">
                    <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Tarea
                </a>
            @endif
        </div>

        <!-- Kanban Board -->
        <div class="kanban-container" id="kanbanBoard">
            @foreach($statuses as $status)
                <div class="kanban-column glass-card !p-6" data-status-id="{{ $status['id'] }}">
                    <div class="kanban-column-header" style="border-color: {{ $status['color'] }};">
                        <div class="kanban-column-title">
                            <span class="status-badge" style="background: {{ $status['color'] }};"></span>
                            {{ $status['name'] }}
                        </div>
                        <span class="task-count">{{ count($tasks[$status['id']] ?? []) }}</span>
                    </div>

                    <div class="kanban-tasks" data-status-id="{{ $status['id'] }}">
                        @foreach($tasks[$status['id']] ?? [] as $task)
                            <div class="task-card glass-card !p-4 {{ $canDrag ? '' : 'no-drag' }}" data-task-id="{{ $task['id'] }}"
                                draggable="{{ $canDrag ? 'true' : 'false' }}">
                                <div class="task-header">
                                    <div>
                                        <div class="task-title">{{ $task['title'] }}</div>
                                    </div>
                                    <span class="task-priority priority-{{ $task['priority'] }}">
                                        {{ $task['priority'] }}
                                    </span>
                                </div>

                                @if($task['description'])
                                    <div class="task-description">
                                        {{ substr($task['description'], 0, 100) }}{{ strlen($task['description']) > 100 ? '...' : '' }}
                                    </div>
                                @endif

                                <div class="task-footer">
                                    <div class="task-assigned">
                                        @if($task['assigned_to'])
                                            <div class="avatar">
                                                {{ strtoupper(substr($task['assigned_username'], 0, 2)) }}
                                            </div>
                                            <span>{{ $task['assigned_name'] ?? $task['assigned_username'] }}</span>
                                        @else
                                            <span style="color: var(--text-muted);">Sin asignar</span>
                                        @endif
                                    </div>

                                    <div class="task-actions">
                                        @if(!$task['assigned_to'] && !$isClient)
                                            <button class="task-action-btn" onclick="takeTask({{ $task['id'] }})"
                                                title="Tomar Tarea (Auto-asignar)">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                </svg>
                                            </button>
                                        @endif
                                        <button class="task-action-btn" onclick="viewTask({{ $task['id'] }})" title="Ver detalles">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        @if($isClient && $status['slug'] === 'client_validation')
                                            <button class="task-action-btn" onclick="approveTask({{ $task['id'] }})"
                                                title="Aprobar y Finalizar" style="color: #10b981;">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        @endif
                                        @if($canDelete)
                                            <button class="task-action-btn" onclick="deleteTask({{ $task['id'] }})" title="Eliminar"
                                                style="color: #ef4444;">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal: Nueva Tarea -->
    <div id="newTaskModal" class="modal">
        <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl border-2 border-white/10">
            <div class="modal-header">
                <h2 class="modal-title">Nueva Tarea</h2>
                <button type="button" class="close-modal" onclick="closeTaskModal('newTaskModal')">&times;</button>
            </div>
            <form id="newTaskForm" onsubmit="createTask(event)">
                <input type="hidden" name="_token" value="{{ $csrf_token }}">
                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-input" style="min-height: 120px; resize: vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Prioridad</label>
                    <select name="priority" class="custom-select">
                        <option value="low">Baja</option>
                        <option value="medium" selected>Media</option>
                        <option value="high">Alta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Asignar a</label>
                    <select name="assigned_to" class="custom-select">
                        <option value="">Sin asignar</option>
                        @foreach($projectUsers as $user)
                            <option value="{{ $user['id'] }}">{{ $user['public_name'] ?? $user['username'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado inicial</label>
                    <select name="status_id" class="custom-select">
                        @foreach($statuses as $status)
                            <option value="{{ $status['id'] }}" {{ $status['slug'] === 'backlog' ? 'selected' : '' }}>
                                {{ $status['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary">Crear Tarea</button>
            </form>
        </div>
    </div>

    <!-- Modal: Ver/Aprobar Tarea -->
    <div id="viewTaskModal" class="modal">
        <div
            class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl border-2 border-white/10 flex flex-col">
            <div class="modal-header border-b border-gray-700 pb-4">
                <h2 class="modal-title flex-1 mr-4" id="modalTaskTitle">Detalles de la Tarea</h2>
                <div class="flex gap-2 items-center z-50">
                    <span id="modalTaskStatus"
                        class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-gray-700 text-gray-300">STATUS</span>
                    <button type="button" class="close-modal text-gray-400 hover:text-white text-3xl leading-none px-2"
                        onclick="closeTaskModal('viewTaskModal')">&times;</button>
                </div>
            </div>

            <div class="modal-tabs flex border-b border-gray-700 mb-4 bg-black/20">
                <button
                    class="tab-btn active px-6 py-3 text-sm font-bold text-gray-400 hover:text-white border-b-2 border-transparent hover:border-blue-500 transition-all"
                    onclick="switchModalTab('general')">General</button>
                <button
                    class="tab-btn px-6 py-3 text-sm font-bold text-gray-400 hover:text-white border-b-2 border-transparent hover:border-blue-500 transition-all"
                    onclick="switchModalTab('comments')">Comentarios</button>
                <button
                    class="tab-btn px-6 py-3 text-sm font-bold text-gray-400 hover:text-white border-b-2 border-transparent hover:border-blue-500 transition-all"
                    onclick="switchModalTab('history')">Historial</button>
            </div>

            <div id="modal-content-container" class="p-4 flex-1 overflow-y-auto">
                <!-- Tab: General -->
                <div id="tab-general" class="tab-content transition-opacity duration-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label
                                    class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Descripción</label>
                                <div id="modalTaskDesc"
                                    class="p-4 bg-gray-900/50 rounded-xl border border-gray-700 text-gray-300 text-sm leading-relaxed min-h-[100px] whitespace-pre-wrap">
                                </div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Asignado
                                    a</label>
                                <select id="modalTaskAssignee" onchange="updateTaskAssignment(this.value)"
                                    class="form-select w-full bg-gray-900 border border-gray-700 rounded-lg p-2 text-sm text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Sin Asignar --</option>
                                    <!-- Options injected via JS -->
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Prioridad</label>
                                <div id="modalTaskPriority" class="text-sm font-bold text-white capitalize">Media</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Creado
                                    Por</label>
                                <div id="modalTaskCreator" class="text-sm text-gray-400">System</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Fecha
                                    Creación</label>
                                <div id="modalTaskDate" class="text-sm text-gray-400"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Comments -->
                <div id="tab-comments" class="tab-content hidden transition-opacity duration-300">
                    <!-- Comments injected here -->
                </div>

                <!-- Tab: History -->
                <div id="tab-history" class="tab-content hidden transition-opacity duration-300">
                    <!-- History injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '{{ $baseUrl }}';
        const csrfToken = '{{ $csrf_token }}';
        const canDrag = {{ $canDrag ? 'true' : 'false' }};
        const isClient = {{ $isClient ? 'true' : 'false' }};
        // Parse users for JS
        const projectUsers = @json($projectUsers);

        let currentTaskId = null; // Store current task ID for context

        // Drag and Drop functionality
        let draggedElement = null;

        document.querySelectorAll('.task-card').forEach(card => {
            if (!canDrag) return;

            card.addEventListener('dragstart', function (e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            card.addEventListener('dragend', function () {
                this.classList.remove('dragging');
            });
        });

        document.querySelectorAll('.kanban-tasks').forEach(column => {
            if (!canDrag) return;

            column.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            column.addEventListener('drop', function (e) {
                e.preventDefault();
                if (draggedElement) {
                    const statusId = this.dataset.statusId;
                    const taskId = draggedElement.dataset.taskId;

                    // Calculate new position
                    const cards = Array.from(this.querySelectorAll('.task-card'));
                    const position = cards.indexOf(draggedElement);

                    // Move task via AJAX
                    moveTask(taskId, statusId, position);

                    // Visual update
                    this.appendChild(draggedElement);
                    updateTaskCounts();
                }
            });
        });

        function moveTask(taskId, statusId, position) {
            fetch(baseUrl + 'admin/tasks/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: `task_id=${taskId}&status_id=${statusId}&position=${position}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al mover la tarea');
                    location.reload();
                });
        }

        function updateTaskCounts() {
            document.querySelectorAll('.kanban-column').forEach(column => {
                const statusId = column.dataset.statusId;
                const count = column.querySelectorAll('.task-card').length;
                column.querySelector('.task-count').textContent = count;
            });
        }

        function openNewTaskModal() {
            document.getElementById('newTaskModal').classList.add('active');
        }

        function openNewTaskModal() {
            document.getElementById('newTaskModal').classList.add('active');
        }

        // Renamed to avoid conflict with global closeModal
        function closeTaskModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function createTask(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            fetch(baseUrl + 'admin/tasks/create', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new URLSearchParams(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear la tarea');
                });
        }

        function viewTask(taskId) {
            currentTaskId = taskId;
            // Load task details, history and comments
            fetch(baseUrl + 'admin/tasks/getTaskDetails?id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTaskDetails(taskId, data);
                    } else {
                        alert("Error al cargar detalles de la tarea.");
                    }
                });
        }

        function displayTaskDetails(taskId, data) {
            const { task, history, comments } = data;

            // --- Fill General Tab ---
            document.getElementById('modalTaskTitle').innerText = task.title;
            document.getElementById('modalTaskDesc').innerText = task.description || 'Sin descripción detallada.';
            document.getElementById('modalTaskPriority').innerText = task.priority;
            document.getElementById('modalTaskCreator').innerText = task.created_by_name || 'System'; // Assuming created_by_name is available
            document.getElementById('modalTaskDate').innerText = task.created_at;

            // Status Badge
            const statusEl = document.getElementById('modalTaskStatus');
            statusEl.innerText = task.status_name || "ID Estado: " + task.status_id; // Assuming status_name is available
            statusEl.style.backgroundColor = task.status_color || '#6b7280'; // Assuming status_color is available

            // Assignee Dropdown
            const assigneeSelect = document.getElementById('modalTaskAssignee');
            assigneeSelect.innerHTML = '<option value="">-- Sin Asignar --</option>';
            projectUsers.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.innerText = u.public_name || u.username;
                if (task.assigned_to == u.id) opt.selected = true;
                assigneeSelect.appendChild(opt);
            });

            // --- Render Comments ---
            const commentsHtml = `
                        <div class="comments-section mb-6">
                            <div class="comments-list space-y-4 mb-4" id="commentsList">
                                ${comments.length > 0 ? comments.map(c => `
                                    <div class="comment-bubble bg-gray-800/50 p-3 rounded-lg border border-gray-700">
                                        <div class="flex justify-between items-center mb-1 text-xs text-gray-400">
                                            <span class="font-bold text-blue-400">${c.public_name || c.username}</span>
                                            <span>${c.created_at}</span>
                                        </div>
                                        <div class="text-sm text-gray-200 whitespace-pre-wrap">${c.comment}</div>
                                    </div>
                                `).join('') : '<p class="text-gray-500 italic text-sm text-center">No hay notas ni comentarios aún.</p>'}
                            </div>
                        </div>

                        <div class="add-comment-section">
                             <form onsubmit="submitComment(event)">
                                <label class="block text-xs font-bold text-gray-400 mb-1">Agregar Nota / Comentario</label>
                                <textarea name="comment" class="form-input w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none mb-2" placeholder="Escribe un comentario o actualización..." rows="3" required></textarea>
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all shadow-lg hover:shadow-blue-500/20">
                                        Publicar
                                    </button>
                                </div>
                            </form>
                        </div>

                        ${isClient && task.status_id == 4 ? `
                            <div class="client-approval-section mt-6 pt-4 border-t border-gray-700">
                                 <h4 class="text-green-500 font-bold mb-2">Aprobación Final</h4>
                                 <form onsubmit="submitApproval(event)">
                                     <div class="approval-checkbox flex items-center gap-2 mb-3">
                                        <input type="checkbox" id="approveCheck" name="approve" value="true" required class="w-4 h-4 rounded border-gray-600 text-green-600 focus:ring-green-500">
                                        <label for="approveCheck" class="text-sm text-gray-300">Acepto los entregables y solicito finalizar la tarea.</label>
                                    </div>
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all shadow-lg hover:shadow-green-500/20">
                                        Aprobar y Finalizar
                                    </button>
                                 </form>
                            </div>
                        ` : ''}
                    `;

            // Render History
            const historyHtml = `
                         <div class="history-list space-y-3">
                            ${history.map(h => `
                                <div class="history-item p-3 rounded-lg border border-gray-700 bg-gray-800/30">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-bold text-xs uppercase tracking-wider text-blue-400">${h.action}</span>
                                        <span class="text-xs text-gray-500 text-right">${h.created_at}</span>
                                    </div>
                                     <div class="text-xs text-gray-400">
                                        <span class="text-gray-300 font-medium">${h.public_name || h.username}</span>
                                        ${h.old_status_name ? ` cambió el estado de <span class="text-gray-300">${h.old_status_name}</span> a <span class="text-gray-300">${h.new_status_name}</span>` : ''}
                                    </div>
                                     ${h.comment ? `<div class="mt-2 text-sm text-gray-300 italic bg-gray-900/50 p-2 rounded border border-gray-700/50 border-l-2 border-l-blue-500">"${h.comment}"</div>` : ''}
                                </div>
                            `).join('')}
                         </div>
                    `;

            document.getElementById('tab-comments').innerHTML = commentsHtml;
            document.getElementById('tab-history').innerHTML = historyHtml;

            document.getElementById('viewTaskModal').classList.add('active');

            // Default to General
            switchModalTab('general');
        }

        function switchModalTab(tabName) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Remove active class from buttons
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active', 'text-white', 'border-blue-500');
                el.classList.add('text-gray-400', 'border-transparent');
            });

            // Show target
            document.getElementById('tab-' + tabName).classList.remove('hidden');

            // Highlight button
            const buttons = document.querySelectorAll('.modal-tabs button');
            let idx = 0;
            if (tabName === 'general') idx = 0;
            if (tabName === 'comments') idx = 1;
            if (tabName === 'history') idx = 2;

            buttons[idx].classList.add('active', 'text-white', 'border-blue-500');
            buttons[idx].classList.remove('text-gray-400', 'border-transparent');
        }

        function updateTaskAssignment(userId) {
            if (!currentTaskId) return;

            fetch(baseUrl + 'admin/tasks/assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: `task_id=${currentTaskId}&user_id=${userId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // visual feedback?
                    } else {
                        alert('Error al asignar: ' + data.error);
                    }
                });
        }

        function submitComment(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('task_id', currentTaskId);

            fetch(baseUrl + 'admin/tasks/postComment', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new URLSearchParams(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload details without closing modal
                        viewTask(currentTaskId);
                        form.reset();
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al publicar comentario');
                });
        }

        function submitApproval(e) {
            e.preventDefault();

            // Use existing addComment for approval logic since it handles status change
            // But we need to make sure we send 'task_id', 'comment', 'approve=true'
            const form = e.target;
            const body = new URLSearchParams();
            body.append('task_id', currentTaskId);
            body.append('approve', 'true');
            body.append('comment', 'Aprobado por el cliente');

            fetch(baseUrl + 'admin/tasks/addComment', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: body
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Tarea aprobada exitosamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                });
        }

        function approveTask(taskId) {
            viewTask(taskId);
        }

        function takeTask(taskId) {
            fetch(baseUrl + 'admin/tasks/take', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: 'task_id=' + taskId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Error al tomar tarea'));
                    }
                });
        }

        function deleteTask(taskId) {
            if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;

            fetch(baseUrl + 'admin/tasks/delete?id=' + taskId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la tarea');
                });
        }

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
@endsection