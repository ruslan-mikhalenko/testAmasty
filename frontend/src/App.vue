<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const router = useRouter();

const links = computed(() => {
  if (!auth.user) {
    return [];
  }

  if (auth.user.role === "admin") {
    return [
      { label: "Задачи", to: "/admin" },
      { label: "Теги", to: "/admin/tags" },
      { label: "Статусы", to: "/admin/statuses" },
    ];
  }

  return [{ label: "Мои обращения", to: "/client" }];
});

const logout = async () => {
  await auth.logout();
  router.push("/login");
};
</script>

<template>
  <div class="layout-shell">
    <header class="app-header card" v-if="auth.user">
      <div class="branding">
        <strong>Support Tracker</strong>
        <span class="muted">{{ auth.user.email }}</span>
      </div>
      <nav class="nav-links">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="nav-link"
        >
          {{ link.label }}
        </router-link>
        <button class="button secondary" @click="logout">Выйти</button>
      </nav>
    </header>

    <router-view />
  </div>
</template>

<style scoped>
.app-header {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
}

.branding {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.muted {
  font-size: 0.85rem;
  color: #64748b;
}

.nav-links {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.nav-link {
  padding: 8px 14px;
  border-radius: 999px;
  font-weight: 500;
  color: #0f172a;
  transition: background 0.2s ease;
}

.nav-link.router-link-active {
  background: rgba(37, 99, 235, 0.1);
  color: var(--accent);
}
</style>
