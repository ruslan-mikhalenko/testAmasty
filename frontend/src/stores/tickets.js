import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTicketsStore = defineStore('tickets', {
  state: () => ({
    items: [],
    meta: { total: 0, page: 1, pages: 1, perPage: 10 },
    filters: { status: '', search: '', dateFrom: '', dateTo: '' },
    sort: 'created_at:desc',
    loading: false,
  }),
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      const query = {
        page: this.meta.page,
        perPage: this.meta.perPage,
        sort: this.sort,
        ...this.filters,
        ...params,
      };
      
      // Удаляем пустые строки из параметров запроса
      Object.keys(query).forEach(key => {
        if (query[key] === '' || query[key] === null || query[key] === undefined) {
          delete query[key];
        }
      });
      
      try {
        const { data } = await api.get('tickets', { params: query });
        this.items = data.data;
        this.meta = data.meta;
      } catch (error) {
        console.error('Ошибка загрузки задач:', error.response?.data?.errors?.[0] || error.message);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    setSort(field, direction = 'desc') {
      this.sort = `${field}:${direction}`;
    },
    toggleSort(field) {
      const [currentField, currentDir] = this.sort.split(':');
      if (currentField === field) {
        this.sort = `${field}:${currentDir === 'asc' ? 'desc' : 'asc'}`;
      } else {
        this.sort = `${field}:desc`;
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
