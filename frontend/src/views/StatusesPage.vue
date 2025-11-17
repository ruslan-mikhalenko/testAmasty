<script setup>
import { reactive } from 'vue';
import { useAdminStore } from '@/stores/admin';

const admin = useAdminStore();

if (!admin.statuses.length) {
  admin.fetchStatuses();
}
const form = reactive({ id: null, name: '' });

const editStatus = (status) => {
  form.id = status.id;
  form.name = status.name;
};

const reset = () => {
  form.id = null;
  form.name = '';
};

const submit = async () => {
  await admin.upsertStatus({ ...form });
  reset();
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
        <input v-model="form.name" class="input" placeholder="Например: Ready For Review" />
      </label>
      <div class="actions">
        <button class="button" @click="submit">{{ form.id ? 'Обновить' : 'Создать' }}</button>
        <button class="button secondary" @click="reset">Сброс</button>
      </div>
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
</style>
