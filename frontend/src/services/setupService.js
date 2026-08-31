import api from './api';

export const setupService = {
  checkSetup: async () => {
    const response = await api.get('/setup/check');
    return response.data;
  },

  storeSetup: async (formData) => {
    const response = await api.post('/setup/store', formData);
    return response.data;
  }
};