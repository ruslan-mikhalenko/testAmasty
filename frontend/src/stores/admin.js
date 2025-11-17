import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    tickets: [],
    meta: { total: 0, page: 1, perPage: 10, pages: 1 },
    filters: { status: '', search: '', dateFrom: '', dateTo: '' },
    sort: 'created_at:desc',
    statuses: [],
    tags: [],
    loading: false,
  }),
  actions: {
    async bootstrap() {
      await Promise.all([this.fetchStatuses(), this.fetchTags(), this.fetchTickets()]);
    },
    async fetchTickets(params = {}) {
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
        const { data } = await api.get('tickets', { params: { ...query, scope: 'all' } });
        this.tickets = data.data;
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
    async fetchStatuses() {
      const { data } = await api.get('statuses');
      this.statuses = data.data;
    },
    async fetchTags() {
      const { data } = await api.get('tags');
      this.tags = data.data;
    },
    async fetchTicket(id) {
      const { data } = await api.get(`tickets/${id}`);
      return data.data;
    },
    async updateTicket(id, payload) {
      const { data } = await api.patch(`tickets/${id}`, payload);
      await this.fetchTickets();
      return data.data;
    },
    async reply(ticketId, message) {
      const { data } = await api.post(`tickets/${ticketId}/reply`, { message });
      return data.data;
    },
    async upsertTag(payload) {
      if (payload.id) {
        const { data } = await api.put(`tags/${payload.id}`, payload);
        await this.fetchTags();
        return data.data;
      }
      const { data } = await api.post('tags', payload);
      await this.fetchTags();
      return data.data;
    },
    async deleteTag(id) {
      await api.delete(`tags/${id}`);
      await this.fetchTags();
    },
    async upsertStatus(payload) {
      if (payload.id) {
        const { data } = await api.put(`statuses/${payload.id}`, payload);
        await this.fetchStatuses();
        return data.data;
      }
      const { data } = await api.post('statuses', payload);
      await this.fetchStatuses();
      return data.data;
    },
    async deleteStatus(id) {
      await api.delete(`statuses/${id}`);
      await this.fetchStatuses();
    },
  },
});
