<template>
  <div class="requests-page">
    <header class="flex justify-between items-center mb-6">
        <div>
            <h1>Gestión de Solicitudes</h1>
            <p class="text-secondary">Pruebas, cambios de propiedad y solicitudes de residentes.</p>
        </div>
        <div class="flex gap-2">
            <button v-for="s in filteredStatus" :key="s.val" 
                    @click="currentFilter = s.val" 
                    class="btn btn-sm" 
                    :class="currentFilter === s.val ? 'btn-primary' : 'btn-secondary'">
                {{ s.label }}
            </button>
        </div>
    </header>

    <div v-if="loading" class="text-center py-10">
        <span class="badge badge-warning">Cargando solicitudes...</span>
    </div>

    <div v-else-if="!requests.length" class="glass-panel text-center py-12">
        <span style="font-size: 3rem;">📋</span>
        <h3>No hay solicitudes {{ currentFilter ? currentFilter : '' }}</h3>
    </div>

    <div v-else class="requests-list">
        <div v-for="req in requests" :key="req.id" class="glass-panel request-card mb-4" :class="'status-' + req.status">
            <div class="card-header flex justify-between items-center mb-3">
                <div class="user-meta">
                    <img :src="req.user?.avatar || 'https://via.placeholder.com/40'" class="avatar-mini" />
                    <div>
                        <span class="font-bold">{{ req.user?.name }} {{ req.user?.last_name }}</span>
                        <span class="text-xs ml-2 text-secondary">{{ formatDate(req.created_at) }}</span>
                    </div>
                </div>
                <span class="badge" :class="'badge-' + getStatusClass(req.status)">{{ req.status }}</span>
            </div>

            <div class="card-body">
                <h4 class="request-title">{{ req.title }}</h4>
                <div class="observation-box mb-3">
                    <strong>Observación del residente:</strong>
                    <p>{{ req.description || 'Sin descripción' }}</p>
                </div>

                <div v-if="req.data?.properties" class="property-grid">
                    <div v-for="(p, i) in req.data.properties" :key="i" class="prop-item">
                       <span>Torre {{ p.tower }}</span>
                       <span class="divider">|</span>
                       <span>Apto {{ p.apartment }}</span>
                       <span class="divider">|</span>
                       <span class="text-xs uppercase">{{ p.type }}</span>
                    </div>
                </div>
            </div>

            <div v-if="req.status === 'pendiente'" class="card-footer mt-4 pt-4 border-top flex gap-4">
                <button @click="handleRequest(req, 'aprobada')" class="btn btn-primary flex-1">Aprobar y Activar Usuario</button>
                <button @click="handleRequest(req, 'rechazada')" class="btn btn-secondary text-danger border-danger flex-1">Rechazar</button>
            </div>
            <div v-else class="card-footer mt-4 pt-4 border-top text-xs text-secondary">
                Gestionada por {{ req.admin?.name || 'Administrador' }} el {{ formatDate(req.updated_at) }}
                <div v-if="req.admin_notes" class="mt-2 p-2 bg-slate-50 italic">Notas: {{ req.admin_notes }}</div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const requests = ref([]);
const loading = ref(true);
const currentFilter = ref('pendiente');

const filteredStatus = [
    { label: 'Pendientes', val: 'pendiente' },
    { label: 'Aprobadas', val: 'aprobada' },
    { label: 'Rechazadas', val: 'rechazada' },
    { label: 'Todas', val: '' }
];

const fetchRequests = async () => {
    loading.value = true;
    try {
        const url = currentFilter.value ? `/api/community-requests?status=${currentFilter.value}` : '/api/community-requests';
        const { data } = await axios.get(url);
        requests.value = data.requests.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
};

watch(currentFilter, fetchRequests);

const handleRequest = async (req, status) => {
    const { value: notes } = await Swal.fire({
        title: status === 'aprobada' ? 'Confirmar Aprobación' : 'Motivo del Rechazo',
        input: 'textarea',
        inputLabel: 'Notas administrativas (opcional)',
        inputPlaceholder: 'Escribe aquí cualquier aclaración...',
        showCancelButton: true,
        confirmButtonText: status === 'aprobada' ? 'Aprobar' : 'Rechazar',
        confirmButtonColor: status === 'aprobada' ? 'var(--primary)' : '#ef4444'
    });

    if (notes !== undefined) {
        try {
            await axios.post(`/api/community-requests/${req.id}/handle`, {
                status,
                admin_notes: notes
            });
            Swal.fire('¡Listo!', `Solicitud ${status} correctamente`, 'success');
            fetchRequests();
        } catch (e) {
            Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
        }
    }
};

const getStatusClass = (status) => {
    if (status === 'pendiente') return 'warning';
    if (status === 'aprobada') return 'success';
    return 'danger';
};

const formatDate = (d) => new Date(d).toLocaleString();

onMounted(fetchRequests);
</script>

<style scoped>
.requests-page { max-width: 950px; }
.avatar-mini { width: 32px; height: 32px; border-radius: 8px; margin-right: 10px; }
.request-card { border-top: 5px solid #cbd5e1; transition: transform 0.2s; }
.status-pendiente { border-top-color: var(--warning); }
.status-aprobada { border-top-color: var(--success); }
.status-rechazada { border-top-color: var(--danger); }

.observation-box { background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0; }
.observation-box p { font-style: italic; color: #475569; margin-top: 5px; }

.property-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.prop-item { background: white; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem; font-weight: 600; display: flex; gap: 8px; }
.divider { color: #cbd5e1; }

.btn-icon { background: none; border: none; font-size: 1.2rem; cursor: pointer; }
.text-danger { color: #ef4444 !important; }
.border-danger { border: 1px solid #ef4444 !important; }
.font-bold { font-weight: 700; color: #1e293b; }
.flex-1 { flex: 1; }
.bg-slate-50 { background: #f8fafc; }
</style>
