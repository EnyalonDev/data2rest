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
                <button class="close-modal" onclick="closeModal('newTaskModal')">&times;</button>
            </div>
            <form id="newTaskForm" onsubmit="createTask(event)">
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
        <div class="glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl border-2 border-white/10">
            <div class="modal-header">
                <h2 class="modal-title">Detalles de la Tarea</h2>
                <button class="close-modal" onclick="closeModal('viewTaskModal')">&times;</button>
            </div>
            <div id="taskDetails"></div>
        </div>
    </div>

    <script>
        const baseUrl = '{{ $baseUrl }}';
        const canDrag = {{ $canDrag ? 'true' : 'false' }};
        const isClient = {{ $isClient ? 'true' : 'false' }};

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

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function createTask(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            fetch(baseUrl + 'admin/tasks/create', {
                method: 'POST',
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
            // Load task details and history
            fetch(baseUrl + 'admin/tasks/history?id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTaskDetails(taskId, data.history);
                    }
                });
        }

        function displayTaskDetails(taskId, history) {
            const detailsHtml = `
                                <div class="history-section">
                                    <h3 style="margin-bottom: 1rem;">Historial de Cambios</h3>
                                    ${history.map(h => `
                                        <div class="history-item">
                                            <div class="history-header">
                                                <span class="history-action">${h.action}</span>
                                                <span class="history-time">${h.created_at}</span>
                                            </div>
                                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                                ${h.public_name || h.username}
                                                ${h.old_status_name ? ' de ' + h.old_status_name : ''}
                                                ${h.new_status_name ? ' a ' + h.new_status_name : ''}
                                            </div>
                                            ${h.comment ? `<div style="margin-top: 0.5rem; padding: 0.5rem; background: var(--bg-primary); border-radius: 4px;">${h.comment}</div>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                                ${isClient ? `
                                    <div class="client-approval-section">
                                        <h4 style="margin-bottom: 0.5rem; color: #166534;">Aprobar y Finalizar Tarea</h4>
                                        <form onsubmit="submitApproval(event, ${taskId})">
                                            <textarea name="comment" class="form-textarea" placeholder="Comentario de aprobación..." required></textarea>
                                            <div class="approval-checkbox">
                                                <input type="checkbox" id="approveCheck" name="approve" value="true" required>
                                                <label for="approveCheck">Acepto los entregables y solicito cierre de tarea</label>
                                            </div>
                                            <button type="submit" class="btn-primary" style="margin-top: 1rem; background: #10b981;">
                                                Aprobar y Finalizar
                                            </button>
                                        </form>
                                    </div>
                                ` : ''}
                            `;

            document.getElementById('taskDetails').innerHTML = detailsHtml;
            document.getElementById('viewTaskModal').classList.add('active');
        }

        function submitApproval(e, taskId) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('task_id', taskId);

            fetch(baseUrl + 'admin/tasks/addComment', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.moved_to_done ? 'Tarea aprobada y finalizada correctamente' : 'Comentario agregado');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al aprobar la tarea');
                });
        }

        function approveTask(taskId) {
            viewTask(taskId);
        }

        function deleteTask(taskId) {
            if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;

            fetch(baseUrl + 'admin/tasks/delete?id=' + taskId, {
                method: 'POST'
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