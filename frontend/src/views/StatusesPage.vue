<script setup>
import { reactive, ref } from 'vue';
import { useAdminStore } from '@/stores/admin';

const admin = useAdminStore();
const error = ref('');

if (!admin.statuses.length) {
  admin.fetchStatuses();
}
const form = reactive({ id: null, name: '' });

const editStatus = (status) => {
  form.id = status.id;
  form.name = status.name;
  error.value = '';
};

const reset = () => {
  form.id = null;
  form.name = '';
  error.value = '';
};

const submit = async () => {
  if (!form.name || !form.name.trim()) {
    error.value = 'Название статуса обязательно';
    return;
  }
  
  try {
    error.value = '';
    await admin.upsertStatus({ ...form });
    reset();
  } catch (err) {
    error.value = err.response?.data?.errors?.[0] || 'Ошибка при сохранении статуса';
  }
};

const remove = async (id) => {
  if (confirm('Удалить статус?')) {
    await admin.deleteStatus(id);
  }
};
</script>

<template>
  <section class="card">
    <header class="section-header">
      <div>
        <h1>Статусы</h1>
        <p class="muted">CRUD для статусов обращений</p>
      </div>
    </header>

    <div class="editor">
      <label>
        Название
        <input v-model="form.name" class="input" placeholder="Например: Ready For Review" required />
      </label>
      <div class="actions">
        <button class="button" @click="submit">{{ form.id ? 'Обновить' : 'Создать' }}</button>
        <button class="button secondary" @click="reset">Сброс</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Название</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="status in admin.statuses" :key="status.id">
          <td>#{{ status.id }}</td>
          <td>{{ status.name }}</td>
          <td>
            <button class="button secondary" @click="editStatus(status)">Редактировать</button>
            <button class="button secondary" @click="remove(status.id)">Удалить</button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.editor {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin: 16px 0;
}

.actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.error {
  color: var(--danger);
  font-size: 0.9rem;
  margin-top: 8px;
}
</style>
