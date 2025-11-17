<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useTicketsStore } from '@/stores/tickets';
import api from '@/services/api';

const tickets = useTicketsStore();
const statuses = ref([]);
const showForm = ref(false);
const selectedTicket = ref(null);
const loadingTicket = ref(false);
const creation = reactive({ title: '', description: '' });
const filters = reactive({ status: '', search: '', dateFrom: '', dateTo: '' });

const loadStatuses = async () => {
  const { data } = await api.get('statuses');
  statuses.value = data.data;
};

const loadTickets = async () => {
  try {
    tickets.setFilter('status', filters.status || '');
    tickets.setFilter('search', filters.search || '');
    tickets.setFilter('dateFrom', filters.dateFrom || '');
    tickets.setFilter('dateTo', filters.dateTo || '');
    await tickets.fetch({ page: 1 });
  } catch (error) {
    console.error('Ошибка загрузки задач:', error);
  }
};

const sortBy = (field) => {
  tickets.toggleSort(field);
  loadTickets();
};

const currentSort = computed(() => {
  const [field, dir] = tickets.sort.split(':');
  return { field, direction: dir };
});

const getSortIcon = (field) => {
  if (currentSort.value.field !== field) return '↕️';
  return currentSort.value.direction === 'asc' ? '↑' : '↓';
};

const openTicket = async (ticketId) => {
  loadingTicket.value = true;
  try {
    selectedTicket.value = await tickets.show(ticketId);
  } finally {
    loadingTicket.value = false;
  }
};

const submitTicket = async () => {
  try {
    await tickets.create(creation);
    creation.title = '';
    creation.description = '';
    showForm.value = false;
  } catch (error) {
    console.error('Ошибка создания задачи:', error);
  }
};

onMounted(async () => {
  await Promise.all([loadStatuses(), loadTickets()]);
});
</script>

<template>
  <section class="card">
    <header class="section-header">
      <div>
        <h1>Мои обращения</h1>
        <p class="muted">Создавайте новые задачи и следите за статусом</p>
      </div>
      <button class="button" @click="showForm = !showForm">
        {{ showForm ? 'Закрыть форму' : 'Новое обращение' }}
      </button>
    </header>

    <div v-if="showForm" class="creation-form">
      <label>
        Заголовок
        <input v-model="creation.title" class="input" placeholder="Краткое описание проблемы" />
      </label>
      <label>
        Описание
        <textarea v-model="creation.description" class="input" rows="4" placeholder="Полное описание"></textarea>
      </label>
      <button class="button" @click="submitTicket">Создать</button>
    </div>

    <div class="filters">
      <select v-model="filters.status" class="input" @change="loadTickets">
        <option value="">Все статусы</option>
        <option v-for="status in statuses" :key="status.id" :value="status.id">
          {{ status.name }}
        </option>
      </select>
      <input v-model="filters.search" class="input" placeholder="Поиск по названию/описанию" @keyup.enter="loadTickets" />
      <input v-model="filters.dateFrom" type="date" class="input" placeholder="С даты" @change="loadTickets" />
      <input v-model="filters.dateTo" type="date" class="input" placeholder="По дату" @change="loadTickets" />
      <button class="button secondary" @click="loadTickets">Применить</button>
      <button class="button secondary" @click="() => { filters.status = ''; filters.search = ''; filters.dateFrom = ''; filters.dateTo = ''; loadTickets(); }">Сбросить</button>
    </div>

    <div v-if="tickets.loading" class="loading">Загрузка...</div>
    <div v-else class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th class="sortable" @click="sortBy('id')">
              ID {{ getSortIcon('id') }}
            </th>
            <th class="sortable" @click="sortBy('created_at')">
              Создано {{ getSortIcon('created_at') }}
            </th>
            <th class="sortable" @click="sortBy('updated_at')">
              Обновлено {{ getSortIcon('updated_at') }}
            </th>
            <th class="sortable" @click="sortBy('status_id')">
              Статус {{ getSortIcon('status_id') }}
            </th>
            <th>Описание</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in tickets.items" :key="item.id">
            <td>#{{ item.id }}</td>
            <td>{{ new Date(item.created_at).toLocaleString() }}</td>
            <td>{{ new Date(item.updated_at).toLocaleString() }}</td>
            <td>
              <span class="badge" :style="{ background: 'rgba(37,99,235,0.1)', color: '#1d4ed8' }">
                {{ item.status_name }}
              </span>
            </td>
            <td>{{ item.description }}</td>
            <td>
              <button class="button secondary" @click="openTicket(item.id)">Подробнее</button>
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

    <div class="ticket-body" v-if="!loadingTicket">
      <p><strong>Статус:</strong> {{ selectedTicket.status_name }}</p>
      <p><strong>Описание:</strong> {{ selectedTicket.description }}</p>

      <div>
        <strong>Теги:</strong>
        <span v-for="tag in selectedTicket.tags" :key="tag.id" class="badge" :style="{ background: tag.color, color: '#fff' }">
          {{ tag.name }}
        </span>
      </div>

      <div class="replies" v-if="selectedTicket.replies?.length">
        <h3>Ответы</h3>
        <article v-for="reply in selectedTicket.replies" :key="reply.id" class="reply">
          <header>{{ reply.admin_email }}</header>
          <p>{{ reply.body }}</p>
          <small class="muted">{{ new Date(reply.created_at).toLocaleString() }}</small>
        </article>
      </div>
      <p v-else class="muted">Ответов пока нет</p>
    </div>
    <div v-else>Загрузка...</div>
  </section>
</template>

<style scoped>
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.creation-form {
  margin: 16px 0;
  display: flex;
  flex-direction: column;
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

.ticket-body {
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
