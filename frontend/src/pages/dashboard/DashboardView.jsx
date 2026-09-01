import React, { useState, useEffect, useCallback } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { dashboardService } from '../../services/dashboardService';
import { documentService } from '../../services/documentService';
import { useApp } from '../../context/AppContext';

/**
 * Dashboard moderne :
 *   - En-tête personnalisé avec rôle et entreprise
 *   - Cartes de statistiques compactes
 *   - Documents récents (5 derniers)
 *   - Graphique de répartition par type (CSS progress bars)
 *   - Rafraîchissement automatique à chaque navigation
 */
export default function DashboardView() {
  const { themeColor, t, user } = useApp();
  const navigate = useNavigate();

  const [stats, setStats] = useState({
    total_documents: 0, total_document_types: 0, total_services: 0, total_users: 0,
    documents_by_type: []
  });
  const [recentDocs, setRecentDocs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      const [statsData, docsData] = await Promise.all([
        dashboardService.getStats(),
        documentService.getAll({ per_page: 5 }),
      ]);
      setStats(statsData);
      setRecentDocs(docsData.data || []);
    } catch (err) {
      setError('Impossible de charger les données. ' + (err?.response?.data?.message || ''));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleDownload = async (doc) => {
    try {
      const safeName = (doc.title || 'document').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '.' + (doc.file_type || 'bin');
      await documentService.download(doc.id, safeName);
    } catch (err) {
      alert(err?.response?.data?.message || 'Téléchargement refusé.');
    }
  };

  const getFileIcon = (fileType) => {
    if (fileType === 'pdf') return 'bi-file-earmark-pdf text-danger';
    if (['png', 'jpg', 'jpeg', 'gif'].includes(fileType)) return 'bi-file-earmark-image text-info';
    return 'bi-file-earmark text-secondary';
  };

  const formatSize = (bytes) => {
    if (!bytes) return '—';
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} Mo`;
  };

  const maxCount = Math.max(...(stats.documents_by_type?.map((i) => i.count) || [1]), 1);

  if (loading) {
    return (
      <div className="container-fluid py-5 text-center">
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Chargement...</span>
        </div>
        <p className="text-muted mt-2">Chargement des données...</p>
      </div>
    );
  }

  return (
    <div className="container-fluid py-4">
      {/* En-tête */}
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 className="fw-bold mb-1">
            Bonjour, {user?.name?.split(' ')[0] || 'utilisateur'} 
          </h2>
          <p className="text-muted mb-0">
            <span className="badge me-2" style={{ backgroundColor: themeColor }}>
              {user?.affectation?.poste?.name || 'Utilisateur'}
            </span>
            <small>{user?.company?.name}</small>
          </p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-light border" onClick={fetchData} title="Rafraîchir">
            <i className="bi bi-arrow-clockwise"></i>
          </button>
          <button className="btn btn-primary" onClick={() => navigate('/documents')}>
            <i className="bi bi-folder2-open me-1"></i> Gérer les documents
          </button>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Cartes de statistiques compactes */}
      <div className="row g-3 mb-4">
        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-body d-flex align-items-center">
              <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#e3f2fd' }}>
                <i className="bi bi-file-earmark-text fs-4" style={{ color: '#1976d2' }}></i>
              </div>
              <div>
                <div className="text-muted small text-uppercase">Documents</div>
                <div className="fs-4 fw-bold">{stats.total_documents}</div>
              </div>
            </div>
          </div>
        </div>
        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-body d-flex align-items-center">
              <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#e8f5e9' }}>
                <i className="bi bi-file-earmark fs-4" style={{ color: '#2e7d32' }}></i>
              </div>
              <div>
                <div className="text-muted small text-uppercase">Types</div>
                <div className="fs-4 fw-bold">{stats.total_document_types}</div>
              </div>
            </div>
          </div>
        </div>
        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-body d-flex align-items-center">
              <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#fff3e0' }}>
                <i className="bi bi-building fs-4" style={{ color: '#e65100' }}></i>
              </div>
              <div>
                <div className="text-muted small text-uppercase">Services</div>
                <div className="fs-4 fw-bold">{stats.total_services}</div>
              </div>
            </div>
          </div>
        </div>
        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-body d-flex align-items-center">
              <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#f3e5f5' }}>
                <i className="bi bi-people fs-4" style={{ color: '#7b1fa2' }}></i>
              </div>
              <div>
                <div className="text-muted small text-uppercase">Utilisateurs</div>
                <div className="fs-4 fw-bold">{stats.total_users}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Contenu principal */}
      <div className="row g-4">
        {/* Documents récents */}
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-header bg-white d-flex justify-content-between align-items-center py-3">
              <h5 className="mb-0 fw-bold">
                <i className="bi bi-clock-history me-2"></i>Documents récents
              </h5>
              <NavLink to="/documents" className="text-decoration-none small fw-semibold">
                Voir tout <i className="bi bi-arrow-right ms-1"></i>
              </NavLink>
            </div>
            <div className="card-body p-0">
              {recentDocs.length === 0 ? (
                <div className="text-center py-5">
                  <i className="bi bi-inbox display-4 text-muted"></i>
                  <p className="text-muted mt-3 mb-0">Aucun document pour le moment.</p>
                  <button className="btn btn-primary mt-3" onClick={() => navigate('/documents')}>
                    <i className="bi bi-plus-lg me-1"></i> Ajouter un document
                  </button>
                </div>
              ) : (
                <div className="list-group list-group-flush">
                  {recentDocs.map((doc) => (
                    <div key={doc.id} className="list-group-item d-flex align-items-center py-3">
                      <i className={`bi ${getFileIcon(doc.file_type)} me-3 fs-4`}></i>
                      <div className="flex-grow-1 min-w-0">
                        <div className="fw-semibold text-truncate">{doc.title}</div>
                        <small className="text-muted">
                          <span className="badge bg-light text-dark me-1">{doc.document_type?.name}</span>
                          {doc.service?.name} • {formatSize(doc.file_size)} • {doc.created_at?.substring(0, 10)}
                        </small>
                      </div>
                      <button className="btn btn-sm btn-outline-primary ms-2" onClick={() => handleDownload(doc)} title="Télécharger">
                        <i className="bi bi-download"></i>
                      </button>
                      <button className="btn btn-sm btn-outline-secondary ms-1" onClick={() => navigate('/documents')} title="Voir">
                        <i className="bi bi-eye"></i>
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Répartition par type */}
        <div className="col-lg-4">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-header bg-white py-3">
              <h5 className="mb-0 fw-bold">
                <i className="bi bi-bar-chart-line me-2"></i>Par type
              </h5>
            </div>
            <div className="card-body">
              {!stats.documents_by_type || stats.documents_by_type.length === 0 ? (
                <p className="text-muted text-center py-4 mb-0">Aucune donnée disponible.</p>
              ) : (
                stats.documents_by_type.map((item, i) => {
                  const pct = maxCount > 0 ? (item.count / maxCount) * 100 : 0;
                  return (
                    <div key={i} className="mb-3">
                      <div className="d-flex justify-content-between mb-1">
                        <span className="small fw-semibold">{item.name}</span>
                        <span className="small text-muted">{item.count}</span>
                      </div>
                      <div className="progress" style={{ height: '6px', borderRadius: '3px' }}>
                        <div className="progress-bar" style={{ width: `${pct}%`, backgroundColor: themeColor, borderRadius: '3px' }} />
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Accès rapide */}
      <div className="row g-3 mt-2">
        <div className="col-6 col-md-3">
          <NavLink to="/documents" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100 text-center py-3 quick-action">
              <i className="bi bi-folder2-open fs-2" style={{ color: themeColor }}></i>
              <div className="small fw-semibold mt-2 text-dark">Documents</div>
            </div>
          </NavLink>
        </div>
        <div className="col-6 col-md-3">
          <NavLink to="/document-types" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100 text-center py-3 quick-action">
              <i className="bi bi-tags fs-2" style={{ color: themeColor }}></i>
              <div className="small fw-semibold mt-2 text-dark">Types</div>
            </div>
          </NavLink>
        </div>
        <div className="col-6 col-md-3">
          <NavLink to="/services" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100 text-center py-3 quick-action">
              <i className="bi bi-building fs-2" style={{ color: themeColor }}></i>
              <div className="small fw-semibold mt-2 text-dark">Services</div>
            </div>
          </NavLink>
        </div>
        <div className="col-6 col-md-3">
          <NavLink to="/users" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100 text-center py-3 quick-action">
              <i className="bi bi-people fs-2" style={{ color: themeColor }}></i>
              <div className="small fw-semibold mt-2 text-dark">Utilisateurs</div>
            </div>
          </NavLink>
        </div>
      </div>
    </div>
  );
}