import { useEffect, useState } from 'react';
import type { Project } from '../types';
import apiClient from '../api';
import { Plus, DollarSign, Calendar } from 'lucide-react';

const Projects = () => {
    const [projects, setProjects] = useState<Project[]>([]);
    const [showModal, setShowModal] = useState(false);
    const [newProject, setNewProject] = useState({ title: '', description: '', budget: 0 });

    useEffect(() => {
        apiClient.get('/projects', { params: { limit: 6 } })
            .then(res => setProjects(res.data.data));
    }, []);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        apiClient.post('/projects', newProject)
            .then(res => {
                setProjects([res.data.data, ...projects]);
                setShowModal(false);
                setNewProject({ title: '', description: '', budget: 0 });
            });
    };

    return (
        <section id="projects" className="section-padding bg-alt">
            <div className="container">
                <div className="section-header">
                    <h2 className="section-title">Proyectos Destacados</h2>
                    <button onClick={() => setShowModal(true)} className="btn btn-primary">
                        Nueva Idea <Plus size={18} />
                    </button>
                </div>

                <div className="grid">
                    {projects.map(project => (
                        <div key={project.id} className="glass-card project-card">
                            <div className="project-status" data-status={project.status}>
                                {project.status === 1 ? 'Activo' : 'Planeado'}
                            </div>
                            <h3>{project.title}</h3>
                            <p className="project-desc">{project.description}</p>
                            <div className="project-meta">
                                <span><DollarSign size={14} /> {project.budget.toLocaleString()}</span>
                                <span><Calendar size={14} /> {project.start_date}</span>
                            </div>
                        </div>
                    ))}
                </div>

                {showModal && (
                    <div className="modal-overlay">
                        <div className="modal-content glass-card">
                            <h3>Propón un Nuevo Proyecto</h3>
                            <form onSubmit={handleSubmit} className="project-form">
                                <input
                                    placeholder="Título del proyecto"
                                    value={newProject.title}
                                    onChange={e => setNewProject({ ...newProject, title: e.target.value })}
                                    required
                                />
                                <textarea
                                    placeholder="Descripción detallada"
                                    value={newProject.description}
                                    onChange={e => setNewProject({ ...newProject, description: e.target.value })}
                                    required
                                />
                                <input
                                    type="number"
                                    placeholder="Presupuesto estimado"
                                    value={newProject.budget}
                                    onChange={e => setNewProject({ ...newProject, budget: Number(e.target.value) })}
                                    required
                                />
                                <div className="form-actions">
                                    <button type="submit" className="btn btn-primary">Enviar Propuesta</button>
                                    <button type="button" onClick={() => setShowModal(false)} className="btn">Cerrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
};

export default Projects;
