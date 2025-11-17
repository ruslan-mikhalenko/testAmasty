import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const LoginView = () => import('@/views/LoginView.vue');
const RegisterView = () => import('@/views/RegisterView.vue');
const ClientDashboard = () => import('@/views/ClientDashboard.vue');
const AdminDashboard = () => import('@/views/AdminDashboard.vue');
const TagsPage = () => import('@/views/TagsPage.vue');
const StatusesPage = () => import('@/views/StatusesPage.vue');

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/register', component: RegisterView, meta: { guest: true } },
    { path: '/', redirect: '/login' },
    { path: '/client', component: ClientDashboard, meta: { requiresAuth: true, role: 'client' } },
    { path: '/admin', component: AdminDashboard, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/tags', component: TagsPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/statuses', component: StatusesPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/:pathMatch(.*)*', redirect: '/login' },
  ],
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();
  if (!auth.isBootstrapped) {
    await auth.bootstrap();
  }

  if (to.meta?.requiresAuth && !auth.isAuthenticated) {
    return next('/login');
  }

  if (to.meta?.role && auth.user?.role !== to.meta.role) {
    return next(auth.user?.role === 'admin' ? '/admin' : '/client');
  }

  if (to.meta?.guest && auth.isAuthenticated) {
    return next(auth.user?.role === 'admin' ? '/admin' : '/client');
  }

  return next();
});

export default router;
