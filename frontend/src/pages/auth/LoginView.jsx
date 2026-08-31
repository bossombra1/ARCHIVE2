import React, { useState } from 'react';
import api from '../../services/api'; // <-- Remontez de deux niveaux
import { languageService } from '../../services/languageService';

export default function LoginView({ onLoginSuccess }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [inactiveMessage, setInactiveMessage] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    setInactiveMessage(false);

    try {
      const response = await api.post('/login', { email, password });
      localStorage.setItem('auth_token', response.data.access_token);
      onLoginSuccess(response.data.user);
    } catch (err) {
      if (err.response?.status === 403 && err.response?.data?.error === 'ACCOUNT_INACTIVE') {
        setInactiveMessage(true);
      } else {
        setError(err.response?.data?.message || 'Identifiants invalides.');
      }
    }
  };

  return (
    <div className="container mt-5" style={{ maxWidth: '450px' }}>
      <div className="card shadow p-4">
        <h2 className="text-center mb-4">Connexion GED</h2>
        
        {error && <div className="alert alert-danger">{error}</div>}
        
        {inactiveMessage && (
          <div className="alert alert-warning">
            <strong>Compte inactif :</strong> Votre compte est désactivé. Veuillez voir l'administrateur.
          </div>
        )}

        <form onSubmit={handleLogin}>
          <div className="mb-3">
            <label className="form-label">Adresse Email</label>
            <input type="email" className="form-control" required 
              value={email} onChange={e => setEmail(e.target.value)} />
          </div>
          <div className="mb-3">
            <label className="form-label">Mot de passe</label>
            <input type="password" className="form-control" required 
              value={password} onChange={e => setPassword(e.target.value)} />
          </div>
          <button type="submit" className="btn btn-primary w-100">Se connecter</button>
        </form>
      </div>
    </div>
  );
}