import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { typeDocService } from '../../services/typeDocService';

export default function TypeDocView() {
  const { themeColor } = useApp();
  const [types, setTypes] = useState([]);
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
      const data = await typeDocService.getAll();
      setTypes(data);
    } catch (err) {
      setError(err?.response?.data?.message || 'Erreur de chargement.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetch(); }, [fetch]);

  const openCreate = () => { setEditing(null); setName(''); setShowModal(true); };
  const openEdit = (t) => { setEditing(t); setName(t.name); setShowModal(true); };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return;
    setSaving(true);
    try {
      if (editing) {
        await typeDocService.update(editing.id, { name });
      } else {
        await typeDocService.create({ name });
      }
      setShowModal(false);
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Erreur.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (t) => {
    if (!confirm(`Supprimer le type "${t.name}" ?`)) return;
    try {
      await typeDocService.delete(t.id);
      fetch();
    } catch (err) {
      alert(err?.response?.data?.message || 'Suppression refusée (des documents utilisent peut-être ce type).');
    }
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Types de documents</h2>
          <p className="text-muted mb-0">{types.length} type(s) défini(s)</p>
        </div>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Nouveau type
        </button>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="text-center py-5"><div className="spinner-border text-primary"></div></div>
      ) : types.length === 0 ? (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5">
            <i className="bi bi-tags display-4 text-muted"></i>
            <p className="text-muted mt-3 mb-0">Aucun type pour le moment.</p>
          </div>
        </div>
      ) : (
        <div className="row g-3">
          {types.map((t) => (
            <div key={t.id} className="col-md-4 col-sm-6">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-body d-flex align-items-center">
                  <div className="rounded-3 p-3 me-3" style={{ backgroundColor: '#e3f2fd' }}>
                    <i className="bi bi-file-earmark fs-4" style={{ color: '#1976d2' }}></i>
                  </div>
                  <div className="flex-grow-1">
                    <div className="fw-semibold">{t.name}</div>
                    <small className="text-muted">ID #{t.id}</small>
                  </div>
                  <div className="d-flex flex-column gap-1">
                    <button className="btn btn-sm btn-outline-secondary" onClick={() => openEdit(t)}>
                      <i className="bi bi-pencil"></i>
                    </button>
                    <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(t)}>
                      <i className="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <form onSubmit={handleSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title">
                    <i className="bi bi-tag me-2"></i>
                    {editing ? 'Modifier le type' : 'Nouveau type'}
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