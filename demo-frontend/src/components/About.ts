import { ApiService } from '../services/api';
import { WebPage } from '../types';
import { showToast } from '../utils/toast';

export class About {
    private data: WebPage | null = null;

    async render(): Promise<string> {
        this.data = await ApiService.getAbout();
        return `
            <section id="about" class="about">
                <h2 class="section-title">Our Manifesto</h2>
                <div class="about-grid">
                    <div>
                        <h3 style="font-size: 2rem; margin-bottom: 1.5rem;">${this.data.title}</h3>
                        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem;">${this.data.content}</p>
                        <button class="btn" id="edit-about-btn">Edit Identity</button>
                    </div>
                    <div style="position: relative;">
                        <img src="${this.data.featured_image || 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072'}" class="about-img">
                    </div>
                </div>
            </section>
        `;
    }

    attachEvents() {
        const btn = document.getElementById('edit-about-btn');
        if (btn) btn.onclick = () => this.openModal();
    }

    private openModal() {
        if (!this.data) return;
        const modalRoot = document.getElementById('modal-root');
        if (!modalRoot) return;

        modalRoot.innerHTML = `
            <div id="edit-modal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 2000; display: flex; items-center: center; justify-content: center; padding: 2rem;">
                <div class="glass-card" style="max-width: 600px; width: 100%;">
                    <h3 class="section-title" style="margin-bottom: 2rem; font-size: 1.5rem;">Update Section</h3>
                    <form id="edit-form">
                        <input type="hidden" name="id" value="${this.data.id}">
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="input" value="${this.data.title}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Content</label>
                            <textarea name="content" class="input" rows="5" required>${this.data.content}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Image Asset</label>
                            <input type="file" name="featured_image" class="input" accept="image/*">
                        </div>
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                            <button type="button" class="btn" style="background: #334155;" id="close-modal">Cancel</button>
                            <button type="submit" class="btn">Commit Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const form = document.getElementById('edit-form') as HTMLFormElement;
        const closeBtn = document.getElementById('close-modal');

        if (closeBtn) closeBtn.onclick = () => (modalRoot.innerHTML = '');

        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const response = await ApiService.updatePage(this.data!.id, formData);
                if (response.ok) {
                    showToast('Identity synchronized successfully');
                    modalRoot.innerHTML = '';
                    window.location.reload(); // Simple way to refresh the app state
                } else {
                    const err = await response.json();
                    showToast(err.error || 'Sync failed', 'error');
                }
            } catch (err) {
                showToast('Link failure', 'error');
            }
        };
    }
}
