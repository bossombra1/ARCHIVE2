import React, { useState, useEffect, useCallback } from 'react';
import { documentActionGrantService } from '../../services/documentActionGrantService';
import { adminService } from '../../services/adminService';
import { documentService } from '../../services/documentService';

/**
 * Vue Administrateur Système : octroi/révocation des droits d'action
 * documentaire (modifier / supprimer / ajouter) à un ou plusieurs
 * utilisateurs simultanément. Cette page n'est accessible que si l'admin
 * navigue vers /document-grants (lien visible uniquement pour lui dans
 * la Navbar) — la sécurité réelle reste le middleware poste:admin.
 */
export default function DocumentGrantsView() {
  const [grants, setGrants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [users, setUsers] = useState([]);
  const [documents, setDocuments] = useState([]);

  const [showModal, setShowModal] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState('');
  const [form, setForm] = useState({
    user_ids: [],
    can_modify: false,
    can_delete: false,
    can_add: false,
    scope: 'all',
    document_ids: [],
    expires_at: '',
  });

  const fetchGrants = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setGrants(await documentActionGrantService.getAll());
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchPickers = useCallback(async () => {
    try {
      const [usersRes, docsRes] = await Promise.all([
        adminService.getUsers({ per_page: 500 }),
        documentService.getAll({ per_page: 500 }),
      ]);
      setUsers(usersRes.data || []);
      setDocuments(docsRes.data || []);
    } catch (err) { console.error(err); }
  }, []);

  useEffect(() => { fetchGrants(); }, [fetchGrants]);
  useEffect(() => { fetchPickers(); }, [fetchPickers]);

  const openModal = () => {
    setForm({
      user_ids: [], can_modify: false, can_delete: false, can_add: false,
      scope: 'all', document_ids: [], expires_at: '',
    });
    setFormError('');
    setShowModal(true);
  };

  const toggleUser = (id) => {
    setForm((f) => ({
      ...f,
      user_ids: f.user_ids.includes(id) ? f.user_ids.filter((u) => u !== id) : [...f.user_ids, id],
    }));
  };

  const toggleDocument = (id) => {
    setForm((f) => ({
      ...f,
      document_ids: f.document_ids.includes(id) ? f.document_ids.filter((d) => d !== id) : [...f.document_ids, id],
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFormError('');

    if (form.user_ids.length === 0) {
      setFormError('Sélectionnez au moins un utilisateur.');
      return;
    }
    if (!form.can_modify && !form.can_delete && !form.can_add) {
      setFormError('Accordez au moins un droit (modifier, supprimer ou ajouter).');
      return;
    }
    if (form.scope === 'specific' && form.document_ids.length === 0) {
      setFormError("Sélectionnez au moins un document pour une portée 'documents précis'.");
      return;
    }

    setSaving(true);
    try {
      await documentActionGrantService.grant({
        user_ids: form.user_ids,
        can_modify: form.can_modify,
        can_delete: form.can_delete,
        can_add: form.can_add,
        scope: form.scope,
        document_ids: form.scope === 'specific' ? form.document_ids : undefined,
        expires_at: form.expires_at || null,
      });
      setShowModal(false);
      fetchGrants();
    } catch (err) {
      if (err?.response?.status === 422) {
        const errs = err.response.data.errors || {};
        const first = Object.values(errs)[0];
        setFormError(Array.isArray(first) ? first[0] : 'Données invalides.');
      } else {
        setFormError(err?.response?.data?.message || 'Erreur.');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleRevoke = async (grant) => {
    if (!confirm(`Révoquer les droits accordés à "${grant.user?.name}" ?`)) return;
    try {
      await documentActionGrantService.revoke(grant.id);
      fetchGrants();
    } catch (err) {
      alert(err?.response?.data?.message || 'Action refusée.');
    }
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Droits d'action documentaire</h2>
          <p className="text-muted mb-0">
            Accordez à des utilisateurs le droit de modifier, supprimer ou ajouter des documents,
            en plus de leurs droits hiérarchiques habituels.
          </p>
        </div>
        <button className="btn btn-primary" onClick={openModal}>
          <i className="bi bi-shield-plus me-1"></i> Accorder des droits
        </button>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>
      ) : grants.length === 0 ? (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5">
            <i className="bi bi-shield-lock display-4 text-muted"></i>
            <p className="text-muted mt-3 mb-0">Aucun droit accordé pour le moment.</p>
          </div>
        </div>
      ) : (
        <div className="card border-0 shadow-sm">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th>Utilisateur</th>
                    <th>Droits</th>
                    <th>Portée</th>
                    <th>Accordé par</th>
                    <th>Expiration</th>
                    <th className="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {grants.map((g) => (
                    <tr key={g.id}>
                      <td>
                        <span className="fw-semibold">{g.user?.name}</span><br />
                        <span className="small text-muted">{g.user?.email}</span>
                      </td>
                      <td>
                        {g.can_modify && <span className="badge bg-warning text-dark me-1">Modifier</span>}
                        {g.can_delete && <span className="badge bg-danger me-1">Supprimer</span>}
                        {g.can_add && <span className="badge bg-success me-1">Ajouter</span>}
                      </td>
                      <td>
                        {g.scope === 'all' ? (
                          <span className="badge bg-primary">Tous les documents</span>
                        ) : (
                          <span title={g.documents?.map((d) => d.title).join(', ')}>
                            {g.documents?.length || 0} document(s) précis
                          </span>
                        )}
                      </td>
                      <td className="small text-muted">{g.granted_by?.name || '—'}</td>
                      <td className="small">
                        {g.is_permanent ? (
                          <span className="text-muted">Permanent</span>
                        ) : g.is_expired ? (
                          <span className="badge bg-secondary">Expiré</span>
                        ) : (
                          new Date(g.expires_at).toLocaleDateString('fr-FR')
                        )}
                      </td>
                      <td className="text-end">
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleRevoke(g)} title="Révoquer">
                          <i className="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* ===== MODAL: ACCORDER DES DROITS ===== */}
      {showModal && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-lg">
            <div className="modal-content">
              <form onSubmit={handleSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title"><i className="bi bi-shield-plus me-2"></i>Accorder des droits</h5>
                  <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                </div>
                <div className="modal-body">
                  {formError && <div className="alert alert-danger py-2">{formError}</div>}

                  <label className="form-label">Utilisateurs * ({form.user_ids.length} sélectionné(s))</label>
                  <div className="border rounded p-2 mb-3" style={{ maxHeight: '160px', overflowY: 'auto' }}>
                    {users.map((u) => (
                      <div className="form-check" key={u.id}>
                        <input
                          className="form-check-input" type="checkbox" id={`grant-user-${u.id}`}
                          checked={form.user_ids.includes(u.id)}
                          onChange={() => toggleUser(u.id)}
                        />
                        <label className="form-check-label small" htmlFor={`grant-user-${u.id}`}>
                          {u.name} <span className="text-muted">({u.email})</span>
                        </label>
                      </div>
                    ))}
                  </div>

                  <label className="form-label">Droits accordés *</label>
                  <div className="d-flex gap-3 mb-3">
                    <div className="form-check">
                      <input className="form-check-input" type="checkbox" id="grant-modify"
                        checked={form.can_modify} onChange={(e) => setForm({ ...form, can_modify: e.target.checked })} />
                      <label className="form-check-label" htmlFor="grant-modify">Modifier</label>
                    </div>
                    <div className="form-check">
                      <input className="form-check-input" type="checkbox" id="grant-delete"
                        checked={form.can_delete} onChange={(e) => setForm({ ...form, can_delete: e.target.checked })} />
                      <label className="form-check-label" htmlFor="grant-delete">Supprimer</label>
                    </div>
                    <div className="form-check">
                      <input className="form-check-input" type="checkbox" id="grant-add"
                        checked={form.can_add} onChange={(e) => setForm({ ...form, can_add: e.target.checked })} />
                      <label className="form-check-label" htmlFor="grant-add">Ajouter</label>
                    </div>
                  </div>

                  <label className="form-label">Portée (modifier / supprimer) *</label>
                  <select className="form-select mb-3" value={form.scope}
                    onChange={(e) => setForm({ ...form, scope: e.target.value, document_ids: [] })}>
                    <option value="all">Tous les documents de l'entreprise</option>
                    <option value="specific">Documents précis</option>
                  </select>

                  {form.scope === 'specific' && (
                    <>
                      <label className="form-label">Documents * ({form.document_ids.length} sélectionné(s))</label>
                      <div className="border rounded p-2 mb-3" style={{ maxHeight: '160px', overflowY: 'auto' }}>
                        {documents.map((d) => (
                          <div className="form-check" key={d.id}>
                            <input
                              className="form-check-input" type="checkbox" id={`grant-doc-${d.id}`}
                              checked={form.document_ids.includes(d.id)}
                              onChange={() => toggleDocument(d.id)}
                            />
                            <label className="form-check-label small" htmlFor={`grant-doc-${d.id}`}>{d.title}</label>
                          </div>
                        ))}
                      </div>
                    </>
                  )}

                  <label className="form-label">Expiration (optionnel)</label>
                  <input type="datetime-local" className="form-control"
                    value={form.expires_at}
                    onChange={(e) => setForm({ ...form, expires_at: e.target.value })} />
                  <small className="text-muted">Laisser vide pour un droit permanent.</small>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-primary" disabled={saving}>
                    {saving ? 'Enregistrement…' : 'Accorder'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}