import { defineStore } from 'pinia';
import axios from 'axios';

// Configure Axios
axios.defaults.headers.common['Accept'] = 'application/json';

// Add interceptor for bearer token
axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.user,
    hasRole: (state) => (roles) => {
      if (!state.user || !state.user.roles) return false;
      return state.user.roles.some(role => roles.includes(role.name));
    }
  },
  actions: {
    async fetchUser() {
      this.loading = true;
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.get('/api/user', {
          headers: { 
            Authorization: `Bearer ${token}`,
            Accept: 'application/json'
          }
        });
        this.user = data.user;
      } catch (err) {
        console.error('Fetch User Error:', err.response || err);
        alert('Error cargando usuario: ' + (err.response?.statusText || err.message || 'Error desconocido') + '\nPor favor contacta soporte.');
        this.error = 'Session expired';
        this.user = null;
        localStorage.removeItem('token');
      } finally {
        this.loading = false;
      }
    },
    async setToken(token) {
      localStorage.setItem('token', token);
      await this.fetchUser();
    },
    async updateProfile(profileData) {
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.post('/api/user/profile', profileData, {
          headers: { Authorization: `Bearer ${token}` }
        });
        this.user = data.user;
        return true;
      } catch (err) {
        throw new Error(err.response?.data?.message || 'Error updating profile');
      }
    },
    async logout() {
      try {
        const token = localStorage.getItem('token');
        await axios.post('/api/logout', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
      } catch (e) {
        console.error(e);
      }
      this.user = null;
      localStorage.removeItem('token');
    }
  }
});
