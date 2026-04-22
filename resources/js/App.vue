<template>
  <div class="app-shell" :class="{ 'auth-page': isLoginRoute }" :style="shellStyle">
    <!-- Sidebar for Desktop -->
    <aside class="sidebar" v-if="auth.isAuthenticated && !isLoginRoute">
      <div class="sidebar-header">
        <div class="logo-symbol">🏙️</div>
        <span class="logo-text">{{ appName }}</span>
      </div>

      <nav class="sidebar-nav">
        <!-- Solo visibles si el usuario está aprobado -->
        <template v-if="isApproved">
            <router-link to="/" class="nav-item">
            <span class="icon">🏠</span>
            <span class="label">Dashboard</span>
            </router-link>

            <div class="nav-divider" v-if="isAdmin">Administración</div>

            <router-link to="/admin" v-if="isAdmin" class="nav-item">
            <span class="icon">🛡️</span>
            <span class="label">Usuarios</span>
            </router-link>

            <router-link to="/admin/requests" v-if="isAdmin" class="nav-item">
                <span class="icon">📋</span>
                <span class="label">Solicitudes</span>
                <span v-if="unreadCount > 0" class="badge-count bg-warning">{{ unreadCount }}</span>
            </router-link>

            <router-link to="/admin/notifications" v-if="isAdmin" class="nav-item">
                <span class="icon">🔔</span>
                <span class="label">Alertas Sistema</span>
                <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }}</span>
            </router-link>

            <router-link to="/admin/settings" v-if="isAdmin" class="nav-item">
                <span class="icon">⚙️</span>
                <span class="label">Configuración</span>
            </router-link>

            <router-link to="/admin/roles" v-if="isAdmin" class="nav-item">
            <span class="icon">🔑</span>
            <span class="label">Roles y Permisos</span>
            </router-link>

            <div class="nav-divider">Comunidad</div>
            <a href="#" class="nav-item disabled">
            <span class="icon">📢</span>
            <span class="label">Anuncios</span>
            </a>
            <a href="#" class="nav-item disabled">
            <span class="icon">📅</span>
            <span class="label">Eventos</span>
            </a>
        </template>

        <!-- Pestaña de Notificaciones para Residente (siempre visible si logueado) -->
        <div class="nav-divider">Canales</div>
        <router-link v-if="!isAdmin" to="/notifications" class="nav-item">
            <span class="icon">🔔</span>
            <span class="label">Mis Notificaciones</span>
            <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }}</span>
        </router-link>

        <router-link to="/profile" class="nav-item">
          <span class="icon">👤</span>
          <span class="label">Mi Perfil</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-profile-summary">
          <img :src="auth.user?.avatar || 'https://via.placeholder.com/40'" class="avatar-mini" />
          <div class="user-info-mini">
            <span class="user-name">{{ auth.user?.name }}</span>
            <span class="user-role">{{ auth.user?.roles?.[0]?.name }}</span>
          </div>
        </div>
        <button @click="handleLogout" class="sidebar-logout-btn" title="Cerrar Sesión">
          <span class="icon">🚪</span>
        </button>
      </div>
    </aside>

    <!-- App Content Wrapper -->
    <div class="content-wrapper">
      <!-- Mobile Topbar -->
      <header class="mobile-topbar" v-if="auth.isAuthenticated && !isLoginRoute">
        <button class="menu-btn" @click="toggleMenu">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>
        <div class="mobile-logo">{{ appName }}</div>
        
        <div class="mobile-actions flex items-center gap-3">
            <div v-if="isAdmin && pendingRequestsCount > 0" class="mobile-bell" @click="router.push('/admin/requests')">
                <span>📋</span>
                <span class="badge-count bg-warning">{{ pendingRequestsCount }}</span>
            </div>
            
            <div class="mobile-bell" @click="isAdmin ? router.push('/admin/notifications') : router.push('/notifications')">
                <span>🔔</span>
                <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }}</span>
            </div>
            
            <img :src="auth.user?.avatar || 'https://via.placeholder.com/40'" @click="router.push('/profile')" class="avatar-mini" />
        </div>
      </header>

      <!-- Mobile Menu Overlay -->
      <transition name="fade">
        <div v-if="menuOpen" class="mobile-overlay" @click="menuOpen = false"></div>
      </transition>
      
      <transition name="slide-side">
        <div v-if="menuOpen" class="mobile-sidebar">
            <div class="sidebar-header">
                <div class="logo-symbol">🏙️</div>
                <span class="logo-text">{{ appName }}</span>
            </div>
            <nav class="sidebar-nav" @click="menuOpen = false">
                <template v-if="isApproved">
                    <router-link to="/" class="nav-item">Dashboard</router-link>
                    <router-link v-if="isAdmin" to="/admin" class="nav-item">Usuarios</router-link>
                    <router-link v-if="isAdmin" to="/admin/requests" class="nav-item">Solicitudes</router-link>
                    <router-link v-if="isAdmin" to="/admin/settings" class="nav-item">Configuración</router-link>
                </template>
                <router-link :to="isAdmin ? '/admin/notifications' : '/notifications'" class="nav-item">
                    Notificaciones ({{ unreadCount }})
                </router-link>
                <router-link to="/profile" class="nav-item">Mi Perfil</router-link>
                <button @click="handleLogout" class="nav-item logout-link">Cerrar Sesión</button>
            </nav>
        </div>
      </transition>

      <!-- Alerta Usuario No Aprobado -->
      <div v-if="auth.isAuthenticated && !isApproved && !isLoginRoute" class="approval-alert">
          <div class="alert-content">
              <span class="alert-icon">⚠️</span>
              <div class="alert-text">
                  <strong>Cuenta en espera de aprobación</strong>
                  <p>Tu acceso a las funciones de la comunidad está limitado hasta que la administración verifique tu perfil.</p>
              </div>
          </div>
      </div>

      <main class="main-body" :class="{ 'no-padding': isLoginRoute }">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, onUnmounted } from 'vue';
import { useAuthStore } from './stores/auth';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const appName = ref('ResidencialApp');
const appBackground = ref('');
const menuOpen = ref(false);
const unreadCount = ref(0);
const pendingRequestsCount = ref(0);
let countersInterval = null;
const lastUnreadCount = ref(0);

const isAdmin = computed(() => auth.user?.roles?.some(r => r.name === 'admin' || r.name === 'master'));
const isApproved = computed(() => auth.user?.status === 'activo');
const isLoginRoute = computed(() => route.path === '/login' || route.path === '/login-callback');

const shellStyle = computed(() => {
    if (isLoginRoute.value && appBackground.value) {
        return {
            backgroundImage: `url(${appBackground.value})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            backgroundAttachment: 'fixed'
        };
    }
    return {};
});

const toggleMenu = () => { menuOpen.value = !menuOpen.value; };

const triggerDesktopNotification = (count) => {
    if (Notification.permission === "granted" && auth.user?.wants_desktop_notifications) {
        new Notification(appName.value, { body: `Tienes ${count} nuevas notificaciones.`, icon: '/favicon.ico' });
    }
};

const fetchCounters = async () => {
    if (!auth.isAuthenticated) return;
    try {
        const [notif, reqs] = await Promise.all([
            axios.get('/api/notifications/unread-count'),
            isAdmin.value ? axios.get('/api/community-requests/pending-count') : Promise.resolve({ data: { count: 0 } })
        ]);
        const newUnread = notif.data.count;
        if (newUnread > lastUnreadCount.value) triggerDesktopNotification(newUnread);
        lastUnreadCount.value = newUnread;
        unreadCount.value = newUnread;
        pendingRequestsCount.value = reqs.data.count;
    } catch (e) { console.error(e); }
};

const handleLogout = async () => {
    menuOpen.value = false;
    const { isConfirmed } = await Swal.fire({ title: '¿Cerrar sesión?', icon: 'question', showCancelButton: true, confirmButtonColor: 'var(--primary)', confirmButtonText: 'Sí, salir' });
    if (isConfirmed) { await auth.logout(); router.push('/login'); }
};

const loadSettings = async () => {
    try {
        const { data } = await axios.get('/api/settings');
        const s = data.settings;
        if (s.app_name) appName.value = s.app_name;
        if (s.login_background_image) appBackground.value = s.login_background_image;
        if (s.app_primary_color) {
            document.documentElement.style.setProperty('--primary', s.app_primary_color);
            document.documentElement.style.setProperty('--primary-glow', s.app_primary_color + '26');
        }
        if (s.app_primary_hover_color) document.documentElement.style.setProperty('--primary-hover', s.app_primary_hover_color);
        if (s.app_secondary_color) document.documentElement.style.setProperty('--secondary', s.app_secondary_color);
    } catch (e) { console.error(e); }
};

onMounted(async () => {
    loadSettings();
    fetchCounters();
    countersInterval = setInterval(fetchCounters, 30000);
});

onUnmounted(() => { if (countersInterval) clearInterval(countersInterval); });
</script>

<style scoped>
.app-shell { display: flex; min-height: 100vh; background: var(--background); transition: background-image 0.5s ease-in-out; }
.auth-page { display: block; position: relative; }
.auth-page::before { content: ""; position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 0; }
.sidebar { width: 280px; background: white; border-right: 1px solid #f1f5f9; display: none; flex-direction: column; height: 100vh; position: sticky; top: 0; padding: 1.5rem; z-index: 10; }
.sidebar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 2.5rem; padding: 0 0.5rem; }
.logo-symbol { font-size: 1.8rem; background: #f8fafc; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
.logo-text { font-size: 1.25rem; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
.sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 0.85rem 1rem; text-decoration: none; color: #64748b; font-weight: 600; border-radius: 12px; transition: all 0.2s ease; position: relative; }
.nav-item:hover { background: #f8fafc; color: var(--primary); }
.nav-item.router-link-active { background: #eef2ff; color: var(--primary); }
.badge-count { position: absolute; right: 12px; background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 999px; }
.bg-warning { background: #f59e0b !important; }
.nav-divider { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin: 1.5rem 0 0.75rem 1rem; }
.sidebar-footer { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.avatar-mini { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; background: #f1f5f9; cursor: pointer; }
.user-name { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.user-role { font-size: 0.75rem; color: #64748b; text-transform: capitalize; }
.sidebar-logout-btn { background: #fff1f2; border: none; width: 38px; height: 38px; border-radius: 10px; cursor: pointer; }
.content-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; z-index: 1; }
.mobile-topbar { height: 64px; background: white; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; padding: 0 1.25rem; position: sticky; top: 0; z-index: 50; }
.menu-btn { background: transparent; border: none; color: #64748b; }
.mobile-logo { font-weight: 800; color: var(--primary); }
.mobile-bell { position: relative; font-size: 1.4rem; cursor: pointer; }
.main-body { padding: 2rem; max-width: 1200px; flex: 1; }
.no-padding { padding: 0 !important; }
.mobile-sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 280px; background: white; z-index: 101; padding: 1.5rem; display: flex; flex-direction: column; box-shadow: 20px 0 50px rgba(0,0,0,0.1); }
.mobile-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 100; }
@media (min-width: 1024px) { .sidebar { display: flex; } .mobile-topbar { display: none; } .main-body { padding: 3rem; } }
</style>
