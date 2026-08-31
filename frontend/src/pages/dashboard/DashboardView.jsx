import React, { useState, useEffect } from 'react';
import Navbar from '../../components/Navbar';
import { dashboardService } from '../../services/dashboardService';
import { useApp } from '../../context/AppContext';

export default function DashboardView({ onLogout }) {
  const { themeColor, t } = useApp();
  const [stats, setStats] = useState({
    total_documents: 0,
    total_document_types: 0,
    total_services: 0,
    total_users: 0,
    documents_by_type: []
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        const data = await dashboardService.getStats();
        setStats(data);
      } catch (err) {
        setError("Impossible de charger les données dynamiques du tableau de bord.");
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  return (
    <div className="min-vh-100 bg-light">
      {/* Intégration de la Navbar */}
      <Navbar onLogout={onLogout} />

      {/* Contenu principal du Dashboard */}
      <div className="container py-4">
        <h2 className="mb-4 fw-bold" style={{ color: themeColor }}>
          <i className="bi bi-speedometer2 me-2"></i> {t('dashboard')} - Vue d'ensemble
        </h2>

        {error && <div className="alert alert-danger">{error}</div>}

        {loading ? (
          <div className="text-center py-5">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Chargement...</span>
            </div>
            <p className="text-muted mt-2">Chargement des données de la base...</p>
          </div>
        ) : (
          <>
            {/* Cartes de Statistiques Dynamiques */}
            <div className="row g-4 mb-4">
              <div className="col-md-3">
                <div className="card shadow-sm border-0 border-start border-4 border-primary p-3">
                  <h6 className="text-muted text-uppercase small fw-bold">Documents</h6>
                  <h3 className="fw-bold mb-0">{stats.total_documents}</h3>
                </div>
              </div>

              <div className="col-md-3">
                <div className="card shadow-sm border-0 border-start border-4 border-success p-3">
                  <h6 className="text-muted text-uppercase small fw-bold">Types de Documents</h6>
                  <h3 className="fw-bold mb-0">{stats.total_document_types}</h3>
                </div>
              </div>

              <div className="col-md-3">
                <div className="card shadow-sm border-0 border-start border-4 border-warning p-3">
                  <h6 className="text-muted text-uppercase small fw-bold">Services</h6>
                  <h3 className="fw-bold mb-0">{stats.total_services}</h3>
                </div>
              </div>

              <div className="col-md-3">
                <div className="card shadow-sm border-0 border-start border-4 border-info p-3">
                  <h6 className="text-muted text-uppercase small fw-bold">Utilisateurs</h6>
                  <h3 className="fw-bold mb-0">{stats.total_users}</h3>
                </div>
              </div>
            </div>

            {/* Section Informations Réelles / Répartition */}
            <div className="row">
              <div className="col-md-12">
                <div className="card shadow-sm p-4 border-0">
                  <h5 className="card-title fw-bold mb-3">Répartition des documents par type</h5>
                  {stats.documents_by_type.length === 0 ? (
                    <p className="text-muted fst-italic">Aucun document ou type de document enregistré pour le moment.</p>
                  ) : (
                    <ul className="list-group list-group-flush">
                      {stats.documents_by_type.map((item, index) => (
                        <li key={index} className="list-group-item d-flex justify-content-between align-items-center py-3">
                          <span className="fw-semibold">{item.name}</span>
                          <span className="badge bg-secondary rounded-pill px-3 py-2">{item.count} document(s)</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}