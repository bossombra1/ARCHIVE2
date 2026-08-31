import api from './api';

export const dashboardService = {
  // Récupérer les statistiques globales et dynamiques de l'entreprise
  getStats: async () => {
    try {
      const response = await api.get('/dashboard/stats');
      return response.data;
    } catch (err) {
      console.error("Erreur lors de la récupération des statistiques du dashboard", err);
      throw err;
    }
  }
};