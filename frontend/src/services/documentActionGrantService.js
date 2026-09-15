import api from './api';

/**
 * Service frontend pour les droits d'action documentaire (modifier /
 * supprimer / ajouter) accordés par l'Administrateur Système à un ou
 * plusieurs utilisateurs. Réservé aux comptes poste.level = 'admin'
 * côté backend (middleware poste:admin) — ce service ne fait aucune
 * vérification, tout est délégué à Laravel.
 */
export const documentActionGrantService = {
  getAll: async () => {
    const response = await api.get('/document-action-grants');
    return response.data.grants;
  },

  /**
   * Accorde les mêmes droits à un ou plusieurs utilisateurs en une seule
   * requête (bulk). payload = {
   *   user_ids: number[], can_modify, can_delete, can_add: boolean,
   *   scope: 'all' | 'specific', document_ids?: number[],
   *   expires_at?: string | null,
   * }
   */
  grant: async (payload) => {
    const response = await api.post('/document-action-grants', payload);
    return response.data.grants;
  },

  revoke: async (id) => {
    const response = await api.delete(`/document-action-grants/${id}`);
    return response.data;
  },
};

export default documentActionGrantService;