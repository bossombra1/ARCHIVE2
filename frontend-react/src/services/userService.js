import api from './api';

export const userService = {
  updatePreferences: async (preferences) => {
    // preferences = { lang: 'en', theme_color: '#ff5733' }
    const response = await api.put('/user/preferences', preferences);
    return response.data;
  }
};