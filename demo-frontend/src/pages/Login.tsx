import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Lock, User, ArrowRight } from 'lucide-react';

const Login = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const navigate = useNavigate();

    const handleLogin = (e: React.FormEvent) => {
        e.preventDefault();
        // Accept any data as requested
        localStorage.setItem('demo_auth', 'true');
        navigate('/admin');
    };

    return (
        <div className="login-page">
            <div className="container flex-center">
                <form onSubmit={handleLogin} className="glass-card login-form animate-fade">
                    <div className="login-header">
                        <div className="lock-icon"><Lock size={32} /></div>
                        <h2>Acceso Demo Admin</h2>
                        <p>Ingresa cualquier dato para continuar</p>
                    </div>

                    <div className="input-group">
                        <label><User size={16} /> Usuario</label>
                        <input
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            placeholder="admin"
                            required
                        />
                    </div>

                    <div className="input-group">
                        <label><Lock size={16} /> Contraseña</label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                    </div>

                    <button type="submit" className="btn btn-primary btn-block">
                        Entrar al Panel <ArrowRight size={18} />
                    </button>
                </form>
            </div>
        </div>
    );
};

export default Login;
