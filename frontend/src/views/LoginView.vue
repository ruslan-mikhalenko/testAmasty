<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();
const form = reactive({ email: '', password: '' });

const submit = async () => {
  try {
    await auth.login(form);
    router.push(auth.user.role === 'admin' ? '/admin' : '/client');
  } catch (error) {
    // сообщение отображается ниже
  }
};
</script>

<template>
  <section class="card auth-card">
    <h1>Вход</h1>
    <p class="muted">Введите email и пароль, чтобы продолжить</p>

    <form class="form" @submit.prevent="submit">
      <label>
        Email
        <input v-model="form.email" type="email" required class="input" placeholder="you@example.com" />
      </label>
      <label>
        Пароль
        <input v-model="form.password" type="password" required minlength="6" class="input" placeholder="••••••" />
      </label>
      <button class="button" type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Загрузка...' : 'Войти' }}
      </button>
      <p v-if="auth.error" class="error">{{ auth.error }}</p>
    </form>

    <p class="muted">
      Нет аккаунта?
      <router-link to="/register">Зарегистрироваться</router-link>
    </p>
  </section>
</template>

<style scoped>
.auth-card {
  max-width: 400px;
  margin: 80px auto;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.error {
  color: var(--danger);
  font-size: 0.9rem;
}
</style>
