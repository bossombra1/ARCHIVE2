import React, { useState, useEffect } from 'react';
import { useApp } from '../context/AppContext';
import { userService } from '../services/userService';
import { authCheckService } from '../services/authCheckService';

export default function Navbar({ onLogout }) {
  const { user, lang, themeColor, t, updateUserSettings } = useApp();
  const [isAdminOrManager, setIsAdminOrManager] = useState(false);

  useEffect(() => {
    // Exemple : Vérifier si l'utilisateur est Admin ou Gestionnaire via son poste
    const checkPermissions = async () => {
      // Vous pouvez adapter les libellés de postes selon votre table 'postes'
      const authorized = await authCheckService.hasRoleOrPoste(['Administrateur', 'Manager RH', 'Responsable GED']);
      setIsAdminOrManager(authorized);
    };
    checkPermissions();
  }, [user]);

  const handleLangChange = async (e) => {
    const newLang = e.target.value;
    updateUserSettings(newLang, null);
    await userService.updatePreferences({ lang: newLang });
  };

  const handleColorChange = async (e) => {
    const newColor = e.target.value;
    updateUserSettings(null, newColor);
    await userService.updatePreferences({ theme_color: newColor });
  };

  return (
    <nav className="navbar navbar-expand-lg navbar-dark shadow-sm px-3" style={{ backgroundColor: themeColor }}>
      <div className="container-fluid">
        <span className="navbar-brand fw-bold">GED - {user?.company?.name || 'Enterprise'}</span>
        
        {/* Bouton Hamburger pour Responsive Mobile */}
        <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="navbarNav">
          <ul className="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
            
            {/* Tableau de bord */}
            <li className="nav-item">
              <a className="nav-link active" href="/dashboard">
                <i className="bi bi-speedometer2 me-1"></i> {t('dashboard')}
              </a>
            </li>

            {/* Types de documents */}
            <li className="nav-item">
              <a href="../documents/TypeDoc" className="nav-link">
                <i className="bi bi-file-earmark-text me-1"></i> Types de documents
              </a>
            </li>

            {/* Services */}
            <li className="nav-item">
              <a href="/services" className="nav-link">
                <i className="bi bi-building me-1"></i> Services
              </a>
            </li>

            {/* Menu Paramètres & Tables Secondaires (Affiché selon le poste/rôle) */}
            <li className="nav-item dropdown">
              <a 
                className="nav-link dropdown-toggle" 
                href="#" 
                id="settingsDropdown" 
                role="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
              >
                <i className="bi bi-gear me-1"></i> Paramètres
              </a>
              <ul className="dropdown-menu shadow" aria-labelledby="settingsDropdown">
                <li><h6 className="dropdown-header text-uppercase small fw-bold">Structure Entreprise</h6></li>
                <li><a className="dropdown-item" href="/departments"><i className="bi bi-diagram-3 me-2"></i> Départements</a></li>
                <li><a className="dropdown-item" href="/directions"><i className="bi bi-compass me-2"></i> Directions</a></li>
                <li><a className="dropdown-item" href="/postes"><i className="bi bi-person-badge me-2"></i> Postes / Rôles</a></li>
                <li><hr className="dropdown-divider" /></li>
                <li><h6 className="dropdown-header text-uppercase small fw-bold">Administration Système</h6></li>
                <li><a className="dropdown-item" href="/users"><i className="bi bi-people me-2"></i> Utilisateurs & Accès</a></li>
                <li><a className="dropdown-item" href="/journals"><i className="bi bi-journal-text me-2"></i> Journaux d'audit</a></li>
              </ul>
            </li>

          </ul>

          {/* Paramètres individuels : Langue & Couleur */}
          <div className="d-flex align-items-center gap-3 text-white flex-wrap my-2 my-lg-0">
            <div className="d-flex align-items-center gap-1">
              <small>{t('language')}:</small>
              <select className="form-select form-select-sm" value={lang} onChange={handleLangChange}>
                <option value="fr">FR</option>
                <option value="en">EN</option>
              </select>
            </div>

            <div className="d-flex align-items-center gap-1">
              <small>{t('themeColor')}:</small>
              <input 
                type="color" 
                className="form-control form-control-color form-control-sm" 
                value={themeColor} 
                onChange={handleColorChange} 
                title="Choisir votre couleur"
              />
            </div>

            <span className="ms-2 fw-semibold">{user?.name}</span>
            <button className="btn btn-outline-light btn-sm" onClick={onLogout}>{t('logout')}</button>
          </div>
        </div>
      </div>
    </nav>
  );
}