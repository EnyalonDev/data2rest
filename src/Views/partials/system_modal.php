<!-- Global System Modal -->
<div id="system-modal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-6 bg-black/80 backdrop-blur-md">
    <div class="glass-card max-w-lg w-full scale-95 opacity-0 transition-all duration-300 transform" id="modal-content">
        <div class="flex items-center gap-4 mb-6">
            <div id="modal-icon" class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl"></div>
            <div>
                <h3 id="modal-title" class="text-xl font-black text-white italic tracking-tight">System Notification</h3>
                <p id="modal-type-label" class="text-[9px] font-black uppercase text-slate-500 tracking-[0.2em]"></p>
            </div>
        </div>
        <p id="modal-message" class="text-slate-400 mb-10 leading-relaxed font-medium"></p>
        <div class="flex justify-end gap-4" id="modal-actions">
            <button onclick="closeModal()" class="px-6 py-3 rounded-xl border border-glass-border text-xs font-black uppercase hover:bg-white/5 transition-all">Dismiss</button>
            <button id="modal-confirm-btn" class="hidden px-8 py-3 rounded-xl bg-primary text-dark font-black text-xs uppercase hover:scale-105 transition-all shadow-xl shadow-primary/20">Execute</button>
        </div>
    </div>
</div>

<script>
function showModal(options) {
    const modal = document.getElementById('system-modal');
    const content = document.getElementById('modal-content');
    const title = document.getElementById('modal-title');
    const msg = document.getElementById('modal-message');
    const icon = document.getElementById('modal-icon');
    const label = document.getElementById('modal-type-label');
    const confirmBtn = document.getElementById('modal-confirm-btn');

    title.innerText = options.title || 'System Alert';
    msg.innerText = options.message || '';
    label.innerText = options.typeLabel || 'Protocol Message';
    
    // Reset buttons
    confirmBtn.classList.add('hidden');
    confirmBtn.onclick = null;

    // Icon & Color
    if (options.type === 'error' || options.type === 'modal') {
        icon.innerText = '⚠️';
        icon.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-2xl bg-red-500/10 text-red-500 border border-red-500/20';
    } else if (options.type === 'confirm') {
        icon.innerText = '🛡️';
        icon.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20';
        confirmBtn.classList.remove('hidden');
        confirmBtn.onclick = () => {
            if (options.onConfirm) options.onConfirm();
            closeModal();
        };
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
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

// Auto-show flash message if type is modal
<?php 
$fm = \App\Core\Auth::getFlashMsg(); 
if ($fm): 
    if ($fm['type'] === 'modal'): ?>
        document.addEventListener('DOMContentLoaded', () => {
            showModal({
                title: 'Security Override',
                message: '<?php echo addslashes($fm['text']); ?>',
                type: 'error',
                typeLabel: 'ACCESS POLICY VIOLATION'
            });
        });
    <?php endif; 
endif; ?>
</script>
