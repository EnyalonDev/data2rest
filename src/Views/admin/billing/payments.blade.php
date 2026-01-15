@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-10 md:mb-16 relative">
        <div
            class="absolute -top-10 md:-top-20 left-1/2 -translate-x-1/2 w-64 md:w-96 h-64 md:h-96 bg-emerald-500/10 blur-[80px] md:blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-emerald-500 text-dark px-4 py-1 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] mb-4 md:mb-6 animate-pulse">
            💳 Payment History
        </div>
        <h1
            class="text-4xl md:text-8xl font-black text-p-title mb-4 md:mb-6 tracking-tighter uppercase italic leading-none">
            Historial de Pagos
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto px-4 text-sm md:text-base">
            Registro completo de todos los pagos recibidos
        </p>
    </header>

    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-p-muted">
            <a href="{{ $baseUrl }}admin/billing" class="hover:text-primary transition-colors">Billing</a>
            <span>&rarr;</span>
            <span class="text-primary">Historial de Pagos</span>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-card !p-6 border-t-4 border-emerald-500/50">
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-2">Total Recibido</p>
            <p class="text-3xl font-black text-emerald-500">${{ number_format($summary['total_received'] ?? 0, 2) }}</p>
            <p class="text-xs text-p-muted mt-2">{{ $summary['total_payments'] ?? 0 }} pagos</p>
        </div>
        
        <div class="glass-card !p-6 border-t-4 border-primary/50">
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-2">Este Mes</p>
            <p class="text-3xl font-black text-primary">${{ number_format($summary['month_received'] ?? 0, 2) }}</p>
            <p class="text-xs text-p-muted mt-2">{{ $summary['month_payments'] ?? 0 }} pagos</p>
        </div>
        
        <div class="glass-card !p-6 border-t-4 border-amber-500/50">
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-2">Promedio</p>
            <p class="text-3xl font-black text-amber-500">${{ number_format($summary['average_payment'] ?? 0, 2) }}</p>
            <p class="text-xs text-p-muted mt-2">por pago</p>
        </div>
        
        <div class="glass-card !p-6 border-t-4 border-purple-500/50">
            <p class="text-xs font-black text-p-muted uppercase tracking-widest mb-2">Último Pago</p>
            <p class="text-xl font-black text-purple-500">{{ $summary['last_payment_date'] ?? 'N/A' }}</p>
            <p class="text-xs text-p-muted mt-2">${{ number_format($summary['last_payment_amount'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card !p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Método de Pago</label>
                <select id="filterMethod" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                    <option value="">Todos</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="cheque">Cheque</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Cliente</label>
                <select id="filterClient" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
                    <option value="">Todos los clientes</option>
                    @foreach($clients ?? [] as $client)
                        <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Fecha Desde</label>
                <input type="date" id="filterDateFrom" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-p-muted uppercase tracking-widest mb-2">Fecha Hasta</label>
                <input type="date" id="filterDateTo" onchange="applyFilters()"
                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-primary/50 outline-none text-sm">
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="glass-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-white/5">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-black text-p-title uppercase tracking-tighter italic">Pagos Registrados</h2>
                    <p class="text-xs text-p-muted">{{ count($payments ?? []) }} pagos encontrados</p>
                </div>
                <button onclick="exportPayments()"
                    class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500 hover:text-dark border border-emerald-500/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-emerald-500 transition-all">
                    Exportar Excel
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5 border-b border-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Fecha
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cliente / Proyecto
                        </th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Cuota
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Método
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-p-muted uppercase tracking-widest">
                            Referencia
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
                <tbody id="paymentsTableBody">
                    @forelse($payments ?? [] as $payment)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors payment-row"
                            data-method="{{ strtolower($payment['payment_method'] ?? '') }}"
                            data-client-id="{{ $payment['client_id'] ?? '' }}"
                            data-date="{{ $payment['payment_date'] ?? '' }}">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ date('M d, Y', strtotime($payment['payment_date'] ?? 'now')) }}</p>
                                    <p class="text-xs text-p-muted">{{ date('H:i', strtotime($payment['created_at'] ?? 'now')) }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-p-title">{{ $payment['client_name'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-p-muted">{{ $payment['project_name'] ?? 'N/A' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-primary/20 text-primary rounded-lg text-xs font-black">
                                    #{{ $payment['installment_number'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-white/5 rounded-lg text-xs font-bold text-p-title capitalize">
                                    {{ $payment['payment_method'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-p-muted font-mono">{{ $payment['reference'] ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-lg font-black text-emerald-500">${{ number_format($payment['amount'] ?? 0, 2) }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'approved' => 'emerald',
                                        'pending' => 'amber',
                                        'rejected' => 'red'
                                    ];
                                    $pColor = $statusColors[$payment['status'] ?? 'approved'] ?? 'slate';
                                @endphp
                                <span class="px-3 py-1 bg-{{ $pColor }}-500/20 text-{{ $pColor }}-500 rounded-lg text-[10px] font-black uppercase tracking-widest border border-{{ $pColor }}-500/30">
                                    {{ $payment['status'] ?? 'approved' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    @if(($payment['status'] ?? 'approved') === 'pending' && \App\Core\Auth::isAdmin())
                                        <button onclick="approvePayment({{ $payment['id'] }})"
                                            class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500 hover:text-dark border border-emerald-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-emerald-500 transition-all">
                                            Aprobar
                                        </button>
                                        <button onclick="rejectPayment({{ $payment['id'] }})"
                                            class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500 hover:text-dark border border-red-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-red-500 transition-all">
                                            Rechazar
                                        </button>
                                    @endif
                                    <button onclick="viewPaymentDetails({{ $payment['id'] ?? 0 }})"
                                        class="px-3 py-1.5 bg-primary/10 hover:bg-primary hover:text-dark border border-primary/30 rounded-lg text-[10px] font-black uppercase tracking-widest text-primary transition-all">
                                        Detalles
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center text-emerald-500 text-3xl mx-auto mb-6">
                                    💳
                                </div>
                                <h3 class="text-xl font-bold text-p-title mb-2">No hay pagos registrados</h3>
                                <p class="text-p-muted">Los pagos aparecerán aquí una vez sean registrados</p>
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
        function applyFilters() {
            const method = document.getElementById('filterMethod').value.toLowerCase();
            const clientId = document.getElementById('filterClient').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;
            
            const rows = document.querySelectorAll('.payment-row');
            
            rows.forEach(row => {
                const rowMethod = row.getAttribute('data-method');
                const rowClientId = row.getAttribute('data-client-id');
                const rowDate = row.getAttribute('data-date');
                
                let show = true;
                
                if (method && rowMethod !== method) show = false;
                if (clientId && rowClientId !== clientId) show = false;
                if (dateFrom && rowDate < dateFrom) show = false;
                if (dateTo && rowDate > dateTo) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function viewPaymentDetails(paymentId) {
            fetch(`{{ $baseUrl }}api/billing/payments/${paymentId}`, {
                headers: {
                    'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const payment = data.data;
                    const html = `
                        <div class="space-y-6">
                            <div class="bg-white/5 p-6 rounded-xl">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Monto</p>
                                        <p class="text-2xl font-black text-emerald-500">$${parseFloat(payment.amount).toFixed(2)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Fecha de Pago</p>
                                        <p class="text-sm font-bold text-p-title">${new Date(payment.payment_date).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Método</p>
                                        <p class="text-sm font-bold text-p-title capitalize">${payment.payment_method}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-p-muted mb-1">Referencia</p>
                                        <p class="text-sm font-bold text-p-title font-mono">${payment.reference || '-'}</p>
                                    </div>
                                </div>
                            </div>
                            
                            ${payment.notes ? `
                                <div class="bg-white/5 p-6 rounded-xl">
                                    <p class="text-xs text-p-muted mb-2">Notas</p>
                                    <p class="text-sm text-p-title">${payment.notes}</p>
                                </div>
                            ` : ''}
                            
                            <div class="bg-white/5 p-6 rounded-xl">
                                <h4 class="text-xs font-black text-p-muted uppercase tracking-widest mb-4">Información de la Cuota</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-p-muted">Cliente:</span>
                                        <span class="text-sm font-bold text-p-title">${payment.client_name || 'N/A'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-p-muted">Proyecto:</span>
                                        <span class="text-sm font-bold text-p-title">${payment.project_name || 'N/A'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-p-muted">Cuota:</span>
                                        <span class="text-sm font-bold text-p-title">#${payment.installment_number || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    showModal({
                        title: '💳 Detalles del Pago',
                        message: '',
                        type: 'modal',
                        maxWidth: 'max-w-2xl'
                    });

                    document.getElementById('modal-message').innerHTML = html;
                }
            });
        }

        function exportPayments() {
            showModal({
                title: '📊 Exportar Pagos',
                message: 'La funcionalidad de exportación estará disponible próximamente.',
                type: 'info'
            });
        }

        function approvePayment(paymentId) {
            showModal({
                title: '✅ Aprobar Pago',
                message: '¿Estás seguro de que deseas aprobar este pago? Esto marcará la cuota correspondiente como pagada.',
                type: 'confirm',
                onConfirm: () => {
                    fetch(`{{ $baseUrl }}api/billing/payments/${paymentId}/approve`, {
                        method: 'POST',
                        headers: { 'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showModal({
                                title: 'Éxito',
                                message: 'Pago aprobado correctamente',
                                type: 'success',
                                onConfirm: () => window.location.reload()
                            });
                        }
                    });
                }
            });
        }

        function rejectPayment(paymentId) {
            const html = `
                <div class="space-y-4">
                    <p class="text-sm text-p-muted">Explica brevemente el motivo del rechazo para que el cliente pueda verlo.</p>
                    <textarea id="rejectReason" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-red-500/50 outline-none resize-none" rows="4" placeholder="Ej: El comprobante no es legible..."></textarea>
                </div>
            `;
            
            showModal({
                title: '❌ Rechazar Pago',
                message: '',
                type: 'confirm',
                confirmText: 'Rechazar Pago',
                onConfirm: () => {
                    const reason = document.getElementById('rejectReason').value;
                    fetch(`{{ $baseUrl }}api/billing/payments/${paymentId}/reject`, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-API-KEY': '{{ $_SESSION['api_key'] ?? '' }}' 
                        },
                        body: JSON.stringify({ reason })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showModal({
                                title: 'Rechazado',
                                message: 'El pago ha sido rechazado',
                                type: 'info',
                                onConfirm: () => window.location.reload()
                            });
                        }
                    });
                }
            });

            document.getElementById('modal-message').innerHTML = html;
        }
    </script>
@endsection
