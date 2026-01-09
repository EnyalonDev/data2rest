import { useEffect, useState } from 'react';
import type { WebPage } from '../types';
import apiClient from '../api';
import { Target, Shield, Zap } from 'lucide-react';

const About = () => {
    const [pageData, setPageData] = useState<WebPage | null>(null);

    useEffect(() => {
        apiClient.get('/web_pages', { params: { slug: 'about-us' } })
            .then(res => {
                if (res.data.data && res.data.data.length > 0) {
                    setPageData(res.data.data[0]);
                }
            });
    }, []);

    if (!pageData) return null;

    return (
        <section id="about" className="section-padding">
            <div className="container">
                <div className="about-grid">
                    <div className="about-image-container animate-fade">
                        <img
                            src={pageData.featured_image || 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&q=80&w=800'}
                            alt="Sobre Nosotros"
                            className="about-image"
                        />
                        <div className="experience-badge glass-card">
                            <span className="exp-num">10+</span>
                            <span className="exp-text">Años de Innovación</span>
                        </div>
                    </div>

                    <div className="about-content animate-fade" style={{ animationDelay: '0.2s' }}>
                        <h2 className="section-title" style={{ textAlign: 'left', marginBottom: '1.5rem' }}>
                            {pageData.title}
                        </h2>
                        <p className="about-text">
                            {pageData.content}
                        </p>

                        <div className="values-grid">
                            <div className="value-item">
                                <div className="value-icon-wrapper">
                                    <Target size={24} />
                                </div>
                                <div className="value-info">
                                    <h4>Misión</h4>
                                    <p>Simplificar datos complejos mediante interfaces elegantes.</p>
                                </div>
                            </div>
                            <div className="value-item">
                                <div className="value-icon-wrapper">
                                    <Shield size={24} />
                                </div>
                                <div className="value-info">
                                    <h4>Integridad</h4>
                                    <p>Seguridad y transparencia en cada línea de código.</p>
                                </div>
                            </div>
                            <div className="value-item">
                                <div className="value-icon-wrapper">
                                    <Zap size={24} />
                                </div>
                                <div className="value-info">
                                    <h4>Velocidad</h4>
                                    <p>Automatización que libera la creatividad humana.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default About;
