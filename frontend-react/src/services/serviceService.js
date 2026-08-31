import api from './api';

export const serviceService = {
  getAll: async () => {
    const response = await api.get('/services');
    return response.data;
  },
  create: async (data) => {
    const response = await api.post('/services', data);
    return response.data;
  },
  update: async (id, data) => {
    const response = await api.put(`/services/${id}`, data);
    return response.data;
  },
  delete: async (id) => {
    const response = await api.delete(`/services/${id}`);
    return response.data;
  }
};