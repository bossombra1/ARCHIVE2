import React, { useState, useEffect } from 'react';
import { typeDocService } from '../../services/typeDocService';
import { useApp } from '../../context/AppContext';

export default function TypeDocView() {
  const { themeColor } = useApp();
  const [types, setTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const [name, setName] = useState('');
  const [editingId, setEditingId] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchTypes = async () => {
    try {
      setLoading(true);
      const data = await typeDocService.getAll();
      setTypes(data);
    } catch (err) {
      setError("Impossible de charger les types de documents.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTypes();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    try {
      if (editingId) {
        await typeDocService.update(editingId, { name });
        setSuccess("Type de document mis à jour avec succès.");
      } else {
        await typeDocService.create({ name });
        setSuccess("Type de document créé avec succès.");
      }
      setName('');
      setEditingId(null);
      fetchTypes();
    } catch (err) {
      setError(err.response?.data?.message || "Une erreur est survenue.");
    }
  };

  const handleEdit = (item) => {
    setEditingId(item.id);
    setName(item.name);
  };

  const handleDelete = async (id) => {
    if (window.confirm("Êtes-vous sûr de vouloir supprimer ce type ?")) {
      try {
        await typeDocService.delete(id);
        setSuccess("Type supprimé.");
        fetchTypes();
      } catch (err) {
        setError(err.response?.data?.message || "Erreur lors de la suppression.");
      }
    }
  };

  return (
    <div className="container mt-4">
      <h2 className="mb-4 fw-bold" style={{ color: themeColor }}>Gestion des Types de Documents</h2>

      {error && <div className="alert alert-danger">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="row">
        <div className="col-md-4 mb-4">
          <div className="card shadow-sm p-3">
            <h5 className="card-title mb-3">{editingId ? "Modifier le type" : "Ajouter un type"}</h5>
            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label">Nom</label>
                <input 
                  type="text" 
                  className="form-control" 
                  value={name} 
                  onChange={(e) => setName(e.target.value)} 
                  required 
                />
              </div>
              <div className="d-flex gap-2">
                <button type="submit" className="btn text-white w-100" style={{ backgroundColor: themeColor }}>
                  {editingId ? "Mettre à jour" : "Enregistrer"}
                </button>
                {editingId && (
                  <button type="button" className="btn btn-secondary" onClick={() => { setEditingId(null); setName(''); }}>
                    Annuler
                  </button>
                )}
              </div>
            </form>
          </div>
        </div>

        <div className="col-md-8">
          <div className="card shadow-sm p-3">
            <h5 className="card-title mb-3">Liste des types</h5>
            {loading ? <p>Chargement...</p> : (
              <div className="table-responsive">
                <table className="table table-hover align-middle">
                  <thead className="table-light">
                    <tr>
                      <th>#</th>
                      <th>Nom</th>
                      <th className="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {types.map((item, index) => (
                      <tr key={item.id}>
                        <td>{index + 1}</td>
                        <td className="fw-semibold">{item.name}</td>
                        <td className="text-end">
                          <button className="btn btn-sm btn-outline-primary me-2" onClick={() => handleEdit(item)}>Éditer</button>
                          <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(item.id)}>Supprimer</button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}