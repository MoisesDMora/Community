<template>
  <div class="requests-page">
    <header class="mb-6">
        <h1>Gestión de Solicitudes</h1>
        <p class="text-secondary">Pruebas, cambios de propiedad y solicitudes de residentes y reservas.</p>
        
        <!-- Pestañas Principales -->
        <div class="tabs-container mt-4">
            <button @click="activeTab = 'usuarios'" class="tab-btn" :class="{ active: activeTab === 'usuarios' }">
                Solicitudes de Usuarios
            </button>
            <button @click="activeTab = 'reservas'" class="tab-btn" :class="{ active: activeTab === 'reservas' }">
                Reservas de Zonas Comunes
            </button>
        </div>
    </header>

    <div v-if="activeTab === 'usuarios'">
        <div class="flex gap-2 mb-4">
            <button v-for="s in filteredStatus" :key="s.val" 
                    @click="userFilter = s.val" 
                    class="btn btn-sm" 
                    :class="userFilter === s.val ? 'btn-primary' : 'btn-secondary'">
                {{ s.label }}
            </button>
        </div>

        <div v-if="loadingUsers" class="text-center py-10">
            <span class="badge badge-warning">Cargando solicitudes...</span>
        </div>
        <div v-else-if="!userRequests.length" class="glass-panel text-center py-12">
            <span style="font-size: 3rem;">📋</span>
            <h3>No hay solicitudes {{ userFilter ? userFilter : '' }}</h3>
        </div>

        <div v-else class="requests-list">
            <div v-for="req in userRequests" :key="req.id" class="glass-panel request-card mb-4" :class="'status-' + req.status">
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
                    <button @click="handleUserRequest(req, 'aprobada')" class="btn btn-primary flex-1">Aprobar y Activar Usuario</button>
                    <button @click="handleUserRequest(req, 'rechazada')" class="btn btn-secondary text-danger border-danger flex-1">Rechazar</button>
                </div>
                <div v-else class="card-footer mt-4 pt-4 border-top text-xs text-secondary">
                    Gestionada por {{ req.admin?.name || 'Administrador' }} el {{ formatDate(req.updated_at) }}
                    <div v-if="req.admin_notes" class="mt-2 p-2 bg-slate-50 italic">Notas: {{ req.admin_notes }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑA: ZONAS COMUNES -->
    <div v-if="activeTab === 'reservas'">
        <div class="flex gap-2 mb-4">
            <button v-for="s in filteredStatus" :key="s.val" 
                    @click="resFilter = s.val" 
                    class="btn btn-sm" 
                    :class="resFilter === s.val ? 'btn-primary' : 'btn-secondary'">
                {{ s.label }}
            </button>
        </div>

        <div v-if="loadingRes" class="text-center py-10">
            <span class="badge badge-warning">Cargando reservas...</span>
        </div>
        <div v-else-if="!filteredReservations.length" class="glass-panel text-center py-12">
            <span style="font-size: 3rem;">📅</span>
            <h3>No hay reservas {{ resFilter ? resFilter : '' }}</h3>
        </div>

        <div v-else class="requests-list">
            <div v-for="r in filteredReservations" :key="r.id" class="glass-panel request-card mb-4" :class="'status-' + r.status">
                <div class="card-header flex justify-between items-center mb-3">
                    <div class="user-meta">
                        <img :src="r.user?.avatar || 'https://via.placeholder.com/40'" class="avatar-mini" />
                        <div>
                            <span class="font-bold">{{ r.user?.name }} {{ r.user?.last_name || '' }}</span>
                            <span class="text-xs ml-2 text-secondary">{{ formatDate(r.created_at) }}</span>
                        </div>
                    </div>
                    <span class="badge" :class="'badge-' + getStatusClass(r.status)">{{ r.status }}</span>
                </div>

                <div class="card-body">
                    <h4 class="request-title" style="margin-bottom: 5px;">{{ r.common_area?.name }}</h4>
                    <div class="reservation-grid">
                        <div class="res-info-box">
                            <strong>Horario:</strong>
                            <p>{{ formatTime(r.start_time) }} - {{ formatTime(r.end_time) }}</p>
                        </div>
                        <div class="res-info-box">
                            <strong>Aforo:</strong>
                            <p>{{ r.people_count }} de {{ r.common_area?.max_people }} personas</p>
                        </div>
                        <div v-if="r.calculated_fee > 0" class="res-info-box" style="background:#ecfdf5; border-color:#a7f3d0;">
                            <strong style="color:#065f46;">💰 Tarifa calculada:</strong>
                            <p style="color:#059669; font-size:16px; font-weight:800;">${{ parseFloat(r.calculated_fee).toFixed(2) }}</p>
                        </div>
                    </div>
                    <div v-if="r.notes" class="observation-box mt-3" style="padding: 10px;">
                        <strong>Notas del residente:</strong>
                        <p>{{ r.notes }}</p>
                    </div>
                    <div v-if="r.status === 'rechazada' && r.rejection_reason" class="rejection-note mt-3">
                        <strong>⛔ Motivo del rechazo:</strong> {{ r.rejection_reason }}
                    </div>
                </div>

                <div v-if="r.status === 'pendiente'" class="card-footer mt-4 pt-4 border-top flex gap-4">
                    <button @click="handleReservation(r, 'aprobada')" class="btn btn-primary flex-1">Aprobar Reserva</button>
                    <button @click="handleReservation(r, 'rechazada')" class="btn btn-secondary text-danger border-danger flex-1">Rechazar</button>
                </div>
                <div v-else class="card-footer mt-4 pt-4 border-top text-xs text-secondary">
                    Actualizado el {{ formatDate(r.updated_at) }}
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const activeTab = ref('usuarios');

// Tab 1: Usuarios
const userRequests = ref([]);
const loadingUsers = ref(false);
const userFilter = ref('pendiente');

// Tab 2: Reservas
const reservations = ref([]);
const loadingRes = ref(false);
const resFilter = ref('pendiente');

const filteredStatus = [
    { label: 'Pendientes', val: 'pendiente' },
    { label: 'Aprobadas', val: 'aprobada' },
    { label: 'Rechazadas', val: 'rechazada' },
    { label: 'Todas', val: '' }
];

const fetchUserRequests = async () => {
    loadingUsers.value = true;
    try {
        const url = userFilter.value ? `/api/community-requests?status=${userFilter.value}` : '/api/community-requests';
        const { data } = await axios.get(url);
        userRequests.value = data.requests.data;
    } catch (e) { console.error(e); }
    finally { loadingUsers.value = false; }
};

const fetchReservations = async () => {
    loadingRes.value = true;
    try {
        const { data } = await axios.get('/api/admin/reservations');
        reservations.value = data.data;
    } catch (e) { console.error(e); }
    finally { loadingRes.value = false; }
};

const filteredReservations = computed(() => {
    if (!resFilter.value) return reservations.value;
    return reservations.value.filter(r => r.status === resFilter.value);
});

watch(userFilter, fetchUserRequests);

watch(activeTab, (newTab) => {
    if (newTab === 'usuarios') {
        fetchUserRequests();
    } else if (newTab === 'reservas') {
        fetchReservations();
    }
});

const handleUserRequest = async (req, status) => {
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
            fetchUserRequests();
        } catch (e) {
            Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
        }
    }
};

const handleReservation = async (reservation, status) => {
    let rejectionReason = '';

    if (status === 'rechazada') {
        const { value, isConfirmed } = await Swal.fire({
            title: 'Motivo del Rechazo',
            input: 'textarea',
            inputLabel: 'Escribe el motivo del rechazo (será notificado al residente)',
            inputPlaceholder: 'Ej: El horario solicitado ya está ocupado con otro evento...',
            showCancelButton: true,
            confirmButtonText: 'Rechazar Reserva',
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Cancelar',
            inputValidator: (v) => !v && 'Por favor escribe un motivo de rechazo.'
        });
        if (!isConfirmed) return;
        rejectionReason = value;
    } else {
        const res = await Swal.fire({
            title: '¿Aprobar Reserva?',
            text: `¿Confirmas la aprobación de la reserva en ${reservation.common_area?.name}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            confirmButtonColor: 'var(--primary)',
        });
        if (!res.isConfirmed) return;
    }

    try {
        await axios.post(`/api/admin/reservations/${reservation.id}/status`, {
            status,
            rejection_reason: rejectionReason || null
        });
        const r = reservations.value.find(x => x.id === reservation.id);
        if (r) {
            r.status = status;
            if (rejectionReason) r.rejection_reason = rejectionReason;
        }
        Swal.fire('Actualizado', `Reserva marcada como ${status}`, 'success');
    } catch (e) { console.error(e); }
};

const getStatusClass = (status) => {
    if (status === 'pendiente') return 'warning';
    if (status === 'aprobada') return 'success';
    return 'danger';
};

const formatDate = (d) => new Date(d).toLocaleString();
const formatTime = (isoString) => new Date(isoString).toLocaleString('es-ES', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'});

onMounted(() => {
    fetchUserRequests();
});
</script>

<style scoped>
.requests-page { max-width: 950px; margin: 0 auto; }
.avatar-mini { width: 38px; height: 38px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 1px solid #cbd5e1; }
.request-card { border-top: 5px solid #cbd5e1; transition: transform 0.2s; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.status-pendiente { border-top-color: #f59e0b; }
.status-aprobada { border-top-color: #10b981; }
.status-rechazada { border-top-color: #ef4444; }

.tabs-container { display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; }
.tab-btn { padding: 12px 24px; background: none; border: none; font-size: 16px; font-weight: bold; color: #64748b; cursor: pointer; transition: 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px; }
.tab-btn:hover { color: #334155; }
.tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; }

.observation-box { background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0; }
.observation-box p { font-style: italic; color: #475569; margin-top: 5px; }

.reservation-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.res-info-box { background: #f1f5f9; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px; }
.res-info-box p { margin: 5px 0 0; color: #334155; }

.property-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.prop-item { background: white; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem; font-weight: 600; display: flex; gap: 8px; }
.divider { color: #cbd5e1; }

.user-meta { display: flex; align-items: center; }

.btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.2s, background-color 0.2s; font-size: 14px; }
.btn-sm { padding: 6px 14px; font-size: 13px; margin-right: 5px; }
.btn-primary { background-color: #4f46e5; color: white; }
.btn-secondary { background-color: #e2e8f0; color: #334155; }
.border-danger { border: 1px solid #ef4444 !important; }
.text-danger { color: #ef4444 !important; background-color: #fef2f2 !important; }
.btn:hover { opacity: 0.85; }

.badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-warning { background-color: #fef3c7; color: #b45309; }
.badge-success { background-color: #d1fae5; color: #065f46; }
.badge-danger { background-color: #ffe4e6; color: #9f1239; }

.font-bold { font-weight: 700; color: #1e293b; }
.flex-1 { flex: 1; }
.bg-slate-50 { background: #f8fafc; }
.rejection-note { background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px 15px; font-size: 13px; color: #9f1239; }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }
.gap-4 { gap: 1rem; }
.mt-4 { margin-top: 1rem; }
.pt-4 { padding-top: 1rem; }
.border-top { border-top: 1px solid #f1f5f9; }
</style>
