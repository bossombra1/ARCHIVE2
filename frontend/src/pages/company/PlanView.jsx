import React, { useState, useEffect, useCallback } from 'react';
import { planService } from '../../services/planService';
import { useApp } from '../../context/AppContext';

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
 * Vue forfait : consultation de l'usage (tous les users), et demande de
 * changement de taille/palier (réservé à l'Administrateur Système).
 *
 * Le changement n'est plus immédiat : l'admin soumet une DEMANDE qui reste
 * en attente jusqu'à validation MANUELLE hors application (en base ou via
 * `php artisan plan:process`). Une fois approuvée, un bouton "Appliquer"
 * apparaît : seul ce clic final bascule réellement le forfait, et le
 * backend refuse toute demande non approuvée (409).
 */
export default function PlanView() {
  const { user } = useApp();
  const isAdmin = user?.affectation?.poste?.level === 'admin';

  const [usage, setUsage] = useState(null);
  const [planRequest, setPlanRequest] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [changing, setChanging] = useState(false);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [usageData, requestData] = await Promise.all([
        planService.getUsage(),
        // La demande ne concerne que l'admin ; un échec ne doit jamais
        // casser la consultation de l'usage (catch silencieux -> null).
        isAdmin ? planService.getPlanChangeRequest().catch(() => null) : Promise.resolve(null),
      ]);
      setUsage(usageData);
      setPlanRequest(requestData);
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, [isAdmin]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleRequestChange = async (size) => {
    if (!usage || size === usage.size) return;
    if (!confirm(`Demander le passage au forfait "${SIZE_LABELS[size]}" ?\n\nLa demande devra être validée manuellement hors application avant d'être appliquée.`)) return;
    setChanging(true);
    try {
      const res = await planService.requestPlanChange(size);
      setPlanRequest(res.request);
      alert(res.message || 'Demande enregistrée.');
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
    } finally {
      setChanging(false);
    }
  };

  const handleApply = async () => {
    if (!confirm('Appliquer la demande approuvée ? Le forfait changera immédiatement.')) return;
    setChanging(true);
    try {
      const res = await planService.applyPlanChange();
      setUsage(res.usage);
      setPlanRequest(null);
      alert(res.message || 'Forfait mis à jour.');
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
      fetchData();
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

  // Bandeau d'état de la demande en cours (admin uniquement).
  const requestBanner = isAdmin && planRequest && (
    planRequest.status === 'pending' ? (
      <div className="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i className="bi bi-hourglass-split fs-5"></i>
        <div>
          <span className="fw-semibold">Demande #{planRequest.id} en attente de validation externe</span> — passage
          « {SIZE_LABELS[planRequest.current_size]} » vers « {SIZE_LABELS[planRequest.requested_size]} »,
          soumise le {new Date(planRequest.created_at).toLocaleString('fr-FR')}.
          <div className="small">Elle sera traitée manuellement (hors application) avant de pouvoir être appliquée.</div>
        </div>
      </div>
    ) : planRequest.status === 'approved' ? (
      <div className="alert alert-success d-flex align-items-center justify-content-between gap-2 mb-4">
        <div className="d-flex align-items-center gap-2">
          <i className="bi bi-check-circle fs-5"></i>
          <div>
            <span className="fw-semibold">Demande #{planRequest.id} approuvée hors application</span> — passage
            « {SIZE_LABELS[planRequest.current_size]} » vers « {SIZE_LABELS[planRequest.requested_size]} ».
          </div>
        </div>
        <button className="btn btn-success btn-sm" disabled={changing} onClick={handleApply}>
          <i className="bi bi-check2-all me-1"></i>Appliquer
        </button>
      </div>
    ) : planRequest.status === 'rejected' ? (
      <div className="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i className="bi bi-x-circle fs-5"></i>
        <div>
          <span className="fw-semibold">Demande #{planRequest.id} refusée hors application</span>
          {planRequest.note && <div className="small">Motif : {planRequest.note}</div>}
        </div>
      </div>
    ) : null
  );

  return (
    <div className="container-fluid py-4">
      <div className="mb-4">
        <h2 className="fw-bold mb-1">Forfait & usage</h2>
        <p className="text-muted mb-0">Forfait actuel : <span className="fw-semibold">{usage.label}</span></p>
      </div>

      {requestBanner}

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

        {isAdmin && (
          <div className="col-lg-6">
            <div className="card border-0 shadow-sm">
              <div className="card-body">
                <h5 className="card-title mb-3">Demander un changement de forfait</h5>
                <div className="list-group">
                  {Object.keys(SIZE_LABELS).map((size) => (
                    <button
                      key={size}
                      className={`list-group-item list-group-item-action d-flex justify-content-between align-items-center ${usage.size === size ? 'active' : ''}`}
                      disabled={changing || usage.size === size || planRequest?.status === 'pending' || planRequest?.status === 'approved'}
                      onClick={() => handleRequestChange(size)}
                    >
                      {SIZE_LABELS[size]}
                      {usage.size === size && <span className="badge bg-light text-dark">Actuel</span>}
                    </button>
                  ))}
                </div>
                <div className="small text-muted mt-3">
                  <i className="bi bi-info-circle me-1"></i>
                  Une demande doit être validée manuellement hors application (en base ou via
                  <code className="ms-1">php artisan plan:process</code>) avant de pouvoir être appliquée.
                  Aucun changement de palier n'affecte les données existantes : en cas de rétrogradation,
                  les ajouts restent bloqués tant que l'usage dépasse la nouvelle limite.
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}