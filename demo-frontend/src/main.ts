import { Hero } from './components/Hero';
import { About } from './components/About';
import { Services } from './components/Services';
import { Contact } from './components/Contact';

class App {
    private root = document.getElementById('app');

    // Instantiate components
    private hero = new Hero();
    private about = new About();
    private services = new Services();
    private contact = new Contact();

    async init() {
        if (!this.root) return;

        // Render structure
        this.root.innerHTML = `
            <div class="bg-gradient"></div>
            <header>
                <div class="logo">DATA2REST</div>
                <nav>
                    <ul>
                        <li><a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
            </header>
            <main>
                <div id="hero-slot"></div>
                <div id="about-slot"></div>
                <div id="services-slot"></div>
                <div id="contact-slot"></div>
            </main>
            <footer style="text-align: center; padding: 4rem; color: var(--text-muted); font-size: 0.8rem; border-top: 1px solid var(--glass-border);">
                <p>© 2026 Data2Rest Ecosystem | All signals reserved.</p>
                <p style="margin-top: 1rem; font-size: 0.75rem;">
                    <a href="https://github.com/enyalondev/data2rest" target="_blank" rel="noopener noreferrer" 
                       style="color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: color 0.3s ease;"
                       onmouseover="this.style.color='#60a5fa'" 
                       onmouseout="this.style.color='var(--text-muted)'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        View on GitHub
                    </a>
                </p>
            </footer>
        `;

        // Load dynamic content
        const heroSlot = document.getElementById('hero-slot');
        const aboutSlot = document.getElementById('about-slot');
        const servicesSlot = document.getElementById('services-slot');
        const contactSlot = document.getElementById('contact-slot');

        if (heroSlot) heroSlot.innerHTML = await this.hero.render();
        if (aboutSlot) aboutSlot.innerHTML = await this.about.render();
        if (servicesSlot) servicesSlot.innerHTML = await this.services.render();
        if (contactSlot) contactSlot.innerHTML = this.contact.render();

        // Attach event listeners
        this.about.attachEvents();
        this.contact.attachEvents();

        // Init icons
        if ((window as any).lucide) {
            (window as any).lucide.createIcons();
        }
    }
}

const app = new App();
app.init();
