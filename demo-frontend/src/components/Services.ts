import { ApiService } from '../services/api.js';
import type { Service } from '../types.js';

export class Services {
    async render(): Promise<string> {
        const services = await ApiService.getServices();
        return `
            <section id="services">
                <h2 class="section-title">Technological Capabilities</h2>
                <div class="grid">
                    ${services.map(s => `
                        <div class="glass-card">
                            <div style="width: 50px; height: 50px; background: rgba(56, 189, 248, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--primary);">
                                <i data-lucide="${this.getIcon(s.icon || (s as any).icon_name)}"></i>
                            </div>
                            <h3 style="margin-bottom: 1rem;">${s.title || (s as any).nombre}</h3>
                            <p style="color: var(--text-muted); font-size: 0.95rem;">${s.description || (s as any).descripcion}</p>
                        </div>
                    `).join('')}
                </div>
            </section>
        `;
    }

    private getIcon(name: string): string {
        const map: Record<string, string> = {
            'web': 'globe',
            'mobile': 'smartphone',
            'rocket': 'rocket',
            'code': 'code',
            'cloud-server': 'cloud',
            'api': 'braces',
            'chart-bar': 'bar-chart-3'
        };
        return map[name] || name || 'sparkles';
    }
}
