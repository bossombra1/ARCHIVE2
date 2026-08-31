import React, { useState } from 'react';
import api from '../../services/api'; // <-- Remontez de deux niveaux
import { languageService } from '../../services/languageService';

export default function SetupView({ onSetupComplete }) {
  const [form, setForm] = useState({
    company_name: '',
    company_size: 'small',
    admin_name: '',
    admin_email: '',
    admin_password: '',
  });
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await api.post('/setup/store', form);
      alert('Configuration réussie ! Redirection vers la connexion.');
      onSetupComplete();
    } catch (err) {
      setError(err.response?.data?.message || 'Une erreur est survenue.');
    }
  };

  return (
    <div className="container mt-5" style={{ maxWidth: '600px' }}>
      <div className="card shadow p-4">
        <h2 className="text-center mb-4 text-primary">Configuration Initiale de la GED</h2>
        {error && <div className="alert alert-danger">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label">Nom de l'entreprise</label>
            <input type="text" className="form-control" required 
              value={form.company_name} onChange={e => setForm({...form, company_name: e.target.value})} />
          </div>
          <div className="mb-3">
            <label className="form-label">Taille de l'entreprise</label>
            <select className="form-select" value={form.company_size} onChange={e => setForm({...form, company_size: e.target.value})}>
              <option value="small">Petite entreprise</option>
              <option value="large">Grande entreprise</option>
            </select>
          </div>
          <hr />
          <h5 className="mb-3 text-secondary">Compte Administrateur</h5>
          <div className="mb-3">
            <label className="form-label">Nom de l'administrateur</label>
            <input type="text" className="form-control" required 
              value={form.admin_name} onChange={e => setForm({...form, admin_name: e.target.value})} />
          </div>
          <div className="mb-3">
            <label className="form-label">Email Admin</label>
            <input type="email" className="form-control" required 
              value={form.admin_email} onChange={e => setForm({...form, admin_email: e.target.value})} />
          </div>
          <div className="mb-3">
            <label className="form-label">Mot de passe</label>
            <input type="password" className="form-control" required 
              value={form.admin_password} onChange={e => setForm({...form, admin_password: e.target.value})} />
          </div>
          <button type="submit" className="btn btn-primary w-100">Enregistrer et initialiser</button>
        </form>
      </div>
    </div>
  );
}