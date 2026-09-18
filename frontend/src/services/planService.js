import api from './api';

/**
 * Service frontend pour le forfait (taille d'entreprise + limites).
 * `getUsage` est accessible à tous les users authentifiés.
 *
 * Le changement de forfait n'est PLUS direct : l'Administrateur Système
 * soumet une DEMANDE (requestPlanChange), validée manuellement hors
 * application (en base ou via `php artisan plan:process`), puis appliquée
 * depuis l'interface via applyPlanChange (endpoint qui refuse toute
 * demande non approuvée). La sécurité réelle reste côté Laravel.
 */
export const planService = {
  getUsage: async () => {
    const response = await api.get('/company/plan-usage');
    return response.data.usage;
  },

  /** Dernière demande non clôturée (pending/approved), ou null. */
  getPlanChangeRequest: async () => {
    const response = await api.get('/company/plan-change-request');
    return response.data.request;
  },

  /** Soumettre une demande de changement de forfait (admin uniquement). */
  requestPlanChange: async (requestedSize) => {
    const response = await api.post('/company/plan-change-request', { requested_size: requestedSize });
    return response.data;
  },

  /** Appliquer une demande approuvée manuellement (admin uniquement). */
  applyPlanChange: async () => {
    const response = await api.post('/company/plan-change-request/apply');
    return response.data;
  },
};

export default planService;
