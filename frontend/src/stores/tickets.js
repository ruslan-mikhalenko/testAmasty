import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTicketsStore = defineStore('tickets', {
  state: () => ({
    items: [],
    meta: { total: 0, page: 1, pages: 1, perPage: 10 },
    filters: { status: '', search: '' },
    loading: false,
  }),
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      const query = {
        page: this.meta.page,
        perPage: this.meta.perPage,
        ...this.filters,
        ...params,
      };
      try {
        const { data } = await api.get('tickets', { params: query });
        this.items = data.data;
        this.meta = data.meta;
      } finally {
        this.loading = false;
      }
    },
    setFilter(key, value) {
      this.filters[key] = value;
    },
    setPage(page) {
      this.meta.page = page;
    },
    async create(payload) {
      const { data } = await api.post('tickets', payload);
      await this.fetch({ page: 1 });
      return data.data;
    },
    async show(id) {
      const { data } = await api.get(`tickets/${id}`);
      return data.data;
    },
  },
});
