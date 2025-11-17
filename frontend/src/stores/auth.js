import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
    isBootstrapped: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.user),
  },
  actions: {
    async bootstrap() {
      try {
        this.loading = true;
        const { data } = await api.get('auth/me').catch(() => ({ data: { data: null } }));
        this.user = data?.data ?? null;
      } finally {
        this.loading = false;
        this.isBootstrapped = true;
      }
    },
    async login(payload) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.post('auth/login', payload);
        this.user = data.data;
        return this.user;
      } catch (error) {
        this.error = error.response?.data?.errors?.[0] ?? 'Ошибка авторизации';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async register(payload) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.post('auth/register', payload);
        this.user = data.data;
        return this.user;
      } catch (error) {
        this.error = error.response?.data?.errors?.[0] ?? 'Ошибка регистрации';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      await api.post('auth/logout').catch(() => {});
      this.user = null;
    },
  },
});
