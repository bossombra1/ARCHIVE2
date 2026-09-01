import React, { useEffect, useState, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { authCheckService } from '../../services/authCheckService';
import { documentService } from '../../services/documentService';
import { typeDocService } from '../../services/typeDocService';

export default function DocumentView() {
  const { user, t } = useApp();

  const [documents, setDocuments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [filters, setFilters] = useState({ search: '', document_type_id: '', page: 1, per_page: 10 });
  const [loadingList, setLoadingList] = useState(false);
  const [listError, setListError] = useState('');
  const [documentTypes, setDocumentTypes] = useState([]);

  // Upload modal
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [upload, setUpload] = useState({ title: '', description: '', document_type_id: '', file: null });
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState('');

  // Preview modal
  const [previewModal, setPreviewModal] = useState({ open: false, doc: null, url: null, loading: false });

  // Edit modal
  const [editModal, setEditModal] = useState({ open: false, doc: null });

  // Permissions modal
  const [permModal, setPermModal] = useState({ open: false, documentId: null, documentTitle: '' });
  const [permissions, setPermissions] = useState([]);
  const [permLoading, setPermLoading] = useState(false);
  const [permForm, setPermForm] = useState({ target_type: 'user', target_id: '', expires_at: '' });
  const [permError, setPermError] = useState('');
  const [targets, setTargets] = useState({ users: [], postes: [], services: [] });

  const canGrant = authCheckService.canGrantPermissions(user);

  const fetchDocuments = useCallback(async () => {
    setLoadingList(true);
    setListError('');
    try {
      const data = await documentService.getAll(filters);
      setDocuments(data.data || []);
      setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total, per_page: data.per_page });
    } catch (err) {
      setListError(err?.response?.data?.message || 'Erreur lors du chargement.');
    } finally {
      setLoadingList(false);
    }
  }, [filters]);

  const fetchDocumentTypes = useCallback(async () => {
    try {
      setDocumentTypes(await typeDocService.getAll());
    } catch (err) { console.error(err); }
  }, []);

  useEffect(() => { fetchDocumentTypes(); }, [fetchDocumentTypes]);
  useEffect(() => { fetchDocuments(); }, [fetchDocuments]);

  // ---- Upload ----
  const openUploadModal = () => {
    setUpload({ title: '', description: '', document_type_id: '', file: null });
    setUploadError('');
    setShowUploadModal(true);
  };

  const handleUploadSubmit = async (e) => {
    e.preventDefault();
    setUploadError('');
    if (!upload.title || !upload.document_type_id || !upload.file) {
      setUploadError('Veuillez renseigner le titre, le type et le fichier.');
      return;
    }
    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('title', upload.title);
      formData.append('description', upload.description || '');
      formData.append('document_type_id', upload.document_type_id);
      formData.append('file', upload.file);
      await documentService.create(formData);
      setShowUploadModal(false);
      fetchDocuments();
    } catch (err) {
      if (err?.response?.status === 422 && err?.response?.data?.errors) {
        const firstErr = Object.values(err.response.data.errors)[0];
        setUploadError(Array.isArray(firstErr) ? firstErr[0] : 'Données invalides.');
      } else {
        setUploadError(err?.response?.data?.message || 'Échec de l\'upload.');
      }
    } finally {
      setUploading(false);
    }
  };

  // ---- Preview ----
  const openPreview = async (doc) => {
    setPreviewModal({ open: true, doc, url: null, loading: true });
    try {
      const url = await documentService.getPreviewUrl(doc.id);
      setPreviewModal({ open: true, doc, url, loading: false });
    } catch (err) {
      setPreviewModal({ open: true, doc, url: null, loading: false });
    }
  };

  const closePreview = () => {
    if (previewModal.url) documentService.releasePreviewUrl(previewModal.url);
    setPreviewModal({ open: false, doc: null, url: null, loading: false });
  };

  // ---- Download ----
  const handleDownload = async (doc) => {
    try {
      const safeName = (doc.title || 'document').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '.' + (doc.file_type || 'bin');
      await documentService.download(doc.id, safeName);
    } catch (err) {
      alert(err?.response?.data?.message || 'Téléchargement refusé.');
    }
  };

  // ---- Delete ----
  const handleDelete = async (doc) => {
    if (!confirm(`Supprimer le document "${doc.title}" ?`)) return;
    try {
      await documentService.delete(doc.id);
      fetchDocuments();
    } catch (err) {
      alert(err?.response?.data?.message || 'Suppression refusée.');
    }
  };

  // ---- Edit ----
  const openEditModal = (doc) => setEditModal({ open: true, doc });
  const handleEditSave = async (doc, data) => {
    try {
      await documentService.update(doc.id, data);
      setEditModal({ open: false, doc: null });
      fetchDocuments();
    } catch (err) {
      alert(err?.response?.data?.message || 'Modification refusée.');
    }
  };

  // ---- Permissions ----
  const openPermissions = async (doc) => {
    setPermModal({ open: true, documentId: doc.id, documentTitle: doc.title });
    setPermError('');
    setPermForm({ target_type: 'user', target_id: '', expires_at: '' });
    setPermLoading(true);
    try {
      const [list, tgts] = await Promise.all([
        documentService.getPermissions(doc.id),
        documentService.getPermissionTargets(),
      ]);
      setPermissions(list);
      setTargets(tgts);
    } catch (err) {
      setPermError(err?.response?.data?.message || 'Accès refusé.');
      setPermissions([]);
    } finally {
      setPermLoading(false);
    }
  };

  const closePermissions = () => {
    setPermModal({ open: false, documentId: null, documentTitle: '' });
    setPermissions([]);
    setPermError('');
  };

  const handleAddPermission = async (e) => {
    e.preventDefault();
    setPermError('');
    if (!permForm.target_id) {
      setPermError('Veuillez sélectionner une cible.');
      return;
    }
    try {
      await documentService.addPermission(permModal.documentId, {
        target_type: permForm.target_type,
        target_id: parseInt(permForm.target_id, 10),
        expires_at: permForm.expires_at || null,
      });
      const list = await documentService.getPermissions(permModal.documentId);
      setPermissions(list);
      setPermForm({ target_type: 'user', target_id: '', expires_at: '' });
    } catch (err) {
      if (err?.response?.status === 422 && err?.response?.data?.errors) {
        const firstErr = Object.values(err.response.data.errors)[0];
        setPermError(Array.isArray(firstErr) ? firstErr[0] : 'Données invalides.');
      } else {
        setPermError(err?.response?.data?.message || 'Échec de l\'enregistrement.');
      }
    }
  };

  const handleRevokePermission = async (permissionId) => {
    if (!confirm('Révoquer cette permission ?')) return;
    try {
      await documentService.revokePermission(permissionId);
      const list = await documentService.getPermissions(permModal.documentId);
      setPermissions(list);
    } catch (err) {
      setPermError(err?.response?.data?.message || 'Échec de la révocation.');
    }
  };

  const formatSize = (bytes) => {
    if (!bytes) return '—';
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} Mo`;
  };

  const getFileIcon = (fileType) => {
    if (fileType === 'pdf') return { icon: 'bi-file-earmark-pdf', color: 'text-danger' };
    if (['png', 'jpg', 'jpeg', 'gif'].includes(fileType)) return { icon: 'bi-file-earmark-image', color: 'text-info' };
    return { icon: 'bi-file-earmark', color: 'text-secondary' };
  };

  return (
    <div className="container-fluid py-4">
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">{t('documents') || 'Documents'}</h2>
          <p className="text-muted mb-0">{meta.total} document(s) visible(s)</p>
        </div>
        <button className="btn btn-primary" onClick={openUploadModal}>
          <i className="bi bi-plus-lg me-1"></i> Ajouter un document
        </button>
      </div>

      {/* Filtres */}
      <div className="card mb-3 shadow-sm border-0">
        <div className="card-body py-3">
          <div className="row g-2 align-items-end">
            <div className="col-md-5">
              <label className="form-label small text-muted mb-1">Rechercher</label>
              <input type="text" className="form-control" value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
                placeholder="Mot-clé…" />
            </div>
            <div className="col-md-4">
              <label className="form-label small text-muted mb-1">Type</label>
              <select className="form-select" value={filters.document_type_id}
                onChange={(e) => setFilters({ ...filters, document_type_id: e.target.value, page: 1 })}>
                <option value="">Tous les types</option>
                {documentTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>
            <div className="col-md-3">
              <button className="btn btn-outline-secondary w-100"
                onClick={() => setFilters({ search: '', document_type_id: '', page: 1, per_page: 10 })}>
                <i className="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Liste */}
      {listError && <div className="alert alert-danger">{listError}</div>}

      {loadingList ? (
        <div className="text-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Chargement…</span>
          </div>
        </div>
      ) : documents.length === 0 ? (
        <div className="card shadow-sm border-0">
          <div className="card-body text-center py-5">
            <i className="bi bi-inbox display-4 text-muted"></i>
            <p className="text-muted mt-3 mb-0">Aucun document visible.</p>
            <button className="btn btn-primary mt-3" onClick={openUploadModal}>
              <i className="bi bi-plus-lg me-1"></i> Ajouter le premier document
            </button>
          </div>
        </div>
      ) : (
        <div className="card shadow-sm border-0">
          <div className="card-body p-0">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '40px' }}></th>
                    <th>Titre</th>
                    <th style={{ width: '15%' }}>Type</th>
                    <th style={{ width: '15%' }}>Service</th>
                    <th style={{ width: '10%' }}>Taille</th>
                    <th style={{ width: '10%' }}>Date</th>
                    <th style={{ width: '180px' }} className="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {documents.map((doc) => {
                    const fi = getFileIcon(doc.file_type);
                    return (
                      <tr key={doc.id} style={{ cursor: 'pointer' }} onClick={() => openPreview(doc)}>
                        <td><i className={`bi ${fi.icon} ${fi.color} fs-5`}></i></td>
                        <td>
                          <div className="fw-semibold">{doc.title}</div>
                          {doc.description && <div className="small text-muted text-truncate" style={{ maxWidth: '300px' }}>{doc.description}</div>}
                        </td>
                        <td><span className="badge bg-light text-dark">{doc.document_type?.name || '—'}</span></td>
                        <td className="small">{doc.service?.name || '—'}</td>
                        <td className="small text-muted">{formatSize(doc.file_size)}</td>
                        <td className="small text-muted">{doc.created_at?.substring(0, 10)}</td>
                        <td className="text-end" onClick={(e) => e.stopPropagation()}>
                          <button className="btn btn-sm btn-outline-primary me-1" onClick={() => openPreview(doc)} title="Voir">
                            <i className="bi bi-eye"></i>
                          </button>
                          <button className="btn btn-sm btn-outline-success me-1" onClick={() => handleDownload(doc)} title="Télécharger">
                            <i className="bi bi-download"></i>
                          </button>
                          <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => openEditModal(doc)} title="Modifier">
                            <i className="bi bi-pencil"></i>
                          </button>
                          <button className="btn btn-sm btn-outline-danger me-1" onClick={() => handleDelete(doc)} title="Supprimer">
                            <i className="bi bi-trash"></i>
                          </button>
                          {canGrant && (
                            <button className="btn btn-sm btn-outline-warning" onClick={() => openPermissions(doc)} title="Permissions">
                              <i className="bi bi-shield-lock"></i>
                            </button>
                          )}
                        </td>
                      </tr>
                    );
                  })}
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

      {/* ===== MODAL: UPLOAD ===== */}
      {showUploadModal && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <form onSubmit={handleUploadSubmit}>
                <div className="modal-header">
                  <h5 className="modal-title"><i className="bi bi-plus-lg me-2"></i>Nouveau document</h5>
                  <button type="button" className="btn-close" onClick={() => setShowUploadModal(false)}></button>
                </div>
                <div className="modal-body">
                  {uploadError && <div className="alert alert-danger py-2">{uploadError}</div>}
                  <div className="mb-3">
                    <label className="form-label">Titre *</label>
                    <input type="text" className="form-control" value={upload.title}
                      onChange={(e) => setUpload({ ...upload, title: e.target.value })} maxLength={191} required autoFocus />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Type *</label>
                    <select className="form-select" value={upload.document_type_id}
                      onChange={(e) => setUpload({ ...upload, document_type_id: e.target.value })} required>
                      <option value="">— Sélectionner —</option>
                      {documentTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                    </select>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Fichier * (PDF, PNG, JPG, JPEG, GIF — max 10 Mo)</label>
                    <input type="file" className="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif"
                      onChange={(e) => setUpload({ ...upload, file: e.target.files[0] })} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description (optionnelle)</label>
                    <textarea className="form-control" rows={2} value={upload.description}
                      onChange={(e) => setUpload({ ...upload, description: e.target.value })} maxLength={5000} />
                  </div>
                  <small className="text-muted d-block">
                    <i className="bi bi-info-circle me-1"></i>
                    Le périmètre (service / département / direction) est déterminé automatiquement par le backend.
                  </small>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowUploadModal(false)}>Annuler</button>
                  <button type="submit" className="btn btn-primary" disabled={uploading}>
                    {uploading ? (<><span className="spinner-border spinner-border-sm me-1"></span>Upload…</>) : 'Ajouter'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* ===== MODAL: PREVIEW ===== */}
      {previewModal.open && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.75)' }}>
          <div className="modal-dialog modal-lg modal-dialog-scrollable">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title"><i className="bi bi-file-earmark me-2"></i>{previewModal.doc?.title}</h5>
                <button type="button" className="btn-close btn-close-white" onClick={closePreview}></button>
              </div>
              <div className="modal-body">
                <div className="row mb-3 g-2">
                  <div className="col-md-6">
                    <small className="text-muted d-block">Type</small>
                    <span className="fw-semibold">{previewModal.doc?.document_type?.name || '—'}</span>
                  </div>
                  <div className="col-md-6">
                    <small className="text-muted d-block">Service</small>
                    <span className="fw-semibold">{previewModal.doc?.service?.name || '—'}</span>
                  </div>
                  <div className="col-md-6">
                    <small className="text-muted d-block">Taille</small>
                    <span className="fw-semibold">{formatSize(previewModal.doc?.file_size)}</span>
                  </div>
                  <div className="col-md-6">
                    <small className="text-muted d-block">Créé le</small>
                    <span className="fw-semibold">{previewModal.doc?.created_at?.substring(0, 10)}</span>
                  </div>
                  {previewModal.doc?.description && (
                    <div className="col-12 mt-2">
                      <small className="text-muted d-block">Description</small>
                      <span>{previewModal.doc.description}</span>
                    </div>
                  )}
                </div>
                <hr />
                {previewModal.loading ? (
                  <div className="text-center py-4">
                    <div className="spinner-border text-primary" role="status"></div>
                    <p className="text-muted mt-2">Chargement du fichier…</p>
                  </div>
                ) : previewModal.url ? (
                  previewModal.doc?.file_type === 'pdf' ? (
                    <iframe src={previewModal.url} width="100%" height="500px" title="Preview"
                      style={{ border: '1px solid #dee2e6', borderRadius: '4px' }} />
                  ) : ['png', 'jpg', 'jpeg', 'gif'].includes(previewModal.doc?.file_type) ? (
                    <div className="text-center">
                      <img src={previewModal.url} alt="Preview" className="img-fluid" style={{ maxHeight: '500px' }} />
                    </div>
                  ) : (
                    <p className="text-muted text-center">Prévisualisation non disponible pour ce type de fichier.</p>
                  )
                ) : (
                  <p className="text-danger text-center">Impossible de charger le fichier.</p>
                )}
              </div>
              <div className="modal-footer">
                <button className="btn btn-primary" onClick={() => handleDownload(previewModal.doc)}>
                  <i className="bi bi-download me-1"></i> Télécharger
                </button>
                <button className="btn btn-secondary" onClick={closePreview}>Fermer</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ===== MODAL: EDIT ===== */}
      {editModal.open && editModal.doc && (
        <EditModal doc={editModal.doc} documentTypes={documentTypes}
          onClose={() => setEditModal({ open: false, doc: null })}
          onSave={handleEditSave} />
      )}

      {/* ===== MODAL: PERMISSIONS ===== */}
      {permModal.open && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-lg">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title"><i className="bi bi-shield-lock me-2"></i>Permissions — {permModal.documentTitle}</h5>
                <button type="button" className="btn-close" onClick={closePermissions}></button>
              </div>
              <div className="modal-body">
                {permError && <div className="alert alert-danger py-2">{permError}</div>}

                <form onSubmit={handleAddPermission} className="mb-4">
                  <div className="row g-2 align-items-end">
                    <div className="col-md-3">
                      <label className="form-label small">Type de cible</label>
                      <select className="form-select" value={permForm.target_type}
                        onChange={(e) => setPermForm({ ...permForm, target_type: e.target.value, target_id: '' })}>
                        <option value="user">Utilisateur</option>
                        <option value="poste">Poste</option>
                        <option value="service">Service</option>
                      </select>
                    </div>
                    <div className="col-md-5">
                      <label className="form-label small">Cible</label>
                      {permForm.target_type === 'user' && (
                        <select className="form-select" value={permForm.target_id}
                          onChange={(e) => setPermForm({ ...permForm, target_id: e.target.value })} required>
                          <option value="">— Sélectionner —</option>
                          {targets.users.map((u) => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                        </select>
                      )}
                      {permForm.target_type === 'poste' && (
                        <select className="form-select" value={permForm.target_id}
                          onChange={(e) => setPermForm({ ...permForm, target_id: e.target.value })} required>
                          <option value="">— Sélectionner —</option>
                          {targets.postes.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.level})</option>)}
                        </select>
                      )}
                      {permForm.target_type === 'service' && (
                        <select className="form-select" value={permForm.target_id}
                          onChange={(e) => setPermForm({ ...permForm, target_id: e.target.value })} required>
                          <option value="">— Sélectionner —</option>
                          {targets.services.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                      )}
                    </div>
                    <div className="col-md-3">
                      <label className="form-label small">Expire le (optionnel)</label>
                      <input type="datetime-local" className="form-control" value={permForm.expires_at}
                        onChange={(e) => setPermForm({ ...permForm, expires_at: e.target.value })} />
                    </div>
                    <div className="col-md-1">
                      <button type="submit" className="btn btn-primary w-100" title="Accorder">
                        <i className="bi bi-plus-lg"></i>
                      </button>
                    </div>
                  </div>
                  <small className="text-muted d-block mt-2">
                    Laissez « Expire le » vide pour une permission permanente.
                  </small>
                </form>

                {permLoading ? (
                  <p className="text-muted text-center py-3">Chargement…</p>
                ) : permissions.length === 0 ? (
                  <p className="text-muted text-center py-3">Aucune permission enregistrée.</p>
                ) : (
                  <table className="table table-sm table-striped">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Cible</th>
                        <th>Expire le</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {permissions.map((p) => (
                        <tr key={p.id}>
                          <td><span className="badge bg-secondary">{p.target_type}</span></td>
                          <td>{p.target_label || `#${p.target_id}`}</td>
                          <td className="small">{p.expires_at || 'Permanent'}</td>
                          <td>
                            {p.is_expired
                              ? <span className="badge bg-danger">Expirée</span>
                              : p.is_permanent
                                ? <span className="badge bg-success">Permanente</span>
                                : <span className="badge bg-info">Valide</span>}
                          </td>
                          <td className="text-end">
                            <button className="btn btn-sm btn-outline-danger" onClick={() => handleRevokePermission(p.id)}>
                              <i className="bi bi-x-lg"></i>
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
              <div className="modal-footer">
                <button className="btn btn-secondary" onClick={closePermissions}>Fermer</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/*
|--------------------------------------------------------------------------
| Sous-composant: EditModal
|--------------------------------------------------------------------------
*/
function EditModal({ doc, documentTypes, onClose, onSave }) {
  const [title, setTitle] = useState(doc.title);
  const [description, setDescription] = useState(doc.description || '');
  const [typeId, setTypeId] = useState(doc.document_type_id);

  return (
    <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title"><i className="bi bi-pencil me-2"></i>Modifier le document</h5>
            <button type="button" className="btn-close" onClick={onClose}></button>
          </div>
          <div className="modal-body">
            <div className="mb-3">
              <label className="form-label">Titre</label>
              <input type="text" className="form-control" value={title} onChange={(e) => setTitle(e.target.value)} maxLength={191} />
            </div>
            <div className="mb-3">
              <label className="form-label">Type</label>
              <select className="form-select" value={typeId} onChange={(e) => setTypeId(e.target.value)}>
                {documentTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>
            <div className="mb-3">
              <label className="form-label">Description</label>
              <textarea className="form-control" rows={2} value={description} onChange={(e) => setDescription(e.target.value)} maxLength={5000} />
            </div>
          </div>
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={onClose}>Annuler</button>
            <button className="btn btn-primary" onClick={() => onSave(doc, { title, description, document_type_id: typeId })}>
              Enregistrer
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}