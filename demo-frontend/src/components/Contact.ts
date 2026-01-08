import { ApiService } from '../services/api';
import { showToast } from '../utils/toast';

export class Contact {
    render(): string {
        return `
            <section id="contact">
                <h2 class="section-title">Initiate Nexus</h2>
                <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
                    <form id="contact-form">
                        <div class="grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Identifier</label>
                                <input type="text" name="nombre" class="input" placeholder="Name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Signal Origin</label>
                                <input type="email" name="email" class="input" placeholder="Email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message Payload</label>
                            <textarea name="mensaje" class="input" rows="5" placeholder="Your message..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data Attachment</label>
                            <input type="file" name="adjunto" class="input" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.doc,.docx,.odt,.md,.rar,.zip">
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Broadcast Signal</button>
                    </form>
                </div>
            </section>
        `;
    }

    attachEvents() {
        const form = document.getElementById('contact-form') as HTMLFormElement;
        if (!form) return;

        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const response = await ApiService.contact(formData);
                if (response.ok) {
                    showToast('Nexus transmission confirmed');
                    form.reset();
                } else {
                    const err = await response.json();
                    showToast(err.error || 'Transmission failed', 'error');
                }
            } catch (err) {
                showToast('Link failure', 'error');
            }
        };
    }
}
