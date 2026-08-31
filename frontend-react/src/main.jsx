import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.jsx';
import { ThemeLanguageProvider } from './context/ThemeLanguageContext';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <ThemeLanguageProvider>
      <App />
    </ThemeLanguageProvider>
  </React.StrictMode>,
);