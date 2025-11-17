<script setup>
import { reactive, ref } from 'vue';
import { useAdminStore } from '@/stores/admin';

const admin = useAdminStore();
const error = ref('');

if (!admin.tags.length) {
  admin.fetchTags();
}
const form = reactive({ id: null, name: '', color: '#2563eb' });

const editTag = (tag) => {
  form.id = tag.id;
  form.name = tag.name;
  form.color = tag.color;
  error.value = '';
};

const reset = () => {
  form.id = null;
  form.name = '';
  form.color = '#2563eb';
  error.value = '';
};

const submit = async () => {
  if (!form.name || !form.name.trim()) {
    error.value = 'Название тега обязательно';
    return;
  }
  
  try {
    error.value = '';
    await admin.upsertTag({ ...form });
    reset();
  } catch (err) {
    error.value = err.response?.data?.errors?.[0] || 'Ошибка при сохранении тега';
  }
};

const remove = async (id) => {
  if (confirm('Удалить тег?')) {
    await admin.deleteTag(id);
  }
};
</script>

<template>
  <section class="card">
    <header class="section-header">
      <div>
        <h1>Теги</h1>
        <p class="muted">Управление каталогом тегов</p>
      </div>
    </header>

    <div class="editor">
      <label>
        Название
        <input v-model="form.name" class="input" placeholder="Например: Tech" required />
      </label>
      <label>
        Цвет
        <input v-model="form.color" class="input" type="color" />
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
          <th>Цвет</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="tag in admin.tags" :key="tag.id">
          <td>#{{ tag.id }}</td>
          <td>{{ tag.name }}</td>
          <td>
            <span class="badge" :style="{ background: tag.color, color: '#fff' }">{{ tag.color }}</span>
          </td>
          <td>
            <button class="button secondary" @click="editTag(tag)">Редактировать</button>
            <button class="button secondary" @click="remove(tag.id)">Удалить</button>
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
