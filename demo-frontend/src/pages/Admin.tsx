import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import type { WebPage, ContactMessage } from '../types';
import apiClient, { uploadFile } from '../api';
import {
    LayoutDashboard,
    MessageSquare,
    LogOut,
    Image as ImageIcon,
    Mail,
    Calendar,
    User
} from 'lucide-react';

const Admin = () => {
    const [activeTab, setActiveTab] = useState<'content' | 'messages'>('content');
    const [heroData, setHeroData] = useState<WebPage | null>(null);
    const [aboutData, setAboutData] = useState<WebPage | null>(null);
    const [messages, setMessages] = useState<ContactMessage[]>([]);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const navigate = useNavigate();

    // Hero Form State
    const [heroTitle, setHeroTitle] = useState('');
    const [heroContent, setHeroContent] = useState('');
    const [heroImage, setHeroImage] = useState<File | null>(null);

    // About Form State
    const [aboutTitle, setAboutTitle] = useState('');
    const [aboutContent, setAboutContent] = useState('');
    const [aboutImage, setAboutImage] = useState<File | null>(null);

    useEffect(() => {
        if (!localStorage.getItem('demo_auth')) {
            navigate('/login');
            return;
        }

        const fetchData = async () => {
            try {
                const [heroRes, aboutRes, msgRes] = await Promise.all([
                    apiClient.get('/web_pages', { params: { slug: 'home-hero' } }),
                    apiClient.get('/web_pages', { params: { slug: 'about-us' } }),
                    apiClient.get('/mensajes_de_contacto', { params: { order_by: 'id', order: 'desc' } })
                ]);

                if (heroRes.data.data?.[0]) {
                    setHeroData(heroRes.data.data[0]);
                    setHeroTitle(heroRes.data.data[0].title);
                    setHeroContent(heroRes.data.data[0].content);
                }
                if (aboutRes.data.data?.[0]) {
                    setAboutData(aboutRes.data.data[0]);
                    setAboutTitle(aboutRes.data.data[0].title);
                    setAboutContent(aboutRes.data.data[0].content);
                }
                setMessages(msgRes.data.data || []);
            } catch (err) {
                console.error(err);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [navigate]);

    const handleUpdateHero = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!heroData) return;
        setIsSaving(true);
        try {
            const formData = new FormData();
            formData.append('title', heroTitle);
            formData.append('content', heroContent);
            if (heroImage) formData.append('featured_image', heroImage);
            await uploadFile('web_pages', heroData.id.toString(), formData);
            alert('Hero actualizado');
        } finally {
            setIsSaving(false);
        }
    };

    const handleUpdateAbout = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!aboutData) return;
        setIsSaving(true);
        try {
            const formData = new FormData();
            formData.append('title', aboutTitle);
            formData.append('content', aboutContent);
            if (aboutImage) formData.append('featured_image', aboutImage);
            await uploadFile('web_pages', aboutData.id.toString(), formData);
            alert('About Us actualizado');
        } finally {
            setIsSaving(false);
        }
    };

    const logout = () => {
        localStorage.removeItem('demo_auth');
        navigate('/');
    };

    if (loading) return <div className="loading">Cargando Panel...</div>;

    return (
        <div className="admin-layout">
            <aside className="admin-sidebar glass-card">
                <div className="admin-logo">Admin Panel</div>
                <nav className="admin-nav">
                    <button
                        className={`admin-nav-item ${activeTab === 'content' ? 'active' : ''}`}
                        onClick={() => setActiveTab('content')}
                    >
                        <LayoutDashboard size={20} /> Editar Contenido
                    </button>
                    <button
                        className={`admin-nav-item ${activeTab === 'messages' ? 'active' : ''}`}
                        onClick={() => setActiveTab('messages')}
                    >
                        <MessageSquare size={20} /> Mensajes ({messages.length})
                    </button>
                    <div className="nav-spacer"></div>
                    <button className="admin-nav-item logout" onClick={logout}>
                        <LogOut size={20} /> Salir
                    </button>
                </nav>
            </aside>

            <main className="admin-main">
                {activeTab === 'content' ? (
                    <div className="admin-content animate-fade">
                        <h2 className="section-title" style={{ textAlign: 'left' }}>Gestión de Contenido</h2>

                        <div className="admin-grid">
                            <div className="glass-card admin-form-card">
                                <h3>Home Hero</h3>
                                <form onSubmit={handleUpdateHero}>
                                    <div className="input-group">
                                        <label>Título Principal</label>
                                        <input value={heroTitle} onChange={e => setHeroTitle(e.target.value)} />
                                    </div>
                                    <div className="input-group">
                                        <label>Contenido / Eslogan</label>
                                        <textarea value={heroContent} onChange={e => setHeroContent(e.target.value)} />
                                    </div>
                                    <div className="input-group">
                                        <label className="image-upload-btn">
                                            <ImageIcon size={18} /> {heroImage ? heroImage.name : 'Cambiar Imagen Hero'}
                                            <input type="file" className="hidden-input" onChange={e => setHeroImage(e.target.files?.[0] || null)} />
                                        </label>
                                    </div>
                                    <button type="submit" className="btn btn-primary" disabled={isSaving}>
                                        {isSaving ? 'Guardando...' : 'Actualizar Hero'}
                                    </button>
                                </form>
                            </div>

                            <div className="glass-card admin-form-card">
                                <h3>Sobre Nosotros</h3>
                                <form onSubmit={handleUpdateAbout}>
                                    <div className="input-group">
                                        <label>Título de Sección</label>
                                        <input value={aboutTitle} onChange={e => setAboutTitle(e.target.value)} />
                                    </div>
                                    <div className="input-group">
                                        <label>Texto Descriptivo</label>
                                        <textarea value={aboutContent} onChange={e => setAboutContent(e.target.value)} />
                                    </div>
                                    <div className="input-group">
                                        <label className="image-upload-btn">
                                            <ImageIcon size={18} /> {aboutImage ? aboutImage.name : 'Cambiar Imagen About'}
                                            <input type="file" className="hidden-input" onChange={e => setAboutImage(e.target.files?.[0] || null)} />
                                        </label>
                                    </div>
                                    <button type="submit" className="btn btn-primary" disabled={isSaving}>
                                        {isSaving ? 'Guardando...' : 'Actualizar About'}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="admin-messages animate-fade">
                        <h2 className="section-title" style={{ textAlign: 'left' }}>Mensajes de Contacto</h2>
                        <div className="messages-list">
                            {messages.length === 0 ? (
                                <p className="no-data">No hay mensajes aún.</p>
                            ) : (
                                messages.map(msg => (
                                    <div key={msg.id} className="glass-card message-card">
                                        <div className="message-header">
                                            <div className="user-info">
                                                <div className="user-avatar"><User size={20} /></div>
                                                <div>
                                                    <strong>{msg.nombre}</strong>
                                                    <span className="user-email"><Mail size={14} /> {msg.email}</span>
                                                </div>
                                            </div>
                                            <div className="msg-meta">
                                                <span className={`priority-badge ${msg.prioridad?.toLowerCase()}`}>{msg.prioridad}</span>
                                                <span className="msg-date"><Calendar size={14} /> ID: {msg.id}</span>
                                            </div>
                                        </div>
                                        <div className="message-body">
                                            <h4>{msg.asunto}</h4>
                                            <p>{msg.mensaje}</p>
                                        </div>
                                        {msg.adjuntos && (
                                            <div className="message-footer">
                                                <span className="attachment-tag">
                                                    <ImageIcon size={14} /> Adjunto: {msg.adjuntos.split('/').pop()}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
};

export default Admin;
