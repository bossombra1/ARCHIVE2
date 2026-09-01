import axios from 'axios';

// URL de base de l'API.
// - En dev local : VITE_API_URL dans .env, sinon fallback http://127.0.0.1:8000/api
// - En prod : VITE_API_URL obligatoire.
const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
});

// Intercepteur de requête : injecte le token Bearer Sanctum si présent.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Intercepteur de réponse : si 401, on nettoie le token local
// (le state user est rafraîchi par App.jsx au prochain mount).
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      // Ne pas supprimer le token sur un simple 401 ponctuel : on peut être
      // sur une route publique. On ne supprime que si l'URL n'est pas /login.
      const url = error?.config?.url || '';
      if (!url.includes('/login') && !url.includes('/setup/check')) {
        localStorage.removeItem('auth_token');
      }
    }
    return Promise.reject(error);
  }
);

export default api;
