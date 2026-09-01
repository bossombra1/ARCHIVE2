import React from 'react';
import { Outlet } from 'react-router-dom';
import Navbar from '../components/Navbar';

/**
 * Layout principal authentifié : Navbar + Outlet pour les pages privées.
 */
export default function MainLayout({ onLogout }) {
  return (
    <div className="min-vh-100 bg-light">
      <Navbar onLogout={onLogout} />
      <Outlet />
    </div>
  );
}
