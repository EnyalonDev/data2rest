<!-- Global System Modal -->
<div id="system-modal"
    class="fixed inset-0 z-[1000] hidden items-center justify-center p-6 bg-black/80 backdrop-blur-md">
    <div class="glass-card max-w-lg w-full scale-95 opacity-0 transition-all duration-300 transform" id="modal-content">
        <div class="flex items-center gap-4 mb-6">
            <div id="modal-icon" class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl"></div>
            <div>
                <h3 id="modal-title" class="text-xl font-black text-white italic tracking-tight">System Notification
                </h3>
                <p id="modal-type-label" class="text-[9px] font-black uppercase text-slate-500 tracking-[0.2em]"></p>
            </div>
        </div>
        <p id="modal-message" class="text-slate-400 mb-6 leading-relaxed font-medium"></p>

        <!-- Safety Checkbox -->
        <div id="modal-safety-container" class="hidden mb-10 p-4 rounded-xl bg-red-500/5 border border-red-500/10">
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" id="modal-safety-checkbox"
                    class="w-5 h-5 rounded border-glass-border bg-white/5 text-primary focus:ring-primary/20 transition-all cursor-pointer">
                <span id="modal-safety-text"
                    class="text-xs font-bold text-slate-400 group-hover:text-slate-200 transition-colors"></span>
            </label>
        </div>

        <div class="flex justify-end gap-4" id="modal-actions">
            <button onclick="closeModal()" id="modal-dismiss-btn"
                class="px-6 py-3 rounded-xl border border-glass-border text-xs font-black uppercase hover:bg-white/5 transition-all text-slate-400 hover:text-white">{{ \App\Core\Lang::get('common.dismiss') }}</button>
            <button id="modal-confirm-btn"
                class="hidden px-8 py-3 rounded-xl bg-primary text-dark font-black text-xs uppercase hover:scale-105 transition-all shadow-xl shadow-primary/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">{{ \App\Core\Lang::get('common.confirm') }}</button>
        </div>
    </div>
</div>

<script>
    let modalTimeout = null;

    function showModal(options) {
        if (modalTimeout) {
            clearTimeout(modalTimeout);
            modalTimeout = null;
        }

        const modal = document.getElementById('system-modal');
        const content = document.getElementById('modal-content');
        const title = document.getElementById('modal-title');
        const msg = document.getElementById('modal-message');
        const icon = document.getElementById('modal-icon');
        const label = document.getElementById('modal-type-label');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const dismissBtn = document.getElementById('modal-dismiss-btn');

        const safetyContainer = document.getElementById('modal-safety-container');
        const safetyCheckbox = document.getElementById('modal-safety-checkbox');
        const safetyText = document.getElementById('modal-safety-text');

        title.innerText = options.title || 'System Alert';
        msg.innerText = options.message || '';
        label.innerText = options.typeLabel || 'Protocol Message';

        // Custom button text
        if (options.confirmText) confirmBtn.innerText = options.confirmText;
        else confirmBtn.innerText = '{!! addslashes(\App\Core\Lang::get('common.confirm')) !!}';

        if (options.dismissText) dismissBtn.innerText = options.dismissText;
        else dismissBtn.innerText = '{!! addslashes(\App\Core\Lang::get('common.dismiss')) !!}';

        // Reset classes and state
        content.className = 'glass-card w-full flex flex-col scale-95 opacity-0 transition-all duration-300 transform ' + (options.maxWidth || 'max-w-lg');
        content.style.maxHeight = '90vh';

        const msgContainer = document.getElementById('modal-message');
        msgContainer.className = 'text-slate-400 mb-6 leading-relaxed font-medium overflow-y-auto custom-scrollbar pr-2';

        confirmBtn.classList.add('hidden');
        confirmBtn.disabled = false;
        confirmBtn.onclick = null;
        safetyContainer.classList.add('hidden');
        safetyCheckbox.checked = false;

        // Safety Check Logic
        if (options.safetyCheck) {
            safetyContainer.classList.remove('hidden');
            safetyText.innerText = options.safetyCheck;
            confirmBtn.disabled = true;
            safetyCheckbox.onchange = (e) => {
                confirmBtn.disabled = !e.target.checked;
            };
        }

        // Icon & Color
        if (options.type === 'error' || options.type === 'modal') {
            icon.innerText = (options.type === 'error') ? '⚠️' : '📊';
            icon.className = 'w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-2xl ' +
                (options.type === 'error' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-primary/10 text-primary border border-primary/20');
        } else if (options.type === 'confirm') {
            icon.innerText = '🛡️';
            icon.className = 'w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20';
        } else if (options.type === 'success') {
            icon.innerText = '✅';
            icon.className = 'w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-2xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
        } else {
            // Default Info
            icon.innerText = 'ℹ️';
            icon.className = 'w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-2xl bg-blue-500/10 text-blue-500 border border-blue-500/20';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('system-modal');
        const content = document.getElementById('modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        modalTimeout = setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modalTimeout = null;
        }, 300);
    }

    // Auto-show flash message for ALL types
    @php
    $fm = \App\Core\Auth::getFlashMsg();
    @endphp
    @if($fm)
        document.addEventListener('DOMContentLoaded', () => {
            showModal({
                title: '{{ $fm['type'] === 'success' ? 'Operación Exitosa' : ($fm['type'] === 'error' ? 'Error de Sistema' : 'Notificación') }}',
                message: '{!! addslashes($fm['text']) !!}',
                type: '{{ $fm['type'] }}',
                typeLabel: '{{ strtoupper($fm['type']) }}'
            });
        });
    @endif
</script>