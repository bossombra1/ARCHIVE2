import api from './api';

/**
 * Service pour les vues d'administration (menu Paramètres).
 */
export const adminService = {
  // ---- Directions ----
  getDirections: async () => (await api.get('/directions')).data,
  createDirection: async (name) => (await api.post('/directions', { name })).data,
  updateDirection: async (id, name) => (await api.put(`/directions/${id}`, { name })).data,
  deleteDirection: async (id) => (await api.delete(`/directions/${id}`)).data,

  // ---- Departments ----
  getDepartments: async () => (await api.get('/departments')).data,
  createDepartment: async (data) => (await api.post('/departments', data)).data,
  updateDepartment: async (id, data) => (await api.put(`/departments/${id}`, data)).data,
  deleteDepartment: async (id) => (await api.delete(`/departments/${id}`)).data,

  // ---- Postes ----
  getPostes: async () => (await api.get('/postes')).data,

  // ---- Users ----
  getUsers: async (params = {}) => (await api.get('/users', { params })).data,
  getUser: async (id) => (await api.get(`/users/${id}`)).data,
  createUser: async (data) => (await api.post('/users', data)).data,
  updateUser: async (id, data) => (await api.put(`/users/${id}`, data)).data,
  deleteUser: async (id) => (await api.delete(`/users/${id}`)).data,
  resetUserPassword: async (id, password) => (await api.post(`/users/${id}/reset-password`, { password })).data,
  updateUserAffectation: async (id, data) => (await api.put(`/users/${id}/affectation`, data)).data,
  reactivateUser: async (id) => (await api.post(`/users/${id}/reactivate`)).data,

  // ---- Journaux ----
  getJournals: async (params = {}) => (await api.get('/journals', { params })).data,
};

export default adminService;