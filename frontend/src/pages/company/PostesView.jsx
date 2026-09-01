import React, { useState, useEffect, useCallback } from 'react';
import { adminService } from '../../services/adminService';

export default function PostesView() {
  const [postes, setPostes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetch = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await adminService.getPostes();
      setPostes(data);
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetch(); }, [fetch]);

  const getBadgeColor = (level) => {
    const colors = {
      admin: 'bg-danger',
      dg: 'bg-dark',
      directeur: 'bg-primary',
      responsable_departement: 'bg-info',
      chef_service: 'bg-success',
      employe: 'bg-secondary',
      agent_temporaire: 'bg-warning text-dark',
    };
    return colors[level] || 'bg-secondary';
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Postes / Rôles</h2>
          <p className="text-muted mb-0">{postes.length} poste(s) défini(s)</p>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <div className="alert alert-info">
        <i className="bi bi-info-circle me-2"></i>
        Les postes sont <strong>globaux</strong> à l'application (partagés entre toutes les entreprises).
        Ils ne peuvent pas être modifiés depuis cette interface. Pour ajouter ou supprimer un poste,
        contactez l'administrateur technique.
      </div>

      {loading ? (
        <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>
      ) : (
        <div className="card border-0 shadow-sm">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '70px' }}>#</th>
                    <th>Nom affiché</th>
                    <th>Code technique (level)</th>
                    <th style={{ width: '140px' }}>Utilisateurs</th>
                  </tr>
                </thead>
                <tbody>
                  {postes.map((p) => (
                    <tr key={p.id}>
                      <td className="text-muted">{p.id}</td>
                      <td className="fw-semibold">{p.name}</td>
                      <td>
                        <span className={`badge ${getBadgeColor(p.level)}`}>{p.level}</span>
                      </td>
                      <td><span className="badge bg-light text-dark">{p.affectations_count} user(s)</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}