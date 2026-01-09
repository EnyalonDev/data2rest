import { useEffect, useState } from 'react';
import type { CompanyProfile, WebPage } from '../types';
import apiClient, { uploadFile } from '../api';
import { Rocket, Edit3, Image as ImageIcon, Check } from 'lucide-react';

interface HeroProps {
    company: CompanyProfile | null;
    showEdit?: boolean;
}

const Hero: React.FC<HeroProps> = ({ company, showEdit = true }) => {
    const [pageData, setPageData] = useState<WebPage | null>(null);
    const [isEditing, setIsEditing] = useState(false);
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        apiClient.get('/web_pages', { params: { slug: 'home-hero' } })
            .then(res => {
                if (res.data.data && res.data.data.length > 0) {
                    const page = res.data.data[0];
                    setPageData(page);
                    setTitle(page.title);
                    setContent(page.content);
                }
            });
    }, []);

    const handleUpdate = async () => {
        if (!pageData) return;
        setIsSaving(true);
        try {
            const formData = new FormData();
            formData.append('title', title);
            formData.append('content', content);
            if (imageFile) {
                formData.append('featured_image', imageFile);
            }

            await uploadFile('web_pages', pageData.id.toString(), formData);

            // Re-fetch to get updated image URL from server
            const updatedRes = await apiClient.get(`/web_pages/${pageData.id}`);
            setPageData(updatedRes.data.data);
            setIsEditing(false);
            setImageFile(null);
        } catch (error) {
            console.error("Error updating hero:", error);
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <section className="hero">
            <div className="container">
                <div className="hero-grid">
                    <div className="hero-content animate-fade">
                        {isEditing ? (
                            <div className="edit-form glass-card">
                                <label className="edit-label">Título</label>
                                <input
                                    value={title}
                                    onChange={e => setTitle(e.target.value)}
                                    className="edit-input-field"
                                />
                                <label className="edit-label">Descripción</label>
                                <textarea
                                    value={content}
                                    onChange={e => setContent(e.target.value)}
                                    className="edit-textarea-field"
                                />

                                <label className="edit-label">Imagen Destacada</label>
                                <div className="image-upload-wrapper">
                                    <label className="image-upload-btn">
                                        <ImageIcon size={18} /> {imageFile ? imageFile.name : 'Cambiar Imagen'}
                                        <input
                                            type="file"
                                            className="hidden-input"
                                            onChange={e => setImageFile(e.target.files?.[0] || null)}
                                            accept="image/*"
                                        />
                                    </label>
                                    {imageFile && <span className="upload-ready"><Check size={14} /> Listo</span>}
                                </div>

                                <div className="edit-actions">
                                    <button
                                        onClick={handleUpdate}
                                        className={`btn btn-primary ${isSaving ? 'loading' : ''}`}
                                        disabled={isSaving}
                                    >
                                        {isSaving ? 'Guardando...' : 'Guardar Cambios'}
                                    </button>
                                    <button onClick={() => setIsEditing(false)} className="btn">Cancelar</button>
                                </div>
                            </div>
                        ) : (
                            <>
                                <h1 className="hero-title">{pageData?.title || 'Innovación Inteligente'}</h1>
                                <p className="hero-subtitle">{pageData?.content || company?.brand_message}</p>
                                <div className="hero-actions">
                                    <button className="btn btn-primary">Empieza Ahora <Rocket size={20} /></button>
                                    {showEdit && (
                                        <button onClick={() => setIsEditing(true)} className="btn btn-outline" style={{ borderColor: 'var(--glass-border)' }}>
                                            Editar Demo <Edit3 size={18} />
                                        </button>
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                    <div className="hero-image-container animate-fade" style={{ animationDelay: '0.2s' }}>
                        <div className="hero-image-wrapper">
                            <img
                                src={pageData?.featured_image || 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&q=80&w=1000'}
                                alt="Hero"
                                className="hero-image"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Hero;
