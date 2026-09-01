import api from './api';

/**
 * Service frontend pour l'API documents.
 * Aucune logique de sécurité ici : tout est délégué à Laravel.
 */
export const documentService = {
  getAll: async (params = {}) => {
    const response = await api.get('/documents', { params });
    return response.data;
  },

  getOne: async (id) => {
    const response = await api.get(`/documents/${id}`);
    return response.data.document;
  },

  create: async (formData) => {
    const response = await api.post('/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data.document;
  },

  update: async (id, data) => {
    const response = await api.put(`/documents/${id}`, data);
    return response.data.document;
  },

  delete: async (id) => {
    const response = await api.delete(`/documents/${id}`);
    return response.data;
  },

  /**
   * Téléchargement classique : déclenche un download avec Content-Disposition: attachment.
   */
  download: async (id, fallbackName = 'document') => {
    const response = await api.get(`/documents/${id}/download`, {
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(response.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = fallbackName;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  },

  /**
   * Prévisualisation : demande au backend un Content-Disposition: inline
   * pour que le navigateur affiche le PDF/image dans un iframe au lieu
   * de le télécharger.
   */
  getPreviewUrl: async (id) => {
    const response = await api.get(`/documents/${id}/download?inline=1`, {
      responseType: 'blob',
    });
    return window.URL.createObjectURL(response.data);
  },

  releasePreviewUrl: (url) => {
    if (url) window.URL.revokeObjectURL(url);
  },

  /*
  |--------------------------------------------------------------------------
  | Permissions
  |--------------------------------------------------------------------------
  */

  getPermissions: async (documentId) => {
    const response = await api.get(`/documents/${documentId}/permissions`);
    return response.data.permissions;
  },

  addPermission: async (documentId, payload) => {
    const response = await api.post(`/documents/${documentId}/permissions`, payload);
    return response.data.permission;
  },

  revokePermission: async (permissionId) => {
    const response = await api.delete(`/document-permissions/${permissionId}`);
    return response.data;
  },

  getPermissionTargets: async () => {
    const [usersRes, postesRes, servicesRes] = await Promise.all([
      api.get('/users/minimal'),
      api.get('/postes'),
      api.get('/services'),
    ]);
    return {
      users: usersRes.data,
      postes: postesRes.data,
      services: servicesRes.data,
    };
  },
};

export default documentService;