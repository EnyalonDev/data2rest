import React, { useState } from 'react';
import { uploadFile } from '../api';
import { Send, Paperclip, CheckCircle } from 'lucide-react';

const Contact = () => {
    const [formData, setFormData] = useState({
        nombre: '',
        email: '',
        asunto: '',
        mensaje: '',
    });
    const [file, setFile] = useState<File | null>(null);
    const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setStatus('loading');

        const data = new FormData();
        data.append('nombre', formData.nombre);
        data.append('email', formData.email);
        data.append('asunto', formData.asunto);
        data.append('mensaje', formData.mensaje);
        if (file) {
            data.append('adjuntos', file);
        }

        try {
            await uploadFile('mensajes_de_contacto', null, data);
            setStatus('success');
            setFormData({ nombre: '', email: '', asunto: '', mensaje: '' });
            setFile(null);
        } catch (err) {
            setStatus('error');
        }
    };

    return (
        <section id="contact" className="section-padding bg-alt">
            <div className="container">
                <div className="contact-grid">
                    <div className="contact-info animate-fade">
                        <h2 className="section-title" style={{ textAlign: 'left' }}>Hablemos de tu Proyecto</h2>
                        <p className="contact-text">
                            Estamos listos para llevar tus datos al siguiente nivel.
                            Completa el formulario y nuestro equipo se conectará contigo.
                        </p>
                        <div className="contact-features">
                            <div className="feat"><CheckCircle size={20} className="text-success" /> Respuesta en menos de 24h</div>
                            <div className="feat"><CheckCircle size={20} className="text-success" /> Consultoría técnica gratuita</div>
                            <div className="feat"><CheckCircle size={20} className="text-success" /> Soporte multi-región</div>
                        </div>
                    </div>

                    <div className="contact-form-container animate-fade" style={{ animationDelay: '0.2s' }}>
                        <form onSubmit={handleSubmit} className="glass-card contact-form">
                            {status === 'success' ? (
                                <div className="success-msg">
                                    <CheckCircle size={48} className="text-success" />
                                    <h3>¡Mensaje Enviado!</h3>
                                    <p>Nos pondremos en contacto pronto.</p>
                                    <button onClick={() => setStatus('idle')} className="btn btn-primary">Enviar otro</button>
                                </div>
                            ) : (
                                <>
                                    <div className="input-group">
                                        <label>Nombre Completo</label>
                                        <input
                                            value={formData.nombre}
                                            onChange={e => setFormData({ ...formData, nombre: e.target.value })}
                                            placeholder="Ej. John Doe"
                                            required
                                        />
                                    </div>
                                    <div className="input-group">
                                        <label>Email Corporativo</label>
                                        <input
                                            type="email"
                                            value={formData.email}
                                            onChange={e => setFormData({ ...formData, email: e.target.value })}
                                            placeholder="john@empresa.com"
                                            required
                                        />
                                    </div>
                                    <div className="input-group">
                                        <label>Asunto</label>
                                        <input
                                            value={formData.asunto}
                                            onChange={e => setFormData({ ...formData, asunto: e.target.value })}
                                            placeholder="¿En qué podemos ayudarte?"
                                            required
                                        />
                                    </div>
                                    <div className="input-group">
                                        <label>Mensaje</label>
                                        <textarea
                                            value={formData.mensaje}
                                            onChange={e => setFormData({ ...formData, mensaje: e.target.value })}
                                            placeholder="Cuéntanos más..."
                                            required
                                        />
                                    </div>
                                    <div className="input-group">
                                        <label className="file-label">
                                            <Paperclip size={18} />
                                            {file ? file.name : 'Adjuntar archivo (PDF, Imágenes...)'}
                                            <input
                                                type="file"
                                                onChange={e => setFile(e.target.files?.[0] || null)}
                                                style={{ display: 'none' }}
                                            />
                                        </label>
                                    </div>
                                    <button
                                        type="submit"
                                        className={`btn btn-primary btn-block ${status === 'loading' ? 'loading' : ''}`}
                                        disabled={status === 'loading'}
                                    >
                                        {status === 'loading' ? 'Enviando...' : 'Enviar Mensaje'} <Send size={18} />
                                    </button>
                                    {status === 'error' && <p className="error-text">Ocurrió un error. Revisa tu conexión.</p>}
                                </>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Contact;
