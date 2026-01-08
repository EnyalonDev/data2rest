// --- Configuration ---
const BASE_URL = import.meta.env.VITE_API_BASE_URL;
const API_KEY = import.meta.env.VITE_API_KEY;

// --- Application State ---
const state = {
    currentView: 'home',
    hero: { title: '', content: '' },
    about: { title: '', content: '', image: '' },
    services: []
};

// --- Initialization ---
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    navigate('home');
});

// --- Routing / Navigation ---
async function navigate(viewId) {
    state.currentView = viewId;

    // Update active nav state
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    const activeNav = document.getElementById(`nav-${viewId}`);
    if (activeNav) activeNav.classList.add('active');

    const titleEl = document.getElementById('current-view-title');
    const container = document.getElementById('view-container');

    // Transitions
    container.style.opacity = '0';
    container.style.transform = 'translateY(10px)';

    setTimeout(async () => {
        switch (viewId) {
            case 'home':
                titleEl.innerText = 'Neural Workspace Control';
                await renderHome(container);
                break;
            case 'about':
                titleEl.innerText = 'Manifesto Configuration';
                await renderAbout(container);
                break;
            case 'services':
                titleEl.innerText = 'System Capabilities';
                await renderServices(container);
                break;
            case 'contact':
                titleEl.innerText = 'Nexus Communication';
                await renderContact(container);
                break;
        }

        container.style.transition = 'all 0.4s var(--transition)';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
        lucide.createIcons();
    }, 200);
}

// --- View Renderers ---

async function renderHome(container) {
    try {
        const response = await fetch(`${BASE_URL}/web_pages/1`, {
            headers: { 'X-API-Key': API_KEY }
        });
        const data = await response.json();
        state.hero = data;

        container.innerHTML = `
            <div class="animate-in">
                <div class="card mb-12">
                    <span class="card-label">Primary Node Status</span>
                    <h1 class="card-title">${data.title || 'Injected Title'}</h1>
                    <p class="card-desc">${data.content || 'System manifesto waiting for input...'}</p>
                    <div class="flex gap-4">
                        <button class="btn btn-primary" onclick="navigate('about')">
                            <i data-lucide="edit-3"></i> Configure Manifesto
                        </button>
                        <button class="btn btn-ghost" onclick="showToast('Connection metrics stable')">
                            <i data-lucide="activity"></i> Metrics
                        </button>
                    </div>
                </div>

                <div class="grid">
                    <div class="card" style="padding: 1.5rem;">
                        <div class="flex items-center gap-4">
                            <i data-lucide="database" class="text-primary"></i>
                            <div>
                                <div class="text-[10px] font-black uppercase text-text-muted">Database Integrity</div>
                                <div class="font-bold">Verified & Secure</div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="padding: 1.5rem;">
                        <div class="flex items-center gap-4">
                            <i data-lucide="zap" class="text-accent"></i>
                            <div>
                                <div class="text-[10px] font-black uppercase text-text-muted">API Latency</div>
                                <div class="font-bold">24ms (Local Loop)</div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="padding: 1.5rem;">
                        <div class="flex items-center gap-4">
                            <i data-lucide="shield-check" class="text-success"></i>
                            <div>
                                <div class="text-[10px] font-black uppercase text-text-muted">Auth Context</div>
                                <div class="font-bold">Encrypted Session</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } catch (e) {
        showToast('Communication Breach: Failed to fetch workspace data', 'error');
    }
}

async function renderAbout(container) {
    try {
        const response = await fetch(`${BASE_URL}/web_pages/2`, {
            headers: { 'X-API-Key': API_KEY }
        });
        const data = await response.json();
        state.about = data;

        container.innerHTML = `
            <div class="animate-in">
                <div class="grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="card">
                        <span class="card-label">Node Integrity</span>
                        <h2 class="card-title">${data.title}</h2>
                        <p class="card-desc">${data.content}</p>
                        <button class="btn btn-primary" onclick="openEditModal('about')">
                            <i data-lucide="edit-2"></i> Update Module
                        </button>
                    </div>
                    <div class="card" style="padding: 0; overflow: hidden; border: none;">
                        <img src="${data.featured_image || 'https://images.unsplash.com/photo-1639322537228-f710d846310a?q=80&w=2000'}" 
                             style="width: 100%; height: 100%; object-cover: cover; filter: saturate(0.8) contrast(1.1);">
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, var(--bg-main), transparent);"></div>
                    </div>
                </div>
            </div>
        `;
    } catch (e) {
        showToast('Failed to load manifesto data', 'error');
    }
}

async function renderServices(container) {
    try {
        const response = await fetch(`${BASE_URL}/servicios`, {
            headers: { 'X-API-Key': API_KEY }
        });
        const result = await response.json();
        const data = result.data || result; // Handle both direct array and wrapped response
        state.services = data;

        container.innerHTML = `
            <div class="animate-in">
                <div class="grid">
                    ${data.map(s => `
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i data-lucide="${getIcon(s.icon || s.icon_name)}"></i>
                            </div>
                            <h3 style="font-size: 1.4rem; margin-bottom: 0.8rem; font-weight: 700;">${s.title || s.nombre}</h3>
                            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">${s.description || s.descripcion}</p>
                            <span class="btn btn-ghost" style="font-size: 0.7rem; padding: 0.5rem 1rem;">System Core: ${s.id}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } catch (e) {
        showToast('System capabilities offline', 'error');
    }
}

async function renderContact(container) {
    container.innerHTML = `
        <div class="animate-in" style="max-w: 800px; margin: 0 auto;">
            <div class="card">
                <span class="card-label">Nexus Protocol</span>
                <h2 class="card-title">Initiate Signal</h2>
                <p class="card-desc">Send structured packets to the backend neural nodes via the unified API gateway.</p>
                
                <form id="nexus-form" class="space-y-6">
                    <div class="grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label class="form-label">Identifier (Name)</label>
                            <input type="text" name="nombre" class="input-field" placeholder="User Alpha" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Signal Origin (Email)</label>
                            <input type="email" name="email" class="input-field" placeholder="alpha@system.io" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Packet Content (Message)</label>
                        <textarea name="mensaje" class="input-field" rows="4" placeholder="Encrypted message payload..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Attachment (Image/Docs/Archive)</label>
                        <input type="file" name="adjunto" class="input-field" accept="image/*,.pdf,.txt,.doc,.docx,.odt,.md,.rar,.zip">
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.5rem;">Maximum per-packet size: Cluster Limit (Defined in Server Config)</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">
                         <i data-lucide="zap"></i> Broadcast Signal
                    </button>
                </form>
            </div>
        </div>
    `;

    document.getElementById('nexus-form').onsubmit = handleContactSubmit;
}

// --- Logic & Actions ---

async function handleContactSubmit(e) {
    e.preventDefault();
    const form = e.target;

    // Use the validation logic we built before
    const validation = validateFiles(form, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip']);
    if (!validation.valid) {
        showToast(validation.error, 'error');
        return;
    }

    const formData = new FormData(form);
    const btn = form.querySelector('button');
    const originalText = btn.innerHTML;

    try {
        btn.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i> Processing...';
        btn.disabled = true;
        lucide.createIcons();

        const response = await fetch(`${BASE_URL}/mensajes_de_contacto`, {
            method: 'POST',
            headers: { 'X-API-Key': API_KEY },
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            showToast('Signal broadcast successful! Node acknowledgement received.');
            form.reset();
        } else {
            showToast(result.error || 'Signal collision: Failed to transmit payload.', 'error');
        }
    } catch (error) {
        showToast('Communication Link Down: Connection error.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        lucide.createIcons();
    }
}

function openEditModal(type) {
    const data = type === 'about' ? state.about : state.hero;
    const modalOverlay = document.getElementById('modal-overlay');
    const modalContent = document.getElementById('modal-content');

    modalContent.innerHTML = `
        <span class="card-label">Configuration Overlay</span>
        <h3 class="card-title">Modify Node [${data.id}]</h3>
        
        <form id="edit-form" class="space-y-6">
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-group">
                <label class="form-label">Node Header (Title)</label>
                <input type="text" name="title" class="input-field" value="${data.title}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Node Narrative (Content)</label>
                <textarea name="content" class="input-field" rows="5" required>${data.content}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Visual Asset (Featured Image)</label>
                <input type="file" name="featured_image" class="input-field" accept="image/*">
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.5rem;">Current: ${data.featured_image ? 'Asset linked' : 'No asset'}</p>
            </div>
            
            <div class="flex justify-end gap-4 pt-4 border-top border-white/5">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    `;

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('flex');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    document.getElementById('edit-form').onsubmit = handleUpdateSubmit;
    lucide.createIcons();
}

async function handleUpdateSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    formData.append('_method', 'PATCH');

    // Validation
    const validation = validateFiles(form, ['jpg', 'jpeg', 'png', 'webp']);
    if (!validation.valid) {
        showToast(validation.error, 'error');
        return;
    }

    try {
        const response = await fetch(`${BASE_URL}/web_pages/${id}`, {
            method: 'POST', // Spoofed PATCH
            headers: { 'X-API-Key': API_KEY },
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            showToast('Module reconfiguration complete.');
            closeModal();
            navigate(state.currentView); // Refresh
        } else {
            showToast(result.error || 'Reconfiguration failed.', 'error');
        }
    } catch (error) {
        showToast('Connection unstable.', 'error');
    }
}

function closeModal() {
    const modalOverlay = document.getElementById('modal-overlay');
    const modalContent = document.getElementById('modal-content');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modalOverlay.classList.remove('flex');
        modalOverlay.classList.add('hidden');
    }, 300);
}

// --- Helpers ---

function validateFiles(form, allowedExtensions) {
    const fileInputs = form.querySelectorAll('input[type="file"]');
    for (const input of fileInputs) {
        if (input.files.length > 0) {
            const fileName = input.files[0].name;
            const ext = fileName.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                return { valid: false, error: `Protocol Violation: Invalid extension .${ext}. Expecting: ${allowedExtensions.join(', ')}` };
            }
        }
    }
    return { valid: true };
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
    toast.innerHTML = `<i data-lucide="${icon}"></i> <span>${message}</span>`;

    container.appendChild(toast);
    lucide.createIcons();

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function getIcon(iconName) {
    const icons = {
        'web': 'globe',
        'code': 'code-2',
        'mobile': 'smartphone',
        'rocket': 'rocket',
        'cloud-server': 'cloud',
        'api': 'braces',
        'chart-bar': 'bar-chart-3'
    };
    return icons[iconName] || iconName || 'sparkles';
}
