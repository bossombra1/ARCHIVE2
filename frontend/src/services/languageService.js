const translations = {
  fr: {
    appName: "GED - Gestion Électronique de Documents",
    loginTitle: "Connexion",
    emailLabel: "Adresse Email",
    passwordLabel: "Mot de passe",
    loginBtn: "Se connecter",
    logoutBtn: "Déconnexion",
    welcome: "Bienvenue",
    totalDocs: "Total Documents",
    activeUsers: "Utilisateurs Actifs",
    trash: "Corbeille",
    setupTitle: "Configuration Initiale de la GED",
    companyName: "Nom de l'entreprise",
    companySize: "Taille de l'entreprise",
    adminName: "Nom de l'administrateur",
    saveSetup: "Enregistrer et initialiser",
    errorGeneric: "Une erreur est survenue.",
    invalidCredentials: "Identifiants invalides."
  },
  en: {
    appName: "EDMS - Electronic Document Management System",
    loginTitle: "Login",
    emailLabel: "Email Address",
    passwordLabel: "Password",
    loginBtn: "Sign In",
    logoutBtn: "Logout",
    welcome: "Welcome",
    totalDocs: "Total Documents",
    activeUsers: "Active Users",
    trash: "Trash",
    setupTitle: "Initial EDMS Setup",
    companyName: "Company Name",
    companySize: "Company Size",
    adminName: "Administrator Name",
    saveSetup: "Save and Initialize",
    errorGeneric: "An error occurred.",
    invalidCredentials: "Invalid credentials."
  }
};

export const languageService = {
  getLanguage: () => {
    return localStorage.getItem('app_lang') || 'fr';
  },
  setLanguage: (lang) => {
    localStorage.setItem('app_lang', lang);
  },
  t: (key) => {
    const lang = languageService.getLanguage();
    return translations[lang][key] || translations['fr'][key] || key;
  }
};