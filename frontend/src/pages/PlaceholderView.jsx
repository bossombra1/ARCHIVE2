import React from 'react';

/**
 * Page placeholder pour les routes futures (départements, directions, etc.).
 * Sera remplacée par une vraie vue quand le contrôleur backend correspondant
 * sera implémenté (lot 7).
 */
export default function PlaceholderView({ title }) {
  return (
    <div className="container py-5">
      <h2 className="mb-4">{title}</h2>
      <div className="alert alert-info" role="alert">
        Cette page sera connectée à l'API dans un lot ultérieur.
        La fonctionnalité cœur (Documents) est priorisée.
      </div>
    </div>
  );
}
