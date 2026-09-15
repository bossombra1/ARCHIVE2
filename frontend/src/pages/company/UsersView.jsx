import React, { useState, useEffect, useCallback } from 'react';
import { adminService } from '../../services/adminService';
import { serviceService } from '../../services/serviceService';
import { typeDocService } from '../../services/typeDocService';
import { planService } from '../../services/planService';

export default function UsersView() {
  const [userLimitReached, setUserLimitReached] = useState(false);

  useEffect(() => {
    planService.getUsage()
      .then((usage) => setUserLimitReached(Boolean(usage?.users?.limit_reached)))
      .catch(() => {}); // page reste utilisable même si /company/plan-usage échoue
  }, []);
  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [filters, setFilters] = useState({ search: '', page: 1, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Modals
  const [showUserModal, setShowUserModal] = useState(false);
  const [showAffectationModal, setShowAffectationModal] = useState(false);
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [selectedUser, setSelectedUser] = useState(null);

  // Form data
  const [userForm, setUserForm] = useState({
    name: '', email: '', password: '', poste_id: '', service_id: '',
    department_id: '', direction_id: '', status: true,
  });
  const [affectationForm, setAffectationForm] = useState({
    poste_id: '', service_id: '', department_id: '', direction_id: '',
  });
  const [newPassword, setNewPassword] = useState('');

  // Lists
  const [postes, setPostes] = useState([]);
  const [services, setServices] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [directions, setDirections] = useState([]);
  const [saving, setSaving] = useState(false);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await adminService.getUsers(filters);
      setUsers(data.data || []);
      setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total });
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  const fetchLists = useCallback(async () => {
    try {
      const [p, s, d, dirs] = await Promise.all([
        adminService.getPostes(),
        serviceService.getAll(),
        adminService.getDepartments(),
        adminService.getDirections(),
      ]);
      setPostes(p);
      setServices(s);
      setDepartments(d);
      setDirections(dirs);
    } catch (err) { console.error(err); }
  }, []);

  useEffect(() => { fetch(); }, [fetch]);
  useEffect(() => { fetchLists(); }, [fetchLists]);

  const openCreateUser = () => {
    setEditing(null);
    setUserForm({ name: '', email: '', password: '', poste_id: '', service_id: '', department_id: '', direction_id: '', status: true });
    setShowUserModal(true);
  };

  const openEditUser = (u) => {
    setEditing(u);
    setUserForm({ name: u.name, email: u.email, password: '', status: u.status, poste_id: '', service_id: '', department_id: '', direction_id: '' });
    setShowUserModal(true);
  };

  const handleUserSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) {
        await adminService.updateUser(editing.id, { name: userForm.name, email: userForm.email, status: userForm.status });
      } else {
        await adminService.createUser(userForm);
      }
      setShowUserModal(false);
      fetch();
    } catch (err) {
      if (err?.response?.status === 422) {
        const errs = err.response.data.errors || {};
        const first = Object.values(errs)[0];
        alert(Array.isArray(first) ? first[0] : 'Données invalides.');
      } else {
        alert(err?.response?.data?.message || 'Erreur.');
      }
    } finally {
      setSaving(false);
    }
  };

  const openAffectation = (u) => {
    setSelectedUser(u);
    const aff = u.affectations?.[0] || {};
    setAffectationForm({
      poste_id: aff.poste_id || '',
      service_id: aff.service_id || '',
      department_id: aff.department_id || '',
      direction_id: aff.direction_id || '',
    });
    setShowAffectationModal(true);
  };

  const handleAffectationSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      await adminService.updateUserAffectation(selectedUser.id, affectationForm);
      setShowAffectationModal(false);
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
    } finally {
      setSaving(false);
    }
  };

  const openPassword = (u) => {
    setSelectedUser(u);
    setNewPassword('');
    setShowPasswordModal(true);
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    if (newPassword.length < 6) { alert('Min 6 caractères.'); return; }
    setSaving(true);
    try {
      await adminService.resetUserPassword(selectedUser.id, newPassword);
      setShowPasswordModal(false);
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async (u) => {
    const action = u.status ? 'désactiver' : 'réactiver';
    if (!confirm(`${action} l'utilisateur "${u.name}" ?`)) return;
    try {
      if (u.status) {
        await adminService.deleteUser(u.id);
      } else {
        await adminService.reactivateUser(u.id);
      }
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Action refusée.');
    }
  };

  const getPosteName = (u) => {
    const aff = u.affectations?.[0];
    return aff?.poste?.name || '—';
  };
  const getServiceName = (u) => {
    const aff = u.affectations?.[0];
    return aff?.service?.name || '—';
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Utilisateurs & Accès</h2>
          <p className="text-muted mb-0">{meta.total} utilisateur(s)</p>
        </div>
        <button className="btn btn-primary" onClick={openCreateUser} disabled={userLimitReached}
          title={userLimitReached ? 'Limite du forfait atteinte' : ''}>
          <i className="bi bi-person-plus me-1"></i> Nouvel utilisateur
        </button>
      </div>
      {userLimitReached && (
        <div className="alert alert-warning py-2 small">
          <i className="bi bi-exclamation-triangle me-1"></i>
          Limite d'utilisateurs de votre forfait atteinte. Consultez la page Forfait & usage.
        </div>
      )}

      {/* Filtre */}
      <div className="card mb-3 border-0 shadow-sm">
        <div className="card-body py-3">
          <div className="row g-2 align-items-end">
            <div className="col-md-8">
              <label className="form-label small text-muted mb-1">Rechercher</label>
              <input type="text" className="form-control" value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
                placeholder="Nom ou email…" />
            </div>
            <div className="col-md-4">
              <button className="btn btn-outline-secondary w-100"
                onClick={() => setFilters({ search: '', page: 1, per_page: 15 })}>
                <i className="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
              </button>
            </div>
          </div>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>
      ) : (
        <div className="card border-0 shadow-sm">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '60px' }}>#</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Poste</th>
                    <th>Service</th>
                    <th style={{ width: '100px' }}>Statut</th>
                    <th style={{ width: '250px' }} className="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((u) => (
                    <tr key={u.id}>
                      <td className="text-muted">{u.id}</td>
                      <td className="fw-semibold">{u.name}</td>
                      <td className="small">{u.email}</td>
                      <td><span className="badge bg-primary">{getPosteName(u)}</span></td>
                      <td className="small">{getServiceName(u)}</td>
                      <td>
                        {u.status
                          ? <span className="badge bg-success">Actif</span>
                          : <span className="badge bg-secondary">Inactif</span>}
                      </td>
                      <td className="text-end">
                        <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => openEditUser(u)} title="Modifier">
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button className="btn btn-sm btn-outline-info me-1" onClick={() => openAffectation(u)} title="Affectation">
                          <i className="bi bi-briefcase"></i>
                        </button>
                        <button className="btn btn-sm btn-outline-warning me-1" onClick={() => openPassword(u)} title="Mot de passe">
                          <i className="bi bi-key"></i>
                        </button>
                        <button className={`btn btn-sm ${u.status ? 'btn-outline-danger' : 'btn-outline-success'}`}
                          onClick={() => handleToggleStatus(u)} title={u.status ? 'Désactiver' : 'Réactiver'}>
                          <i className={`bi ${u.status ? 'bi-lock' : 'bi-unlock'}`}></i>
                        </button>
                      </td>
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

      {/* ===== MODAL: CREATE/EDIT USER ===== */}
      {showUserModal && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-lg">
            <div className="modal-content">
              <form onSubmit={handleUserSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title">
                    <i className="bi bi-person me-2"></i>
                    {editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'}
                  </h5>
                  <button type="button" className="btn-close" onClick={() => setShowUserModal(false)}></button>
                </div>
                <div className="modal-body">
                  <div className="row g-3">
                    <div className="col-md-6">
                      <label className="form-label">Nom complet *</label>
                      <input type="text" className="form-control" value={userForm.name}
                        onChange={(e) => setUserForm({ ...userForm, name: e.target.value })} required />
                    </div>
                    <div className="col-md-6">
                      <label className="form-label">Email *</label>
                      <input type="email" className="form-control" value={userForm.email}
                        onChange={(e) => setUserForm({ ...userForm, email: e.target.value })} required />
                    </div>
                    {!editing && (
                      <div className="col-md-6">
                        <label className="form-label">Mot de passe * (min 6)</label>
                        <input type="password" className="form-control" value={userForm.password}
                          onChange={(e) => setUserForm({ ...userForm, password: e.target.value })} required minLength={6} />
                      </div>
                    )}
                    <div className="col-md-6">
                      <label className="form-label">Statut</label>
                      <select className="form-select" value={userForm.status ? '1' : '0'}
                        onChange={(e) => setUserForm({ ...userForm, status: e.target.value === '1' })}>
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                      </select>
                    </div>
                    {!editing && (
                      <>
                        <div className="col-12"><hr /><h6 className="text-muted">Affectation initiale</h6></div>
                        <div className="col-md-6">
                          <label className="form-label">Poste *</label>
                          <select className="form-select" value={userForm.poste_id}
                            onChange={(e) => setUserForm({ ...userForm, poste_id: e.target.value })} required>
                            <option value="">— Sélectionner —</option>
                            {postes.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.level})</option>)}
                          </select>
                        </div>
                        <div className="col-md-6">
                          <label className="form-label">Service *</label>
                          <select className="form-select" value={userForm.service_id}
                            onChange={(e) => setUserForm({ ...userForm, service_id: e.target.value })} required>
                            <option value="">— Sélectionner —</option>
                            {services.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                          </select>
                        </div>
                        <div className="col-md-6">
                          <label className="form-label">Département (optionnel)</label>
                          <select className="form-select" value={userForm.department_id}
                            onChange={(e) => setUserForm({ ...userForm, department_id: e.target.value })}>
                            <option value="">— Aucun —</option>
                            {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                          </select>
                        </div>
                        <div className="col-md-6">
                          <label className="form-label">Direction (optionnelle)</label>
                          <select className="form-select" value={userForm.direction_id}
                            onChange={(e) => setUserForm({ ...userForm, direction_id: e.target.value })}>
                            <option value="">— Aucune —</option>
                            {directions.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                          </select>
                        </div>
                      </>
                    )}
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowUserModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-primary" disabled={saving}>
                    {saving ? 'Enregistrement…' : 'Enregistrer'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* ===== MODAL: AFFECTATION ===== */}
      {showAffectationModal && selectedUser && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <form onSubmit={handleAffectationSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title">
                    <i className="bi bi-briefcase me-2"></i>Affectation — {selectedUser.name}
                  </h5>
                  <button type="button" className="btn-close" onClick={() => setShowAffectationModal(false)}></button>
                </div>
                <div className="modal-body">
                  <div className="alert alert-info py-2 small">
                    <i className="bi bi-info-circle me-1"></i>
                    L'ancienne affectation sera désactivée et une nouvelle sera créée.
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Poste *</label>
                    <select className="form-select" value={affectationForm.poste_id}
                      onChange={(e) => setAffectationForm({ ...affectationForm, poste_id: e.target.value })} required>
                      <option value="">— Sélectionner —</option>
                      {postes.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.level})</option>)}
                    </select>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Service *</label>
                    <select className="form-select" value={affectationForm.service_id}
                      onChange={(e) => setAffectationForm({ ...affectationForm, service_id: e.target.value })} required>
                      <option value="">— Sélectionner —</option>
                      {services.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                    </select>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Département (optionnel)</label>
                    <select className="form-select" value={affectationForm.department_id}
                      onChange={(e) => setAffectationForm({ ...affectationForm, department_id: e.target.value })}>
                      <option value="">— Aucun —</option>
                      {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                    </select>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Direction (optionnelle)</label>
                    <select className="form-select" value={affectationForm.direction_id}
                      onChange={(e) => setAffectationForm({ ...affectationForm, direction_id: e.target.value })}>
                      <option value="">— Aucune —</option>
                      {directions.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                    </select>
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowAffectationModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-primary" disabled={saving}>
                    {saving ? 'Enregistrement…' : 'Mettre à jour'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* ===== MODAL: PASSWORD ===== */}
      {showPasswordModal && selectedUser && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <form onSubmit={handlePasswordSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title">
                    <i className="bi bi-key me-2"></i>Réinitialiser le mot de passe — {selectedUser.name}
                  </h5>
                  <button type="button" className="btn-close" onClick={() => setShowPasswordModal(false)}></button>
                </div>
                <div className="modal-body">
                  <div className="alert alert-warning py-2 small">
                    <i className="bi bi-exclamation-triangle me-1"></i>
                    L'utilisateur devra utiliser ce nouveau mot de passe à la prochaine connexion.
                  </div>
                  <label className="form-label">Nouveau mot de passe * (min 6)</label>
                  <input type="password" className="form-control" value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)} required minLength={6} autoFocus />
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowPasswordModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-warning" disabled={saving}>
                    {saving ? 'Enregistrement…' : 'Réinitialiser'}
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