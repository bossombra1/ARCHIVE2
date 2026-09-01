import React, { useState, useEffect, useCallback } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { dashboardService } from '../../services/dashboardService';
import { documentService } from '../../services/documentService';
import { useApp } from '../../context/AppContext';

export default function DashboardView() {
  const { themeColor, t, user } = useApp();
  const navigate = useNavigate();

  // On compare sur poste.level (la référence backend) au lieu de poste.name
  const isAdmin = user?.affectation?.poste?.level === 'admin';

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

  const addedThisWeek = recentDocs.filter((doc) => {
    if (!doc.created_at) return false;
    const created = new Date(doc.created_at);
    const now = new Date();
    const diffDays = (now - created) / (1000 * 60 * 60 * 24);
    return diffDays <= 7;
  }).length;

  // ---- Calculs pour le diagramme circulaire (donut) ----
  const totalDocs = stats.documents_by_type?.reduce((sum, item) => sum + item.count, 0) || 0;

  // Palette de couleurs cohérente pour le diagramme
  const chartColors = ['#1976d2', '#2e7d32', '#e65100', '#7b1fa2', '#00838f', '#ad1457', '#f9a825', '#5d4037', '#455a64', '#26a69a'];

  // Calcul des segments pour le donut SVG
  const segments = [];
  let cumulativePercent = 0;
  if (totalDocs > 0) {
    (stats.documents_by_type || []).forEach((item, i) => {
      if (item.count > 0) {
        const percent = (item.count / totalDocs) * 100;
        segments.push({
          name: item.name,
          count: item.count,
          percent,
          color: chartColors[i % chartColors.length],
          startPercent: cumulativePercent,
        });
        cumulativePercent += percent;
      }
    });
  }

  // Calcul des arcs SVG (path) pour le donut
  const donutPath = (startPercent, endPercent, radius = 80, innerRadius = 50) => {
    const angleStart = (startPercent / 100) * 2 * Math.PI - Math.PI / 2;
    const angleEnd = (endPercent / 100) * 2 * Math.PI - Math.PI / 2;
    const x1 = 100 + radius * Math.cos(angleStart);
    const y1 = 100 + radius * Math.sin(angleStart);
    const x2 = 100 + radius * Math.cos(angleEnd);
    const y2 = 100 + radius * Math.sin(angleEnd);
    const x3 = 100 + innerRadius * Math.cos(angleEnd);
    const y3 = 100 + innerRadius * Math.sin(angleEnd);
    const x4 = 100 + innerRadius * Math.cos(angleStart);
    const y4 = 100 + innerRadius * Math.sin(angleStart);
    const largeArc = endPercent - startPercent > 50 ? 1 : 0;
    return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4} Z`;
  };

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

        {isAdmin ? (
          <>
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
          </>
        ) : (
          <>
            <div className="col-6 col-lg-3">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-body d-flex align-items-center">
                  <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#e0f7fa' }}>
                    <i className="bi bi-calendar-week fs-4" style={{ color: '#00838f' }}></i>
                  </div>
                  <div>
                    <div className="text-muted small text-uppercase">Ajoutés cette semaine</div>
                    <div className="fs-4 fw-bold">{addedThisWeek}</div>
                  </div>
                </div>
              </div>
            </div>
            <div className="col-6 col-lg-3">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-body d-flex align-items-center">
                  <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#fff8e1' }}>
                    <i className="bi bi-star fs-4" style={{ color: '#f9a825' }}></i>
                  </div>
                  <div>
                    <div className="text-muted small text-uppercase">Type fréquent</div>
                    <div className="fs-6 fw-bold text-truncate" style={{ maxWidth: '120px' }}>
                      {stats.documents_by_type?.reduce((top, item) => (item.count > (top?.count || 0) ? item : top), null)?.name || '—'}
                    </div>
                    <div className="small text-muted">
                      {stats.documents_by_type?.reduce((top, item) => (item.count > (top?.count || 0) ? item : top), null)?.count || 0} doc(s)
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="col-6 col-lg-3">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-body d-flex align-items-center">
                  <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#fce4ec' }}>
                    <i className="bi bi-shield-check fs-4" style={{ color: '#ad1457' }}></i>
                  </div>
                  <div>
                    <div className="text-muted small text-uppercase">Mon service</div>
                    <div className="fs-6 fw-bold text-truncate" style={{ maxWidth: '120px' }}>
                      {user?.affectation?.service?.name || '—'}
                    </div>
                    <div className="small text-muted">Périmètre</div>
                  </div>
                </div>
              </div>
            </div>
          </>
        )}
      </div>

      {/* Contenu principal : Documents récents + Diagramme */}
      <div className="row g-4">
        {/* Documents récents */}
        <div className="col-lg-7">
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

        {/* Diagramme circulaire (Donut SVG) */}
        <div className="col-lg-5">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-header bg-white py-3">
              <h5 className="mb-0 fw-bold">
                <i className="bi bi-pie-chart-fill me-2"></i>Répartition par type
              </h5>
            </div>
            <div className="card-body d-flex flex-column align-items-center">
              {totalDocs === 0 ? (
                <div className="text-center py-5 w-100">
                  <i className="bi bi-pie-chart display-4 text-muted"></i>
                  <p className="text-muted mt-3 mb-0">Aucune donnée disponible.</p>
                </div>
              ) : (
                <>
                  {/* SVG Donut */}
                  <div className="position-relative my-3" style={{ width: '220px', height: '220px' }}>
                    <svg width="220" height="220" viewBox="0 0 200 200">
                      {segments.map((seg, i) => (
                        <path
                          key={i}
                          d={donutPath(seg.startPercent, seg.startPercent + seg.percent)}
                          fill={seg.color}
                          opacity="0.9"
                        />
                      ))}
                    </svg>
                    {/* Total au centre */}
                    <div className="position-absolute top-50 start-50 translate-middle text-center">
                      <div className="fs-3 fw-bold lh-1">{totalDocs}</div>
                      <div className="small text-muted">documents</div>
                    </div>
                  </div>

                  {/* Légende */}
                  <div className="w-100 mt-2">
                    {segments.map((seg, i) => (
                      <div key={i} className="d-flex align-items-center mb-2">
                        <span className="me-2" style={{ width: '12px', height: '12px', borderRadius: '3px', backgroundColor: seg.color, display: 'inline-block' }}></span>
                        <span className="small flex-grow-1 text-truncate">{seg.name}</span>
                        <span className="badge bg-light text-dark ms-2">{seg.count}</span>
                        <span className="small text-muted ms-2" style={{ minWidth: '45px', textAlign: 'right' }}>{seg.percent.toFixed(0)}%</span>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Accès rapide — mis en avant avec de grandes cartes */}
      <div className="mt-4">
        <h5 className="fw-bold mb-3">
          <i className="bi bi-grid-3x3-gap me-2"></i>Accès rapide
        </h5>
        <div className="row g-3">
          <div className="col-6 col-md-3">
            <NavLink to="/documents" className="text-decoration-none">
              <div className="card border-0 shadow-sm h-100 text-center quick-action-card p-4">
                <div className="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style={{ width: '60px', height: '60px', backgroundColor: '#e3f2fd' }}>
                  <i className="bi bi-folder2-open fs-3" style={{ color: '#1976d2' }}></i>
                </div>
                <div className="fw-bold text-dark">Documents</div>
                <div className="small text-muted">Consulter et gérer</div>
              </div>
            </NavLink>
          </div>

          {isAdmin && (
            <>
              <div className="col-6 col-md-3">
                <NavLink to="/document-types" className="text-decoration-none">
                  <div className="card border-0 shadow-sm h-100 text-center quick-action-card p-4">
                    <div className="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style={{ width: '60px', height: '60px', backgroundColor: '#e8f5e9' }}>
                      <i className="bi bi-tags fs-3" style={{ color: '#2e7d32' }}></i>
                    </div>
                    <div className="fw-bold text-dark">Types</div>
                    <div className="small text-muted">Catégories de documents</div>
                  </div>
                </NavLink>
              </div>
              <div className="col-6 col-md-3">
                <NavLink to="/services" className="text-decoration-none">
                  <div className="card border-0 shadow-sm h-100 text-center quick-action-card p-4">
                    <div className="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style={{ width: '60px', height: '60px', backgroundColor: '#fff3e0' }}>
                      <i className="bi bi-building fs-3" style={{ color: '#e65100' }}></i>
                    </div>
                    <div className="fw-bold text-dark">Services</div>
                    <div className="small text-muted">Organisation</div>
                  </div>
                </NavLink>
              </div>
              <div className="col-6 col-md-3">
                <NavLink to="/users" className="text-decoration-none">
                  <div className="card border-0 shadow-sm h-100 text-center quick-action-card p-4">
                    <div className="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style={{ width: '60px', height: '60px', backgroundColor: '#f3e5f5' }}>
                      <i className="bi bi-people fs-3" style={{ color: '#7b1fa2' }}></i>
                    </div>
                    <div className="fw-bold text-dark">Utilisateurs</div>
                    <div className="small text-muted">Comptes & accès</div>
                  </div>
                </NavLink>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}