@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-amber-500/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-amber-500 text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            📋 Installments Management
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Cuotas
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Gestión de cuotas y registro de pagos
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Cuotas</span>
        </nav>
    </div>

    <!-- Filters -->
    <div class="glass-card !p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Estado</label>
                <select id="filterStatus" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                    <option value="all" {{ $currentFilter == 'all' ? 'selected' : '' }}>Todas</option>
                    <option value="pending" {{ $currentFilter == 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="upcoming" {{ $currentFilter == 'upcoming' ? 'selected' : '' }}>Próximas a Vencer</option>
                    <option value="overdue" {{ $currentFilter == 'overdue' ? 'selected' : '' }}>Vencidas</option>
                    <option value="paid" {{ $currentFilter == 'paid' ? 'selected' : '' }}>Pagadas</option>
                </select>
            </div>

            <!-- Project Filter -->
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Proyecto</label>
                <select id="filterProject" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                    <option value="">Todos los proyectos</option>
                    @foreach($projects as $proj)
                        <option value="{{ $proj['id'] }}" {{ $currentProjectId == $proj['id'] ? 'selected' : '' }}>
                            {{ $proj['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Buscar</label>
                <input type="text" id="searchInstallments" placeholder="Buscar por proyecto o cliente..."
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder-p-muted focus:border-primary/50 outline-none text-sm">
            </div>
        </div>
    </div>

    <!-- Installments Table -->
    <div class="glass-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-white/5">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-black text-p-title uppercase tracking-tighter italic">Cuotas</h2>
                    <p class="text-xs text-p-muted">{{ count($installments) }} cuotas encontradas</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5 border-b border-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cuota
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Proyecto / Cliente
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Plan
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Vencimiento
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Monto
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Estado
                        </th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody id="installmentsTableBody">
                    @forelse($installments as $inst)
                        @php
                            $statusColors = [
                                'pendiente' => 'amber',
                                'pagada' => 'emerald',
                                'vencida' => 'red',
                                'cancelada' => 'slate'
                            ];
                            $color = $statusColors[$inst['status']] ?? 'slate';
                        @endphp
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors installment-row"
                            data-project-name="{{ strtolower($inst['project_name']) }}"
                            data-client-name="{{ strtolower($inst['client_name']) }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-{{ $color }}-500/20 flex items-center justify-center text-{{ $color }}-500 font-black">
                                        #{{ $inst['installment_number'] }}
                                    </div>
                                    <div>
                                        <p class="text-xs text-p-muted">Cuota {{ $inst['installment_number'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ $inst['project_name'] }}</p>
                                    <p class="text-xs text-p-muted">{{ $inst['client_name'] }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-p-title">{{ $inst['plan_name'] }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ date('M d, Y', strtotime($inst['due_date'])) }}</p>
                                    @if($inst['status'] == 'pendiente')
                                        @php
                                            $daysUntilDue = (strtotime($inst['due_date']) - time()) / (60 * 60 * 24);
                                        @endphp
                                        @if($daysUntilDue < 0)
                                            <p class="text-xs text-red-500">Vencida</p>
                                        @elseif($daysUntilDue <= 5)
                                            <p class="text-xs text-amber-500">{{ (int)$daysUntilDue }} días</p>
                                        @else
                                            <p class="text-xs text-p-muted">{{ (int)$daysUntilDue }} días</p>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div>
                                    <p class="text-lg font-black text-p-title">${{ number_format($inst['amount'], 2) }}</p>
                                    @if($inst['paid_amount'] > 0 && $inst['status'] != 'pagada')
                                        <p class="text-xs text-emerald-500">Pagado: ${{ number_format($inst['paid_amount'], 2) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-{{ $color }}-500/20 text-{{ $color }}-500 rounded-lg text-[10px] font-black uppercase tracking-widest border border-{{ $color }}-500/30">
                                    {{ ucfirst($inst['status']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    @if($inst['status'] == 'pendiente' || $inst['status'] == 'vencida')
                                        @if(\App\Core\Auth::isAdmin())
                                            <button onclick="registerPayment({{ $inst['id'] }}, {{ $inst['amount'] }}, '{{ $inst['project_name'] }}', {{ $inst['installment_number'] }}, 'direct')"
                                                class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500 hover:text-dark border border-emerald-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-emerald-500 transition-all">
                                                Registrar Pago
                                            </button>
                                        @endif
                                        <button onclick="registerPayment({{ $inst['id'] }}, {{ $inst['amount'] }}, '{{ $inst['project_name'] }}', {{ $inst['installment_number'] }}, 'report')"
                                            class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500 hover:text-dark border border-amber-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-amber-500 transition-all">
                                            Reportar Pago
                                        </button>
                                    @endif
                                    <button onclick="viewDetails({{ $inst['id'] }})"
                                        class="px-3 py-1.5 bg-primary/10 hover:bg-primary hover:text-dark border border-primary/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-primary transition-all">
                                        Detalles
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center text-amber-500 text-3xl mx-auto mb-6">
                                    📋
                                </div>
                                <h3 class="text-xl font-bold text-p-title mb-2">No hay cuotas</h3>
                                <p class="text-p-muted">No se encontraron cuotas con los filtros seleccionados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Search functionality
        document.getElementById('searchInstallments').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.installment-row');
            
            rows.forEach(row => {
                const projectName = row.getAttribute('data-project-name');
                const clientName = row.getAttribute('data-client-name');
                
                if (projectName.includes(searchTerm) || clientName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function applyFilters() {
            const status = document.getElementById('filterStatus').value;
            const project = document.getElementById('filterProject').value;
            
            let url = '{{ $baseUrl }}admin/billing/installments?';
            if (status && status !== 'all') url += `filter=${status}&`;
            if (project) url += `project_id=${project}&`;
            
            window.location.href = url;
        }

        function registerPayment(installmentId, amount, projectName, installmentNumber, type = 'direct') {
            const isDirect = type === 'direct';
            const html = `
                <form id="paymentForm" class="space-y-6">
                    <div class="bg-white/5 p-4 rounded-xl mb-6">
                        <p class="text-sm text-p-muted mb-2">Proyecto: <span class="text-p-title font-bold">${projectName}</span></p>
                        <p class="text-sm text-p-muted">Cuota: <span class="text-p-title font-bold">#${installmentNumber}</span></p>
                        <p class="text-lg text-primary font-black mt-2">Monto: $${parseFloat(amount).toFixed(2)}</p>
                        ${!isDirect ? '<p class="text-[10px] text-amber-500 font-bold uppercase mt-2">⚠️ Este pago será enviado a revisión</p>' : ''}
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Monto Pagado *</label>
                        <input type="number" name="amount" step="0.01" value="${amount}" required
                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Fecha de Pago *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Método de Pago *</label>
                        <select name="payment_method" required
                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                            <option value="">Seleccionar...</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                            <option value="cheque">Cheque</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Referencia</label>
                        <input type="text" name="reference" placeholder="Número de transacción, recibo, etc."
                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-p-muted uppercase tracking-widest mb-2">Notas</label>
                        <textarea name="notes" rows="3" placeholder="Notas adicionales..."
                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none resize-none"></textarea>
                    </div>
                </form>
            `;

            showModal({
                title: isDirect ? '💰 Registrar Pago' : '📑 Reportar Pago',
                message: '',
                type: 'confirm',
                confirmText: isDirect ? 'Registrar Pago' : 'Enviar Reporte',
                maxWidth: 'max-w-2xl',
                onConfirm: () => submitPayment(installmentId, type)
            });

            document.getElementById('modal-message').innerHTML = html;
        }

        function submitPayment(installmentId, type = 'direct') {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            const endpoint = type === 'direct' ? 'pay' : 'report';

            fetch(`{{ $baseUrl }}api/billing/installments/${installmentId}/${endpoint}`, {
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
                        title: '✅ Éxito',
                        message: type === 'direct' ? 'El pago ha sido registrado exitosamente' : 'El pago ha sido reportado y está pendiente de revisión',
                        type: 'success',
                        onConfirm: () => window.location.reload()
                    });
                } else {
                    showModal({
                        title: '❌ Error',
                        message: data.message || 'Error al registrar el pago',
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

        function viewDetails(installmentId) {
            fetch(`{{ $baseUrl }}api/billing/installments/${installmentId}`, {
                headers: {
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const inst = data.data;
                    const statusColors = {
                        'pendiente': 'amber',
                        'pagada': 'emerald',
                        'vencida': 'red',
                        'cancelada': 'slate'
                    };
                    const color = statusColors[inst.status] || 'slate';
                    
                    const html = `
                        <div class="space-y-6">
                            <div class="bg-white/5 p-6 rounded-xl">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Cuota</p>
                                        <p class="text-2xl font-black text-p-title">#${inst.installment_number}</p>
                                    </div>
                                    <span class="px-4 py-2 bg-${color}-500/20 text-${color}-500 rounded-lg text-xs font-black uppercase tracking-widest border border-${color}-500/30">
                                        ${inst.status.charAt(0).toUpperCase() + inst.status.slice(1)}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Monto</p>
                                        <p class="text-xl font-black text-primary">$${parseFloat(inst.amount).toFixed(2)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Vencimiento</p>
                                        <p class="text-sm font-bold text-p-title">${new Date(inst.due_date).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white/5 p-6 rounded-xl">
                                <h4 class="text-xs font-black text-p-muted uppercase tracking-widest mb-4">Información del Proyecto</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-p-muted">Proyecto:</span>
                                        <span class="text-sm font-bold text-p-title">${inst.project_name || 'N/A'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-p-muted">Plan:</span>
                                        <span class="text-sm font-bold text-p-title">${inst.plan_name || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    showModal({
                        title: 'Detalles de la Cuota',
                        message: '',
                        type: 'modal',
                        maxWidth: 'max-w-2xl'
                    });

                    document.getElementById('modal-message').innerHTML = html;
                }
            });
        }
    </script>
@endsection
