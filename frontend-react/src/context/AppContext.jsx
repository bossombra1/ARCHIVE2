import React, { createContext, useContext, useState, useEffect } from 'react';

const AppContext = createContext();

const translations = {
  fr: {
    dashboard: "Tableau de bord",
    documents: "Documents",
    users: "Utilisateurs",
    logout: "Se déconnecter",
    welcome: "Bienvenue",
    settings: "Paramètres",
    language: "Langue",
    themeColor: "Couleur de thème",
    save: "Enregistrer"
  },
  en: {
    dashboard: "Dashboard",
    documents: "Documents",
    users: "Users",
    logout: "Logout",
    welcome: "Welcome",
    settings: "Settings",
    language: "Language",
    themeColor: "Theme Color",
    save: "Save"
  }
};

export const AppProvider = ({ children, initialUser }) => {
  const [user, setUser] = useState(initialUser);
  const [lang, setLang] = useState(initialUser?.lang || 'fr');
  const [themeColor, setThemeColor] = useState(initialUser?.theme_color || '#2563eb');

  // Appliquer la couleur dynamiquement sur les variables CSS globales du navigateur
  useEffect(() => {
    document.documentElement.style.setProperty('--bs-primary', themeColor);
    document.documentElement.style.setProperty('--secondary-color', themeColor);
  }, [themeColor]);

  // Fonction de traduction globale accessible dans tous les CRUDs
  const t = (key) => {
    return translations[lang]?.[key] || translations['fr'][key] || key;
  };

  const updateUserSettings = (newLang, newColor) => {
    if (newLang) setLang(newLang);
    if (newColor) setThemeColor(newColor);
  };

  return (
    <AppContext.Provider value={{ user, lang, themeColor, t, updateUserSettings }}>
      {children}
    </AppContext.Provider>
  );
};

export const useApp = () => useContext(AppContext);