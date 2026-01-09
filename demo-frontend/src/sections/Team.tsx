import { useEffect, useState } from 'react';
import type { Employee } from '../types';
import apiClient from '../api';
import { Mail, User } from 'lucide-react';

const Team = () => {
    const [team, setTeam] = useState<Employee[]>([]);

    useEffect(() => {
        apiClient.get('/employees')
            .then(res => setTeam(res.data.data));
    }, []);

    return (
        <section id="team" className="section-padding">
            <div className="container">
                <h2 className="section-title">Nuestros Expertos</h2>
                <div className="grid">
                    {team.map(member => (
                        <div key={member.id} className="glass-card team-card">
                            <div className="avatar-wrapper">
                                {member.avatar ? (
                                    <img src={member.avatar} alt={member.full_name} className="avatar" />
                                ) : (
                                    <div className="avatar-placeholder"><User size={40} /></div>
                                )}
                            </div>
                            <div className="team-info">
                                <h3>{member.full_name}</h3>
                                <span className="role-badge">{member.role}</span>
                                <p className="dept-tag">{member.department_id_label || 'Engineering'}</p>
                                <a href={`mailto:${member.email}`} className="email-link">
                                    <Mail size={16} /> {member.email}
                                </a>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
};

export default Team;
