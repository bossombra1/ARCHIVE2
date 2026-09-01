import React from 'react';
import { NavLink } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { userService } from '../services/userService';
import { authCheckService } from '../services/authCheckService';

/**
 * Navbar de l'application.
 *
 * Structure :
 *   - "Tableau de bord" et "Documents" : visibles par TOUS les rôles
 *   - "Paramètres" (menu déroulant) : visible UNIQUEMENT par l'admin
 *     (poste.level === 'admin'). Contient toutes les vues d'administration :
 *     Types de documents, Services, Départements, Directions, Postes,
 *     Utilisateurs, Journaux d'audit.
 *
 * Sécurité : ce masquage est purement UX. La sécurité réelle reste côté
 * Laravel (middleware poste:admin + Policy).
 */
export default function Navbar({ onLogout }) {
  const { user, lang, themeColor, t, updateUserSettings } = useApp();

  // Seul l'Administrateur Système (level = 'admin') voit le menu Paramètres
  const isAdmin = authCheckService.hasLevelSync(user, ['admin']);

  const handleLangChange = async (e) => {
    const newLang = e.target.value;
    updateUserSettings(newLang, null);
    try {
      await userService.updatePreferences({ lang: newLang });
    } catch (err) {
      console.error('Préférence langue non enregistrée', err);
    }
  };

  const handleColorChange = async (e) => {
    const newColor = e.target.value;
    updateUserSettings(null, newColor);
    try {
      await userService.updatePreferences({ theme_color: newColor });
    } catch (err) {
      console.error('Préférence couleur non enregistrée', err);
    }
  };

  const linkClass = ({ isActive }) =>
    'nav-link' + (isActive ? ' active' : '');

  return (
    <nav className="navbar navbar-expand-lg navbar-dark shadow-sm px-3" style={{ backgroundColor: themeColor }}>
      <div className="container-fluid">
        <span className="navbar-brand fw-bold">
          GED - {user?.company?.name || 'Enterprise'}
        </span>

        <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="navbarNav">
          <ul className="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
            {/* Tableau de bord — visible par tous */}
            <li className="nav-item">
              <NavLink to="/dashboard" className={linkClass}>
                <i className="bi bi-speedometer2 me-1"></i> {t('dashboard')}
              </NavLink>
            </li>

            {/* Documents — visible par tous */}
            <li className="nav-item">
              <NavLink to="/documents" className={linkClass}>
                <i className="bi bi-file-earmark-text me-1"></i> {t('documents') || 'Documents'}
              </NavLink>
            </li>

            {/* Menu Paramètres — visible UNIQUEMENT par l'admin */}
            {isAdmin && (
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
                  <li><h6 className="dropdown-header text-uppercase small fw-bold">Documents</h6></li>
                  <li><NavLink className="dropdown-item" to="/document-types"><i className="bi bi-file-earmark-text me-2"></i> Types de documents</NavLink></li>
                  <li><hr className="dropdown-divider" /></li>
                  <li><h6 className="dropdown-header text-uppercase small fw-bold">Structure Entreprise</h6></li>
                  <li><NavLink className="dropdown-item" to="/services"><i className="bi bi-building me-2"></i> Services</NavLink></li>
                  <li><NavLink className="dropdown-item" to="/departments"><i className="bi bi-diagram-3 me-2"></i> Départements</NavLink></li>
                  <li><NavLink className="dropdown-item" to="/directions"><i className="bi bi-compass me-2"></i> Directions</NavLink></li>
                  <li><NavLink className="dropdown-item" to="/postes"><i className="bi bi-person-badge me-2"></i> Postes / Rôles</NavLink></li>
                  <li><hr className="dropdown-divider" /></li>
                  <li><h6 className="dropdown-header text-uppercase small fw-bold">Administration Système</h6></li>
                  <li><NavLink className="dropdown-item" to="/users"><i className="bi bi-people me-2"></i> Utilisateurs & Accès</NavLink></li>
                  <li><NavLink className="dropdown-item" to="/journals"><i className="bi bi-journal-text me-2"></i> Journaux d'audit</NavLink></li>
                </ul>
              </li>
            )}
          </ul>

          {/* Préférences utilisateur — visibles par tous */}
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