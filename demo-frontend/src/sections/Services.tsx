import { useEffect, useState } from 'react';
import type { Service } from '../types';
import apiClient from '../api';
import * as LucideIcons from 'lucide-react';

const Services = () => {
    const [services, setServices] = useState<Service[]>([]);

    useEffect(() => {
        apiClient.get('/servicios', { params: { order_by: 'order_num', order: 'asc' } })
            .then(res => setServices(res.data.data));
    }, []);

    const getIcon = (name: string) => {
        // Basic mapping for demo
        const IconComponent = (LucideIcons as any)[name] || LucideIcons.Terminal;
        return <IconComponent size={32} className="service-icon" />;
    };

    return (
        <section id="services" className="section-padding">
            <div className="container">
                <h2 className="section-title">Nuestros Servicios</h2>
                <div className="grid">
                    {services.map(service => (
                        <div key={service.id} className="glass-card service-card">
                            <div className="icon-wrapper">
                                {getIcon(service.icon)}
                            </div>
                            <h3>{service.title}</h3>
                            <p>{service.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
};

export default Services;
