import { useEffect, useState } from 'react';
import Hero from '../sections/Hero';
import About from '../sections/About';
import Services from '../sections/Services';
import Projects from '../sections/Projects';
import Team from '../sections/Team';
import Contact from '../sections/Contact';
import apiClient from '../api';
import type { CompanyProfile } from '../types';

function Landing() {
    const [company, setCompany] = useState<CompanyProfile | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiClient.get('/company_profile')
            .then(res => {
                if (res.data.data && res.data.data.length > 0) {
                    setCompany(res.data.data[0]);
                }
            })
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="loading">Cargando Experiencia...</div>;

    return (
        <div className="landing">
            <nav className="glass-nav">
                <div className="container nav-content">
                    <div className="logo">{company?.name || 'Data2Rest Demo'}</div>
                    <div className="nav-links">
                        <a href="#about">Nosotros</a>
                        <a href="#services">Servicios</a>
                        <a href="#projects">Proyectos</a>
                        <a href="#team">Equipo</a>
                        <a href="#contact" className="btn btn-primary">Contacto</a>
                    </div>
                </div>
            </nav>

            <main>
                <Hero company={company} showEdit={false} />
                <About />
                <Services />
                <Projects />
                <Team />
                <Contact />
            </main>

            <footer className="footer">
                <div className="container">
                    <p>&copy; 2026 {company?.name}. Powered by Data2Rest.</p>
                </div>
            </footer>
        </div>
    );
}

export default Landing;
