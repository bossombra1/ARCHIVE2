import React, { createContext, useContext, useState, useEffect } from 'react';

const ThemeLanguageContext = createContext();

// Dictionnaire de traduction très simple (ou extensible via des fichiers JSON)
const translations = {
  fr: {
    dashboard: "Tableau de bord",
    documents: "Gestion des documents",
    settings: "Paramètres",
    logout: "Se déconnecter",
    welcome: "Bienvenue",
  },
  en: {
    dashboard: "Dashboard",
    documents: "Document Management",
    settings: "Settings",
    logout: "Logout",
    welcome: "Welcome",
  }
};

export const ThemeLanguageProvider = ({ children }) => {
  // 1. Gestion de la Langue (Par défaut 'fr' ou depuis le localStorage)
  const [lang, setLang] = useState(localStorage.getItem('app_lang') || 'fr');

  // 2. Gestion de la Charte / Thème (Couleur primaire, mode, etc.)
  const [primaryColor, setPrimaryColor] = useState(localStorage.getItem('app_color') || '#0d6efd');

  useEffect(() => {
    localStorage.setItem('app_lang', lang);
  }, [lang]);

  useEffect(() => {
    localStorage.setItem('app_color', primaryColor);
    // Application dynamique de la couleur primaire sur les variables CSS globales
    document.documentElement.style.setProperty('--bs-primary', primaryColor);
  }, [primaryColor]);

  // Fonction de traduction simple
  const t = (key) => {
    return translations[lang][key] || key;
  };

  return (
    <ThemeLanguageContext.Provider value={{ lang, setLang, primaryColor, setPrimaryColor, t }}>
      {children}
    </ThemeLanguageContext.Provider>
  );
};

// Hook personnalisé pour utiliser le contexte facilement dans les composants
export const useThemeLanguage = () => useContext(ThemeLanguageContext);