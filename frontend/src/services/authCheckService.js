import api from './api';

/**
 * Service de vérification des postes côté frontend.
 *
 * IMPORTANT : la référence pour les règles métier est `poste.level`
 * (ex : 'admin', 'dg', 'directeur', 'responsable_departement',
 * 'chef_service', 'employe', 'agent_temporaire').
 *
 * La vérification côté frontend n'est qu'une amélioration UX : la sécurité
 * réelle est garantie par Laravel (middleware + Policy). Ne jamais faire
 * confiance au state frontend pour autoriser une action.
 */
export const authCheckService = {
  /**
   * Vérifie si l'utilisateur possède un poste.level parmi la liste autorisée.
   * @param {string[]} allowedLevels ex : ['admin', 'dg']
   * @returns {Promise<boolean>}
   */
  hasLevel: async (allowedLevels = []) => {
    try {
      const response = await api.get('/user/current-affectation');
      const level = response.data?.poste?.level;
      if (!level) return false;
      return allowedLevels.includes(level);
    } catch (err) {
      console.error("Erreur lors de la vérification du poste", err);
      return false;
    }
  },

  /**
   * Variante synchrone basée sur l'objet user déjà chargé.
   * @param {object} user objet user retourné par /me
   * @param {string[]} allowedLevels ex : ['admin', 'dg']
   */
  hasLevelSync: (user, allowedLevels = []) => {
    if (!user || !user.affectation) return false;
    return allowedLevels.includes(user.affectation?.poste?.level);
  },

  /**
   * Raccourcis par niveau.
   */
  isAdmin: (user) => authCheckService.hasLevelSync(user, ['admin']),
  isDG: (user) => authCheckService.hasLevelSync(user, ['dg']),
  isDirector: (user) => authCheckService.hasLevelSync(user, ['directeur']),
  isRespDept: (user) => authCheckService.hasLevelSync(user, ['responsable_departement']),
  isChefService: (user) => authCheckService.hasLevelSync(user, ['chef_service']),
  isEmploye: (user) => authCheckService.hasLevelSync(user, ['employe']),
  isAgent: (user) => authCheckService.hasLevelSync(user, ['agent_temporaire']),

  /**
   * Peut accorder des permissions.
   */
  canGrantPermissions: (user) =>
    authCheckService.hasLevelSync(user, [
      'admin', 'dg', 'directeur', 'responsable_departement', 'chef_service',
    ]),
};
