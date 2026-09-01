import React, { useState, useEffect, useCallback } from 'react';
import { adminService } from '../../services/adminService';

export default function JournalsView() {
  const [journals, setJournals] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [filters, setFilters] = useState({ search: '', page: 1, per_page: 25 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetch = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await adminService.getJournals(filters);
      setJournals(data.data || []);
      setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total });
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => { fetch(); }, [fetch]);

  const getActionBadge = (action) => {
    if (action?.includes('LOGIN') || action?.includes('LOGOUT')) return 'bg-info';
    if (action?.includes('CREATE')) return 'bg-success';
    if (action?.includes('DELETE') || action?.includes('DEACTIVATE')) return 'bg-danger';
    if (action?.includes('UPDATE')) return 'bg-warning text-dark';
    if (action?.includes('FAILED') || action?.includes('BLOCKED') || action?.includes('AMBIGUOUS')) return 'bg-danger';
    return 'bg-secondary';
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Journaux d'audit</h2>
          <p className="text-muted mb-0">{meta.total} entrée(s)</p>
        </div>
        <button className="btn btn-outline-secondary" onClick={fetch}>
          <i className="bi bi-arrow-clockwise me-1"></i> Rafraîchir
        </button>
      </div>

      <div className="card mb-3 border-0 shadow-sm">
        <div className="card-body py-3">
          <div className="row g-2 align-items-end">
            <div className="col-md-9">
              <label className="form-label small text-muted mb-1">Rechercher (action ou description)</label>
              <input type="text" className="form-control" value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
                placeholder="Ex : LOGIN, DOCUMENT_CREATE, USER_UPDATE…" />
            </div>
            <div className="col-md-3">
              <button className="btn btn-outline-secondary w-100"
                onClick={() => setFilters({ search: '', page: 1, per_page: 25 })}>
                <i className="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
              </button>
            </div>
          </div>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>
      ) : journals.length === 0 ? (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5">
            <i className="bi bi-journal-text display-4 text-muted"></i>
            <p className="text-muted mt-3 mb-0">Aucune entrée dans le journal.</p>
          </div>
        </div>
      ) : (
        <div className="card border-0 shadow-sm">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '60px' }}>#</th>
                    <th style={{ width: '180px' }}>Date</th>
                    <th style={{ width: '180px' }}>Action</th>
                    <th>Description</th>
                    <th style={{ width: '180px' }}>Utilisateur</th>
                    <th style={{ width: '120px' }}>IP</th>
                  </tr>
                </thead>
                <tbody>
                  {journals.map((j) => (
                    <tr key={j.id}>
                      <td className="text-muted">{j.id}</td>
                      <td className="small">{formatDate(j.created_at)}</td>
                      <td><span className={`badge ${getActionBadge(j.action)}`}>{j.action}</span></td>
                      <td className="small">{j.description}</td>
                      <td className="small">
                        {j.user ? (
                          <span>{j.user.name}<br /><span className="text-muted">{j.user.email}</span></span>
                        ) : (
                          <span className="text-muted fst-italic">Anonyme</span>
                        )}
                      </td>
                      <td className="small text-muted">{j.ip_address || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          {meta.last_page > 1 && (
            <div className="card-footer bg-white">
              <nav>
                <ul className="pagination justify-content-center mb-0">
                  <li className={`page-item ${meta.current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => setFilters({ ...filters, page: meta.current_page - 1 })}>«</button>
                  </li>
                  <li className="page-item disabled">
                    <span className="page-link">{meta.current_page} / {meta.last_page}</span>
                  </li>
                  <li className={`page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => setFilters({ ...filters, page: meta.current_page + 1 })}>»</button>
                  </li>
                </ul>
              </nav>
            </div>
          )}
        </div>
      )}
    </div>
  );
}