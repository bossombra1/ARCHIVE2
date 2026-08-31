import React, { useState, useEffect } from 'react';
import { setupService } from './services/setupService';
import { authService } from './services/authService';
import { AppProvider } from './context/AppContext'; // <-- Importez le provider

import SetupView from './pages/setup/SetupView';
import LoginView from './pages/auth/LoginView';
import DashboardView from './pages/dashboard/DashboardView';

export default function App() {
  const [isConfigured, setIsConfigured] = useState(null);
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkAppStatus = async () => {
    try {
      const setupRes = await setupService.checkSetup();
      setIsConfigured(setupRes.is_configured);

      const token = localStorage.getItem('auth_token');
      if (token && setupRes.is_configured) {
        const userRes = await authService.getMe();
        setUser(userRes.user);
      }
    } catch (err) {
      console.error(err);
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
    } catch (e) {}
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

  // ENCAPSULATION DANS LE PROVIDER ICI :
  return (
    <AppProvider initialUser={user}>
      <DashboardView onLogout={handleLogout} />
    </AppProvider>
  );
}