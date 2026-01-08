import { ApiService } from '../services/api';
import { WebPage } from '../types';

export class Hero {
    private data: WebPage | null = null;

    async render(): Promise<string> {
        this.data = await ApiService.getHero();
        return `
            <section class="hero animate-fade-in">
                <h1>${this.data.title}</h1>
                <p>${this.data.content}</p>
                <div class="flex gap-4">
                    <button class="btn" onclick="document.getElementById('about').scrollIntoView({behavior: 'smooth'})">Learn More</button>
                    <button class="btn" style="background: transparent; border: 1px solid var(--primary); color: var(--primary);" onclick="document.getElementById('contact').scrollIntoView({behavior: 'smooth'})">Contact Us</button>
                </div>
            </section>
        `;
    }
}
