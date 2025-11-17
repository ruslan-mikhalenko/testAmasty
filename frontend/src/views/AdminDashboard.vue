<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminStore } from '@/stores/admin';

const admin = useAdminStore();
const filters = reactive({ status: '', search: '', dateFrom: '', dateTo: '' });
const selectedTicket = ref(null);
const edit = reactive({ status_id: null, tags: [] });
const replyMessage = ref('');
const panelLoading = ref(false);

const applyFilters = async () => {
  try {
    admin.setFilter('status', filters.status || '');
    admin.setFilter('search', filters.search || '');
    admin.setFilter('dateFrom', filters.dateFrom || '');
    admin.setFilter('dateTo', filters.dateTo || '');
    await admin.fetchTickets({ page: 1 });
  } catch (error) {
    console.error('Ошибка применения фильтров:', error);
  }
};

const sortBy = (field) => {
  admin.toggleSort(field);
  applyFilters();
};

const currentSort = computed(() => {
  const [field, dir] = admin.sort.split(':');
  return { field, direction: dir };
});

const getSortIcon = (field) => {
  if (currentSort.value.field !== field) return '↕️';
  return currentSort.value.direction === 'asc' ? '↑' : '↓';
};

const openTicket = async (ticketId) => {
  panelLoading.value = true;
  try {
    const ticket = await admin.fetchTicket(ticketId);
    selectedTicket.value = ticket;
    edit.status_id = ticket.status_id;
    edit.tags = ticket.tags?.map((tag) => tag.id) ?? [];
  } finally {
    panelLoading.value = false;
  }
};

const saveTicket = async () => {
  await admin.updateTicket(selectedTicket.value.id, { status_id: edit.status_id, tags: edit.tags });
  await openTicket(selectedTicket.value.id);
};

const sendReply = async () => {
  if (!replyMessage.value.trim()) return;
  await admin.reply(selectedTicket.value.id, replyMessage.value);
  replyMessage.value = '';
  await openTicket(selectedTicket.value.id);
};

onMounted(() => {
  admin.bootstrap();
});
</script>

<template>
  <section class="card">
    <header class="section-header">
      <div>
        <h1>Админ панель</h1>
        <p class="muted">Управление обращениями клиентов</p>
      </div>
    </header>

    <div class="filters">
      <select v-model="filters.status" class="input" @change="applyFilters">
        <option value="">Все статусы</option>
        <option v-for="status in admin.statuses" :key="status.id" :value="status.id">
          {{ status.name }}
        </option>
      </select>
      <input v-model="filters.search" class="input" placeholder="Поиск по названию/описанию" @keyup.enter="applyFilters" />
      <input v-model="filters.dateFrom" type="date" class="input" placeholder="С даты" @change="applyFilters" />
      <input v-model="filters.dateTo" type="date" class="input" placeholder="По дату" @change="applyFilters" />
      <button class="button secondary" @click="applyFilters">Применить</button>
      <button class="button secondary" @click="() => { filters.status = ''; filters.search = ''; filters.dateFrom = ''; filters.dateTo = ''; applyFilters(); }">Сбросить</button>
    </div>

    <div v-if="admin.loading" class="loading">Загрузка...</div>
    <div v-else class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th class="sortable" @click="sortBy('id')">
              ID {{ getSortIcon('id') }}
            </th>
            <th>Клиент</th>
            <th class="sortable" @click="sortBy('status_id')">
              Статус {{ getSortIcon('status_id') }}
            </th>
            <th>Теги</th>
            <th class="sortable" @click="sortBy('updated_at')">
              Обновлено {{ getSortIcon('updated_at') }}
            </th>
            <th class="sortable" @click="sortBy('created_at')">
              Создано {{ getSortIcon('created_at') }}
            </th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ticket in admin.tickets" :key="ticket.id">
            <td>#{{ ticket.id }}</td>
            <td>{{ ticket.user_email }}</td>
            <td>
              <span class="badge" :style="{ background: 'rgba(37,99,235,0.1)', color: '#1d4ed8' }">
                {{ ticket.status_name }}
              </span>
            </td>
            <td>
              <span v-for="tag in ticket.tags" :key="tag.id" class="badge" :style="{ background: tag.color, color: '#fff' }">
                {{ tag.name }}
              </span>
            </td>
            <td>{{ new Date(ticket.updated_at).toLocaleString() }}</td>
            <td>{{ new Date(ticket.created_at).toLocaleString() }}</td>
            <td>
              <button class="button secondary" @click="openTicket(ticket.id)">Открыть</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section v-if="selectedTicket" class="card">
    <header class="section-header">
      <div>
        <h2>Обращение #{{ selectedTicket.id }}</h2>
        <p class="muted">{{ selectedTicket.title }}</p>
      </div>
      <button class="button secondary" @click="selectedTicket = null">Закрыть</button>
    </header>

    <div v-if="panelLoading">Загрузка...</div>
    <div v-else class="panel-grid">
      <div class="panel-column">
        <label>
          Статус
          <select v-model="edit.status_id" class="input">
            <option v-for="status in admin.statuses" :key="status.id" :value="status.id">
              {{ status.name }}
            </option>
          </select>
        </label>
        <label>
          Теги
          <select v-model="edit.tags" class="input" multiple>
            <option v-for="tag in admin.tags" :key="tag.id" :value="tag.id">
              {{ tag.name }}
            </option>
          </select>
        </label>
        <button class="button" @click="saveTicket">Сохранить</button>
      </div>

      <div class="panel-column">
        <label>
          Ответ клиенту
          <textarea v-model="replyMessage" class="input" rows="4" placeholder="Сообщение"></textarea>
        </label>
        <button class="button" @click="sendReply" :disabled="!replyMessage.trim()">Отправить</button>
        <div class="replies">
          <article v-for="reply in selectedTicket.replies" :key="reply.id" class="reply">
            <header>{{ reply.admin_email }}</header>
            <p>{{ reply.body }}</p>
            <small class="muted">{{ new Date(reply.created_at).toLocaleString() }}</small>
          </article>
          <p v-if="!selectedTicket.replies?.length" class="muted">Ответов пока нет</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.filters {
  margin: 24px 0;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.loading {
  padding: 24px;
  text-align: center;
  color: #64748b;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background-color: rgba(37, 99, 235, 0.05);
}

.table-wrapper {
  overflow-x: auto;
}

.panel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.panel-column {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.reply {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 12px;
}
</style>
