import React, { useState, useEffect } from 'react';
import { setupService } from './services/setupService';
import { authService } from './services/authService';
import { AppProvider, useApp } from './context/AppContext';

import SetupView from './pages/setup/SetupView';
import LoginView from './pages/auth/LoginView';
import DashboardView from './pages/dashboard/DashboardView';
import Navbar from './components/Navbar';

// 🚧 Vue temporaire : prouve que la navigation fonctionne
function PlaceholderView({ viewName, onLogout }) {
  const { themeColor, t, navigate } = useApp();
  return (
    <div className="min-vh-100 bg-light">
      <Navbar onLogout={onLogout} />
      <div className="container py-5">
        <div className="card shadow-sm border-0 p-5 text-center">
          <i className="bi bi-cone-striped display-4" style={{ color: themeColor }}></i>
          <h3 className="fw-bold mt-3">Page « {viewName} » en construction</h3>
          <p className="text-muted">Créez le composant puis branchez-le dans le switch de AppRouter.</p>
          <button className="btn btn-primary" onClick={() => navigate('dashboard')}>
            <i className="bi bi-arrow-left me-1"></i> Retour au tableau de bord
          </button>
        </div>
      </div>
    </div>
  );
}

// Doit être DANS le provider pour lire currentView
function AppRouter({ onLogout }) {
  const { currentView } = useApp();

  switch (currentView) {
    case 'dashboard': return <DashboardView onLogout={onLogout} />;

    // ⬇️ Décommentez au fur et à mesure que vous créez les vues :
    // case 'documents':   return <DocumentsView onLogout={onLogout} />;
    // case 'typeDoc':     return <TypeDocView onLogout={onLogout} />;
    // case 'services':    return <ServicesView onLogout={onLogout} />;
    // case 'departments': return <DepartmentsView onLogout={onLogout} />;
    // case 'directions':  return <DirectionsView onLogout={onLogout} />;
    // case 'postes':      return <PostesView onLogout={onLogout} />;
    // case 'users':       return <UsersView onLogout={onLogout} />;
    // case 'journals':    return <JournalsView onLogout={onLogout} />;

    default: return <PlaceholderView viewName={currentView} onLogout={onLogout} />;
  }
}

export default function App() {
  const [isConfigured, setIsConfigured] = useState(null);
  const [initError, setInitError] = useState(false);
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkAppStatus = async () => {
    try {
      setInitError(false);
      const setupRes = await setupService.checkSetup();
      const configured = setupRes?.is_configured ?? setupRes?.configured;
      setIsConfigured(configured);

      const token = localStorage.getItem('auth_token');
      if (token && configured) {
        const userRes = await authService.getMe();
        setUser(userRes.user);
      }
    } catch (err) {
      console.error('ERREUR dans checkAppStatus :', err);
      setInitError(true);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { checkAppStatus(); }, []);

  const handleLogout = async () => {
    try { await authService.logout(); } catch (e) {}
    localStorage.removeItem('auth_token');
    setUser(null);
  };

  if (loading) {
    return <div className="text-center mt-5"><h3>Chargement de l'application...</h3></div>;
  }

  if (initError) {
    return (
      <div className="container text-center mt-5">
        <div className="alert alert-danger">Impossible de contacter le serveur.</div>
        <button className="btn btn-primary" onClick={() => { setLoading(true); checkAppStatus(); }}>Réessayer</button>
      </div>
    );
  }

  if (!isConfigured) {
    return <SetupView onSetupComplete={() => setIsConfigured(true)} />;
  }

  if (!user) {
    return <LoginView onLoginSuccess={(userData) => setUser(userData)} />;
  }

  // ✅ AppRouter (et PAS DashboardView en direct)
  return (
    <AppProvider initialUser={user}>
      <AppRouter onLogout={handleLogout} />
    </AppProvider>
  );
}