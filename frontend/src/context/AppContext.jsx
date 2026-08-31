import React, { createContext, useContext, useState, useEffect } from 'react';

const AppContext = createContext();

const translations = {
  fr: {
    dashboard: "Tableau de bord",
    documents: "Documents",
    users: "Utilisateurs",
    logout: "Se déconnecter",
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
  const [currentView, setCurrentView] = useState('dashboard');

  useEffect(() => {
    document.documentElement.style.setProperty('--bs-primary', themeColor);
    document.documentElement.style.setProperty('--secondary-color', themeColor);
  }, [themeColor]);

  const t = (key) => translations[lang]?.[key] || translations['fr'][key] || key;

  const updateUserSettings = (newLang, newColor) => {
    if (newLang) setLang(newLang);
    if (newColor) setThemeColor(newColor);
  };

  // 🔑 LA FONCTION MANQUANTE : navigation sans rechargement
  const navigate = (view) => {
    setCurrentView(view);
    window.scrollTo(0, 0);
  };

  return (
    <AppContext.Provider value={{ user, lang, themeColor, t, updateUserSettings, currentView, navigate }}>
      {children}
    </AppContext.Provider>
  );
};

export const useApp = () => useContext(AppContext);