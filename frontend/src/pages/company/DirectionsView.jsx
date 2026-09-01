import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { adminService } from '../../services/adminService';

export default function DirectionsView() {
  const { themeColor } = useApp();
  const [directions, setDirections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [name, setName] = useState('');
  const [saving, setSaving] = useState(false);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await adminService.getDirections();
      setDirections(data);
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetch(); }, [fetch]);

  const openCreate = () => { setEditing(null); setName(''); setShowModal(true); };
  const openEdit = (d) => { setEditing(d); setName(d.name); setShowModal(true); };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return;
    setSaving(true);
    try {
      if (editing) {
        await adminService.updateDirection(editing.id, name);
      } else {
        await adminService.createDirection(name);
      }
      setShowModal(false);
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur lors de l\'enregistrement.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (d) => {
    if (!confirm(`Supprimer la direction "${d.name}" ?`)) return;
    try {
      await adminService.deleteDirection(d.id);
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Suppression refusée.');
    }
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Directions</h2>
          <p className="text-muted mb-0">{directions.length} direction(s)</p>
        </div>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Nouvelle direction
        </button>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="text-center py-5">
          <div className="spinner-border text-primary"></div>
        </div>
      ) : directions.length === 0 ? (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5">
            <i className="bi bi-compass display-4 text-muted"></i>
            <p className="text-muted mt-3 mb-0">Aucune direction pour le moment.</p>
          </div>
        </div>
      ) : (
        <div className="card border-0 shadow-sm">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '70px' }}>#</th>
                    <th>Nom</th>
                    <th style={{ width: '180px' }}>Départements</th>
                    <th style={{ width: '120px' }} className="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {directions.map((d) => (
                    <tr key={d.id}>
                      <td className="text-muted">{d.id}</td>
                      <td className="fw-semibold">{d.name}</td>
                      <td>
                        <span className="badge bg-light text-dark">
                          {d.departments_count} département(s)
                        </span>
                      </td>
                      <td className="text-end">
                        <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => openEdit(d)}>
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(d)}>
                          <i className="bi bi-trash"></i>
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

      {showModal && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <form onSubmit={handleSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title">
                    <i className="bi bi-compass me-2"></i>
                    {editing ? 'Modifier la direction' : 'Nouvelle direction'}
                  </h5>
                  <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                </div>
                <div className="modal-body">
                  <label className="form-label">Nom *</label>
                  <input type="text" className="form-control" value={name}
                    onChange={(e) => setName(e.target.value)} maxLength={191} required autoFocus />
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-primary" disabled={saving}>
                    {saving ? 'Enregistrement…' : 'Enregistrer'}
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