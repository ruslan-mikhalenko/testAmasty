import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    tickets: [],
    meta: { total: 0, page: 1, perPage: 10, pages: 1 },
    filters: { status: '', search: '' },
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
        ...this.filters,
        ...params,
      };
      try {
        const { data } = await api.get('tickets', { params: { ...query, scope: 'all' } });
        this.tickets = data.data;
        this.meta = data.meta;
      } finally {
        this.loading = false;
      }
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
