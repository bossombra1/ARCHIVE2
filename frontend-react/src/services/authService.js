import api from './api';

export const authService = {
  login: async (credentials) => {
    const response = await api.post('/login', credentials);
    return response.data;
  },
  
  logout: async () => {
    const response = await api.post('/logout');
    return response.data;
  },

  getMe: async () => {
    const response = await api.get('/me');
    return response.data;
  }
};