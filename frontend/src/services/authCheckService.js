import api from './api';

export const authCheckService = {
  // Vérifie si l'utilisateur possède un poste spécifique (ex: 'Administrateur', 'Manager')
  hasRoleOrPoste: async (allowedPostes = []) => {
    try {
      const response = await api.get('/user/current-affectation'); // Route Laravel renvoyant le poste/affectation actif
      const userPoste = response.data?.poste?.name;
      
      if (!userPoste) return false;
      return allowedPostes.includes(userPoste);
    } catch (err) {
      console.error("Erreur lors de la vérification du poste", err);
      return false;
    }
  },

  // Vérification synchrone basée sur les données déjà chargées dans le state utilisateur global (si disponible)
  checkUserPoste: (user, allowedPostes = []) => {
    if (!user || !user.affectation) return false;
    return allowedPostes.includes(user.affectation.poste?.name);
  }
};