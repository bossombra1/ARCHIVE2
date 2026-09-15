import api from './api';

/**
 * Service frontend pour le forfait (taille d'entreprise + limites).
 * `getUsage` est accessible à tous les users authentifiés ; `updateSize`
 * est réservé à l'Administrateur Système côté backend.
 */
export const planService = {
  getUsage: async () => {
    const response = await api.get('/company/plan-usage');
    return response.data.usage;
  },

  updateSize: async (size) => {
    const response = await api.put('/company/size', { size });
    return response.data;
  },
};

export default planService;