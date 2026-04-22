<template>
  <div class="dashboard-page">
    <h1 class="page-title">Bienvenido(a), {{ auth.user?.name }}</h1>
    
    <!-- Pending Status Alert -->
    <div class="glass-panel alert-card" v-if="auth.user?.status === 'pendiente'">
      <div class="alert-icon">⏳</div>
      <div class="alert-content">
        <h2>Aprobación Pendiente</h2>
        <p>Tu cuenta ha sido registrada y tus datos están en revisión. Pronto un administrador activará tu acceso completo.</p>
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Residences Widget -->
      <div class="glass-panel widget-card">
        <div class="widget-header">
            <h3>Mis Propiedades</h3>
            <span class="badge badge-primary">{{ auth.user?.properties?.length || 0 }}</span>
        </div>
        <div class="properties-mini-list">
            <div v-for="prop in auth.user?.properties" :key="prop.id" class="mini-prop-item">
                <div class="prop-icon">🏠</div>
                <div class="prop-details">
                    <span class="prop-loc">Torre {{ prop.tower }} - Apto {{ prop.apartment }}</span>
                    <span class="prop-type">{{ prop.type === 'propietario' ? 'Propietario' : 'Arrendatario' }}</span>
                </div>
            </div>
            <div v-if="!auth.user?.properties?.length" class="empty-mini">
                No tienes propiedades registradas.
            </div>
        </div>
        <router-link to="/profile" class="btn btn-secondary btn-sm mt-4 w-full">Actualizar Perfil</router-link>
      </div>

      <!-- Notifications Widget -->
      <div class="glass-panel widget-card">
        <h3>Notificaciones</h3>
        <div class="empty-notifications">
            <p>No hay anuncios recientes en la comunidad.</p>
        </div>
      </div>

      <!-- Admin Quick Access -->
      <div class="glass-panel widget-card admin-widget" v-if="isAdmin">
        <h3>Administración</h3>
        <p>Gestiona los residentes y la configuración global.</p>
        <router-link to="/admin" class="btn btn-primary mt-4 w-full">Ver Panel Admin</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { computed } from 'vue';

const auth = useAuthStore();
const isAdmin = computed(() => auth.hasRole(['admin', 'master']));
</script>

<style scoped>
.dashboard-page {
  max-width: 1200px;
}

.page-title {
  margin-bottom: 2.5rem;
  font-size: 2.4rem;
}

.alert-card {
    display: flex;
    gap: 20px;
    align-items: center;
    background: #fffbeb;
    border: 1px solid #fef3c7;
    margin-bottom: 2.5rem;
}

.alert-icon { font-size: 2.5rem; }
.alert-card h2 { color: #92400e; margin-bottom: 0.25rem; }
.alert-card p { color: #b45309; }

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2rem;
}

.widget-card {
  padding: 1.8rem;
  display: flex;
  flex-direction: column;
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.widget-card h3 {
  margin-bottom: 1.25rem;
  color: var(--primary);
  font-size: 1.25rem;
}

.properties-mini-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mini-prop-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 12px;
}

.prop-icon { font-size: 1.2rem; }
.prop-details { display: flex; flex-direction: column; }
.prop-loc { font-weight: 700; font-size: 0.95rem; color: #1e293b; }
.prop-type { font-size: 0.75rem; color: #64748b; text-transform: capitalize; }

.empty-notifications, .empty-mini {
    padding: 2rem 0;
    text-align: center;
    color: #94a3b8;
    font-size: 0.9rem;
}

.w-full { width: 100%; }
.mt-4 { margin-top: 1rem; }

.admin-widget {
    border: 1px solid #e0e7ff;
    background: linear-gradient(135deg, #ffffff 0%, #f5f7ff 100%);
}
</style>
