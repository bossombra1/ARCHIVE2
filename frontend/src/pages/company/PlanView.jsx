import React, { useState, useEffect, useCallback } from 'react';
import { planService } from '../../services/planService';

const SIZE_LABELS = { small: 'Petite entreprise', medium: 'Moyenne entreprise', large: 'Grande entreprise' };

function UsageBar({ label, used, max, remaining, limitReached }) {
  const pct = max ? Math.min(100, Math.round((used / max) * 100)) : 0;
  return (
    <div className="mb-4">
      <div className="d-flex justify-content-between">
        <span className="fw-semibold">{label}</span>
        <span className={limitReached ? 'text-danger fw-semibold' : 'text-muted'}>
          {used} / {max ?? '∞'}
        </span>
      </div>
      {max ? (
        <div className="progress mt-1" style={{ height: '10px' }}>
          <div
            className={`progress-bar ${limitReached ? 'bg-danger' : pct > 80 ? 'bg-warning' : 'bg-success'}`}
            style={{ width: `${pct}%` }}
          ></div>
        </div>
      ) : (
        <div className="small text-muted mt-1">Illimité pour ce forfait.</div>
      )}
      {limitReached && (
        <div className="small text-danger mt-1">
          <i className="bi bi-exclamation-triangle me-1"></i> Limite atteinte.
        </div>
      )}
      {!limitReached && max !== null && (
        <div className="small text-muted mt-1">{remaining} restant(s).</div>
      )}
    </div>
  );
}

/**
 * Vue forfait : consultation de l'usage (tous les users), et changement
 * de taille/palier (réservé à l'Administrateur Système — la Navbar ne
 * montre le lien qu'à lui, mais le backend reste la seule vraie barrière).
 */
export default function PlanView() {
  const [usage, setUsage] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [changing, setChanging] = useState(false);

  const fetchUsage = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setUsage(await planService.getUsage());
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchUsage(); }, [fetchUsage]);

  const handleChangeSize = async (size) => {
    if (size === usage.size) return;
    if (!confirm(`Passer au forfait "${SIZE_LABELS[size]}" ?`)) return;
    setChanging(true);
    try {
      const res = await planService.updateSize(size);
      setUsage(res.usage);
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
    } finally {
      setChanging(false);
    }
  };

  if (loading) {
    return <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>;
  }
  if (error) {
    return <div className="container-fluid py-4"><div className="alert alert-danger">{error}</div></div>;
  }

  return (
    <div className="container-fluid py-4">
      <div className="mb-4">
        <h2 className="fw-bold mb-1">Forfait & usage</h2>
        <p className="text-muted mb-0">Forfait actuel : <span className="fw-semibold">{usage.label}</span></p>
      </div>

      <div className="row g-4">
        <div className="col-lg-6">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <h5 className="card-title mb-3">Usage courant</h5>
              <UsageBar
                label="Utilisateurs"
                used={usage.users.used} max={usage.users.max}
                remaining={usage.users.remaining} limitReached={usage.users.limit_reached}
              />
              <UsageBar
                label="Documents"
                used={usage.documents.used} max={usage.documents.max}
                remaining={usage.documents.remaining} limitReached={usage.documents.limit_reached}
              />
            </div>
          </div>
        </div>

        <div className="col-lg-6">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <h5 className="card-title mb-3">Changer de forfait</h5>
              <div className="list-group">
                {Object.keys(SIZE_LABELS).map((size) => (
                  <button
                    key={size}
                    className={`list-group-item list-group-item-action d-flex justify-content-between align-items-center ${usage.size === size ? 'active' : ''}`}
                    disabled={changing || usage.size === size}
                    onClick={() => handleChangeSize(size)}
                  >
                    {SIZE_LABELS[size]}
                    {usage.size === size && <span className="badge bg-light text-dark">Actuel</span>}
                  </button>
                ))}
              </div>
              <div className="small text-muted mt-3">
                <i className="bi bi-info-circle me-1"></i>
                Un changement de forfait n'affecte jamais les données existantes : en cas de
                rétrogradation, les ajouts restent bloqués tant que l'usage dépasse la nouvelle limite.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}