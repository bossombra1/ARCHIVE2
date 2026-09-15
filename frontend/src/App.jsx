import React, { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { setupService } from './services/setupService';
import { authService } from './services/authService';
import { AppProvider } from './context/AppContext';
import 'bootstrap-icons/font/bootstrap-icons.css';

import SetupView from './pages/setup/SetupView';
import LoginView from './pages/auth/LoginView';
import DashboardView from './pages/dashboard/DashboardView';
import DocumentView from './pages/documents/DocumentView';
import TypeDocView from './pages/documents/TypeDocView';
import ServiceView from './pages/company/ServiceView';
import DirectionsView from './pages/company/DirectionsView';
import DepartmentsView from './pages/company/DepartmentsView';
import PostesView from './pages/company/PostesView';
import UsersView from './pages/company/UsersView';
import DocumentGrantsView from './pages/company/DocumentGrantsView';
import PlanView from './pages/company/PlanView';
import JournalsView from './pages/company/JournalsView';
import MainLayout from './components/MainLayout';

/**
 * Racine de l'application.
 *
 * Workflow :
 *   1. Au mount, on vérifie si l'app est configurée et si un token est présent.
 *   2. Selon l'état, on affiche SetupView / LoginView / pages authentifiées.
 *   3. Une fois authentifié, AppProvider fournit le user + i18n à toute l'app.
 *   4. React Router gère la navigation (Navbar utilise NavLink).
 */
export default function App() {
  const [isConfigured, setIsConfigured] = useState(null);
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkAppStatus = async () => {
    try {
      const setupRes = await setupService.checkSetup();
      // Compatible avec les deux clés : is_configured (nouveau) et configured (ancien)
      const configured = setupRes.is_configured ?? setupRes.configured ?? false;
      setIsConfigured(Boolean(configured));

      const token = localStorage.getItem('auth_token');
      if (token && configured) {
        try {
          const userRes = await authService.getMe();
          setUser(userRes.user);
        } catch (err) {
          // Token invalide ou expiré : on nettoie
          localStorage.removeItem('auth_token');
        }
      }
    } catch (err) {
      console.error('Erreur lors de la vérification du setup :', err);
      setIsConfigured(false);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkAppStatus();
  }, []);

  const handleLogout = async () => {
    try {
      await authService.logout();
    } catch (e) {
      // ignore : on nettoie le token local de toute façon
    }
    localStorage.removeItem('auth_token');
    setUser(null);
  };

  if (loading) {
    return <div className="text-center mt-5"><h3>Chargement de l'application...</h3></div>;
  }

  if (!isConfigured) {
    return <SetupView onSetupComplete={() => setIsConfigured(true)} />;
  }

  if (!user) {
    return <LoginView onLoginSuccess={(userData) => setUser(userData)} />;
  }

  return (
    <AppProvider initialUser={user}>
      <BrowserRouter>
        <Routes>
          <Route element={<MainLayout onLogout={handleLogout} />}>
            <Route path="/dashboard" element={<DashboardView />} />
            <Route path="/documents" element={<DocumentView />} />
            <Route path="/document-types" element={<TypeDocView />} />
            <Route path="/services" element={<ServiceView />} />
            <Route path="/departments" element={<DepartmentsView />} />
            <Route path="/directions" element={<DirectionsView />} />
            <Route path="/postes" element={<PostesView />} />
            <Route path="/users" element={<UsersView />} />
            <Route path="/document-grants" element={<DocumentGrantsView />} />
            <Route path="/plan" element={<PlanView />} />
            <Route path="/journals" element={<JournalsView />} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AppProvider>
  );
}