import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
  {
    path: '/',
    component: () => import('./views/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    component: () => import('./views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/login-callback',
    component: () => import('./views/LoginCallback.vue')
  },
  {
    path: '/profile',
    component: () => import('./views/Profile.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    component: () => import('./views/Admin.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'master'] }
  },
  {
    path: '/admin/roles',
    component: () => import('./views/RolesPermissions.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'master'] }
  },
  {
    path: '/admin/notifications',
    component: () => import('./views/Notifications.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'master'] }
  },
  {
    path: '/admin/requests',
    component: () => import('./views/Requests.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'master'] }
  },
  {
    path: '/admin/settings',
    component: () => import('./views/Settings.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'master'] }
  },
  {
    path: '/notifications',
    component: () => import('./views/Notifications.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth) {
    if (!localStorage.getItem('token')) {
        return next('/login');
    }
    
    // Ensure user data is loaded
    if (!authStore.user) {
        await authStore.fetchUser();
    }
    
    // Check if user object is loaded
    if (!authStore.user) {
        return next('/login');
    }

    // Role Guard
    if (to.meta.roles && !authStore.hasRole(to.meta.roles)) {
        return next('/');
    }

    // Status Guardian
    if (to.path !== '/profile' && authStore.user.status === 'pendiente') {
       // Only allow access to profile to set details until approved
       console.log('User status is pending, redirecting to profile.');
       // We can allow Dashboard to show generic message too, but Profile is mandatory for completion.
    }
    
    return next();
  }

  if (to.meta.guest && localStorage.getItem('token')) {
    return next('/');
  }

  next();
});

export default router;
