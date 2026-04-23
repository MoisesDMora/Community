<template>
  <div class="common-areas-page">
    <header class="header-section">
        <div class="header-text">
            <h1>Zonas Comunes</h1>
            <p class="text-secondary">Conoce y reserva los espacios del conjunto.</p>
        </div>
        <div class="header-actions">
            <button v-if="isAdminMode" @click="openCreateModal" class="btn btn-primary btn-sm">Nueva Zona Común</button>
            <button @click="openMyReservations" class="btn btn-secondary btn-sm">Mis Reservas</button>
        </div>
    </header>

    <div v-if="loading" style="margin-top: 40px; text-align: center;">
        <span class="badge badge-warning" style="font-size: 16px;">Cargando espacios...</span>
    </div>

    <!-- Lista de Zonas Comunes -->
    <div v-else class="areas-grid">
        <div v-for="area in commonAreas" :key="area.id" class="glass-panel area-card">
            <div class="area-info">
                <h3>{{ area.name }}</h3>
                <p class="text-secondary">⏱️ Permitido hasta: <b>{{ area.time_limit }} {{ area.time_unit }}</b></p>
                <p class="text-secondary">👥 Aforo Máximo: <b>{{ area.max_people }} px</b></p>
                <p v-if="area.fee_type && area.fee_type !== 'none'" class="text-secondary">
                    💰 Tarifa: <b>${{ parseFloat(area.fee_amount).toFixed(2) }}</b>
                    <span class="fee-badge">{{ area.fee_type === 'per_person' ? 'por persona' : 'por reserva' }}</span>
                </p>
                <p v-else class="text-secondary">💰 Tarifa: <b>Gratis</b></p>
            </div>
            <div class="area-actions">
                <button @click="openCalendar(area)" class="btn btn-primary w-100" style="margin-bottom: 5px;">Ver y Reservar</button>
                <div v-if="isAdminMode" style="display:flex; gap: 5px; width: 100%;">
                    <button @click="openEditModal(area)" class="btn btn-warning w-100" style="flex:1;">Editar</button>
                    <button @click="deleteArea(area.id)" class="btn btn-danger btn-icon" title="Eliminar Zona">&#128465;</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Zona -->
    <div v-if="showCreateArea" class="custom-modal-overlay">
        <div class="custom-modal-content glass-panel">
            <h2>{{ isEditing ? 'Editar Zona Común' : 'Crear Nueva Zona Común' }}</h2>
            <form @submit.prevent="saveArea" class="form-container">
                <div class="form-group">
                    <label>Nombre de la Zona</label>
                    <input type="text" v-model="newArea.name" class="form-control" required placeholder="Ej: BBQ 1, Salón Comunal..." />
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label>Tiempo Límite</label>
                        <input type="number" v-model="newArea.time_limit" class="form-control" min="1" required />
                    </div>
                    <div class="form-group half-width">
                        <label>Unidad de Tiempo</label>
                        <select v-model="newArea.time_unit" class="form-control" required>
                            <option value="horas">Horas</option>
                            <option value="dias">Días</option>
                            <option value="semanas">Semanas</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Aforo Máximo (Personas permitidas)</label>
                    <input type="number" v-model="newArea.max_people" class="form-control" min="1" required />
                </div>

                <!-- Sección de Tarifas -->
                <div class="fee-section">
                    <h4 class="section-title">💰 Configuración de Tarifa</h4>
                    <div class="form-group">
                        <label>Tipo de Tarifa</label>
                        <div class="fee-type-options">
                            <label class="fee-option" :class="{ active: newArea.fee_type === 'none' }">
                                <input type="radio" v-model="newArea.fee_type" value="none" />
                                <span class="fee-option-icon">🆓</span>
                                <span>Sin tarifa</span>
                            </label>
                            <label class="fee-option" :class="{ active: newArea.fee_type === 'per_person' }">
                                <input type="radio" v-model="newArea.fee_type" value="per_person" />
                                <span class="fee-option-icon">👤</span>
                                <span>Por persona</span>
                            </label>
                            <label class="fee-option" :class="{ active: newArea.fee_type === 'per_time' }">
                                <input type="radio" v-model="newArea.fee_type" value="per_time" />
                                <span class="fee-option-icon">⏱️</span>
                                <span>Por reserva</span>
                            </label>
                        </div>
                    </div>
                    <div v-if="newArea.fee_type !== 'none'" class="form-group">
                        <label>{{ newArea.fee_type === 'per_person' ? 'Valor por Persona ($)' : 'Valor por Reserva ($)' }}</label>
                        <input type="number" v-model="newArea.fee_amount" class="form-control" min="0" step="0.01" required placeholder="0.00" />
                        <p class="field-hint" v-if="newArea.fee_type === 'per_person'">
                            El total se calculará automáticamente según el número de personas al reservar.
                        </p>
                        <p class="field-hint" v-else>
                            Este monto fijo se cobrará por cada reserva realizada.
                        </p>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" @click="showCreateArea = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Calendario / Reservar -->
    <div v-if="selectedArea" class="custom-modal-overlay top-aligned">
        <div class="custom-modal-content glass-panel cal-modal-content">
            <div class="modal-header">
                <div>
                    <h2 style="margin-bottom: 5px;">Calendario: {{ selectedArea.name }}</h2>
                    <p class="text-secondary" style="font-size: 13px;">
                        Aforo Total: <b>{{ selectedArea.max_people }} px</b> | 
                        Límite: {{ selectedArea.time_limit }} {{ selectedArea.time_unit }}
                        <span v-if="selectedArea.fee_type && selectedArea.fee_type !== 'none'" style="margin-left:10px; color: #059669; font-weight:bold;">
                            💰 {{ selectedArea.fee_type === 'per_person' ? '$' + selectedArea.fee_amount + '/persona' : '$' + selectedArea.fee_amount + '/reserva' }}
                        </span>
                    </p>
                </div>
                <button @click="selectedArea = null" class="btn-close-modal">&#10006;</button>
            </div>
            
            <div class="calendar-layout">
                <div class="calendar-wrapper">
                    <FullCalendar :options="calendarOptions" />
                </div>

                <div class="reservation-form">
                    <h4 style="margin-top:0;">Solicitar Reserva</h4>
                    <p class="help-text">Puedes hacer clic en el calendario para seleccionar el día rápidamente.</p>
                    <form @submit.prevent="reserveArea" class="form-container">
                        <div class="form-group">
                            <label>Desde (Fecha y Hora)</label>
                            <input type="datetime-local" v-model="reservation.start_time" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label>Hasta (Fecha y Hora)</label>
                            <input type="datetime-local" v-model="reservation.end_time" class="form-control" required />
                        </div>
                        <div class="form-group" style="padding-top: 10px; border-top: 1px solid #ddd;">
                            <label>Número de Personas</label>
                            <input type="number" v-model.number="reservation.people_count" class="form-control" min="1" :max="selectedArea.max_people" required />
                        </div>
                        
                        <!-- Cálculo de Tarifa en Tiempo Real -->
                        <div v-if="selectedArea.fee_type && selectedArea.fee_type !== 'none'" class="fee-preview">
                            <div class="fee-preview-row">
                                <span>Tipo de tarifa:</span>
                                <span>{{ selectedArea.fee_type === 'per_person' ? 'Por persona' : 'Por reserva' }}</span>
                            </div>
                            <div v-if="selectedArea.fee_type === 'per_person'" class="fee-preview-row">
                                <span>{{ reservation.people_count }} persona(s) × ${{ selectedArea.fee_amount }}</span>
                                <span class="fee-total">${{ (reservation.people_count * parseFloat(selectedArea.fee_amount || 0)).toFixed(2) }}</span>
                            </div>
                            <div v-else class="fee-preview-row">
                                <span>Valor fijo por reserva:</span>
                                <span class="fee-total">${{ parseFloat(selectedArea.fee_amount || 0).toFixed(2) }}</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notas Adicionales</label>
                            <textarea v-model="reservation.notes" class="form-control" rows="2" placeholder="Opcional. Ej. Llevaré mascota..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" style="margin-top: 10px; padding: 12px; font-size: 16px;" :disabled="saving">
                            {{ saving ? 'Enviando...' : 'Confirmar Solicitud' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mis Reservas -->
    <div v-if="showMyReservations" class="custom-modal-overlay">
        <div class="custom-modal-content glass-panel list-modal">
            <div class="modal-header">
                <h2 style="margin: 0;">Mis Reservas</h2>
                <button @click="showMyReservations = false" class="btn-close-modal">&#10006;</button>
            </div>
            
            <div class="scrollable-list" v-if="myReservations.length">
                <div v-for="r in myReservations" :key="r.id" class="list-card" :class="'border-' + r.status">
                    <div class="card-details">
                        <h4 style="margin:0 0 5px;">{{ r.common_area?.name }}</h4>
                        <div class="datetime-grid text-secondary">
                            <div>Del: {{ formatRealDate(r.start_time) }}</div>
                            <div>Al: {{ formatRealDate(r.end_time) }}</div>
                            <div>Asistentes: <b>{{ r.people_count }} px.</b></div>
                            <div v-if="r.calculated_fee > 0">Tarifa: <b style="color: #059669;">${{ parseFloat(r.calculated_fee).toFixed(2) }}</b></div>
                        </div>
                        <div v-if="r.status === 'rechazada' && r.rejection_reason" class="rejection-note">
                            <strong>Motivo del rechazo:</strong> {{ r.rejection_reason }}
                        </div>
                    </div>
                    <div class="card-status">
                        <span class="badge" :class="'badge-' + r.status">{{ r.status }}</span>
                    </div>
                </div>
            </div>
            <div v-else class="empty-state">No tienes reservas recientes.</div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const auth = useAuthStore();
const commonAreas = ref([]);
const loading = ref(true);
const saving = ref(false);

const showCreateArea = ref(false);
const isEditing = ref(false);
const editingAreaId = ref(null);
const newArea = reactive({ name: '', time_limit: 2, time_unit: 'horas', max_people: 10, fee_type: 'none', fee_amount: 0 });

const selectedArea = ref(null);
const areaReservations = ref([]);
const reservation = reactive({ start_time: '', end_time: '', people_count: 1, notes: '' });

const showMyReservations = ref(false);
const myReservations = ref([]);

const isAdminMode = computed(() => auth.user?.roles?.some(r => r.name === 'admin' || r.name === 'master'));

const formatRealDate = (isoString) => new Date(isoString).toLocaleString('es-ES', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'});

const calendarOptions = ref({
    plugins: [ dayGridPlugin, interactionPlugin ],
    initialView: 'dayGridMonth',
    locale: 'es',
    headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
    buttonText: { today: 'Hoy' },
    selectable: true,
    height: '100%',
    events: [],
    dateClick: (info) => {
        let dStr = new Date(info.date);
        let tzoffset = (new Date()).getTimezoneOffset() * 60000;
        dStr.setHours(8,0,0,0);
        reservation.start_time = (new Date(dStr - tzoffset)).toISOString().slice(0, 16);
        dStr.setHours(10,0,0,0);
        reservation.end_time = (new Date(dStr - tzoffset)).toISOString().slice(0, 16);
    }
});

const injectCalendarEvents = () => {
    calendarOptions.value.events = areaReservations.value.map(r => ({
        id: r.id,
        title: `Ocupa ${r.people_count} px`,
        start: r.start_time,
        end: r.end_time,
        color: r.status === 'aprobada' ? '#10b981' : '#f59e0b'
    }));
};

const fetchAreas = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/common-areas');
        commonAreas.value = data.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
};

const openCreateModal = () => {
    isEditing.value = false;
    editingAreaId.value = null;
    Object.assign(newArea, { name: '', time_limit: 2, time_unit: 'horas', max_people: 10, fee_type: 'none', fee_amount: 0 });
    showCreateArea.value = true;
};

const openEditModal = (area) => {
    isEditing.value = true;
    editingAreaId.value = area.id;
    Object.assign(newArea, { 
        name: area.name, 
        time_limit: area.time_limit, 
        time_unit: area.time_unit, 
        max_people: area.max_people,
        fee_type: area.fee_type || 'none',
        fee_amount: area.fee_amount || 0,
    });
    showCreateArea.value = true;
};

const saveArea = async () => {
    saving.value = true;
    try {
        const payload = { ...newArea };
        if (payload.fee_type === 'none') payload.fee_amount = 0;

        if (isEditing.value) {
            const { data } = await axios.put(`/api/common-areas/${editingAreaId.value}`, payload);
            const index = commonAreas.value.findIndex(a => a.id === editingAreaId.value);
            if (index !== -1) commonAreas.value[index] = data.data;
            Swal.fire({ title: 'Actualizada', text: 'Zona común modificada', icon: 'success', timer: 1500, showConfirmButton: false });
        } else {
            const { data } = await axios.post('/api/common-areas', payload);
            commonAreas.value.push(data.data);
            Swal.fire({ title: 'Creada', text: 'Zona común creada exitosamente', icon: 'success', timer: 1500, showConfirmButton: false });
        }
        showCreateArea.value = false;
    } catch (e) {
        Swal.fire('Error', 'No se pudo guardar la zona.', 'error');
    } finally { saving.value = false; }
};

const deleteArea = async (id) => {
    const res = await Swal.fire({ title: '¿Eliminar zona?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar' });
    if (res.isConfirmed) {
        try {
            await axios.delete(`/api/common-areas/${id}`);
            commonAreas.value = commonAreas.value.filter(a => a.id !== id);
        } catch (e) { console.error(e); }
    }
};

const openCalendar = async (area) => {
    selectedArea.value = area;
    Object.assign(reservation, { start_time: '', end_time: '', people_count: 1, notes: '' });
    areaReservations.value = [];
    calendarOptions.value.events = [];
    
    try {
        const { data } = await axios.get(`/api/common-areas/${area.id}/reservations`);
        areaReservations.value = data.data;
        injectCalendarEvents();
        let now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setHours(now.getHours() + 1);
        reservation.start_time = now.toISOString().slice(0, 16);
        now.setHours(now.getHours() + 2);
        reservation.end_time = now.toISOString().slice(0, 16);
    } catch (e) { console.error(e); }
};

const reserveArea = async () => {
    saving.value = true;
    try {
        await axios.post(`/api/common-areas/${selectedArea.value.id}/reservations`, reservation);
        let msg = 'Tu solicitud de reserva está pendiente de aprobación.';
        if (selectedArea.value.fee_type === 'per_person') {
            const fee = (reservation.people_count * parseFloat(selectedArea.value.fee_amount || 0)).toFixed(2);
            msg += ` Tarifa estimada: $${fee}`;
        } else if (selectedArea.value.fee_type === 'per_time') {
            msg += ` Tarifa por reserva: $${parseFloat(selectedArea.value.fee_amount || 0).toFixed(2)}`;
        }
        Swal.fire('¡Reservado!', msg, 'success');
        selectedArea.value = null;
    } catch (e) {
        Swal.fire('Atención', e.response?.data?.error || 'No se pudo reservar el horario.', 'warning');
    } finally { saving.value = false; }
};

const openMyReservations = async () => {
    showMyReservations.value = true;
    try {
        const { data } = await axios.get('/api/my-reservations');
        myReservations.value = data.data;
    } catch (e) { console.error(e); }
};

onMounted(fetchAreas);
</script>

<style scoped>
.common-areas-page { max-width: 1200px; margin: 0 auto; padding: 20px; box-sizing: border-box; }
.header-section { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; gap: 15px; }
.header-text h1 { margin: 0 0 5px 0; font-size: 32px; color: #333; }
.header-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

.areas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.area-card { display: flex; flex-direction: column; justify-content: space-between; padding: 20px; }
.area-info h3 { margin: 0 0 10px; font-size: 22px; color: #222; }
.area-info p { margin: 5px 0; font-size: 14px; }
.fee-badge { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 11px; padding: 2px 7px; border-radius: 20px; margin-left: 6px; font-weight: 700; }
.area-actions { display: flex; gap: 10px; margin-top: 20px; flex-direction: column; }
.w-100 { width: 100%; }

.btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.2s; font-size: 14px; display: inline-flex; justify-content: center; align-items: center; }
.btn:hover { opacity: 0.85; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-sm { padding: 6px 14px; font-size: 13px; }
.btn-primary { background-color: #4f46e5; color: white; }
.btn-secondary { background-color: #e2e8f0; color: #334155; }
.btn-warning { background-color: #f59e0b; color: white; }
.btn-success { background-color: #10b981; color: white; }
.btn-danger { background-color: #ef4444; color: white; }
.btn-icon { padding: 8px 12px; font-size: 16px; }

.badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
.badge-pendiente { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-aprobada { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.badge-rechazada { background-color: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; }

/* Modales */
.custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(15,23,42,0.7); backdrop-filter: blur(4px); z-index: 10000; display: flex; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; }
.custom-modal-overlay.top-aligned { align-items: flex-start; padding-top: 40px; overflow-y: auto; }
.custom-modal-content { background: #ffffff; padding: 25px; border-radius: 16px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-sizing: border-box; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); }
.cal-modal-content { max-width: 950px; }
.list-modal { max-width: 600px; }

.modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
.modal-header h2 { font-size: 24px; color: #1e293b; margin: 0; }
.btn-close-modal { background: #f1f5f9; color: #64748b; border: none; border-radius: 50%; width: 36px; height: 36px; font-size: 16px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: 0.2s; flex-shrink: 0; }
.btn-close-modal:hover { background: #fee2e2; color: #ef4444; }

/* Forms */
.form-container { display: flex; flex-direction: column; gap: 15px; }
.form-row { display: flex; gap: 15px; flex-wrap: wrap; }
.half-width { flex: 1; min-width: 140px; }
.form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #475569; }
.form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 15px; color: #334155; box-sizing: border-box; background: #fff; }
.form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.field-hint { font-size: 12px; color: #64748b; margin-top: 5px; font-style: italic; }

/* Fee Section */
.fee-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; }
.section-title { margin: 0 0 12px; font-size: 15px; color: #334155; }
.fee-type-options { display: flex; gap: 10px; flex-wrap: wrap; }
.fee-option { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: #64748b; transition: 0.2s; flex: 1; min-width: 90px; }
.fee-option input[type="radio"] { display: none; }
.fee-option:hover { border-color: #a5b4fc; background: #eef2ff; }
.fee-option.active { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; }
.fee-option-icon { font-size: 20px; }

/* Fee Preview */
.fee-preview { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px 15px; margin-top: 5px; }
.fee-preview-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #065f46; padding: 3px 0; }
.fee-total { font-size: 16px; font-weight: 800; color: #059669; }

/* Calendar */
.calendar-layout { display: flex; gap: 25px; flex-wrap: wrap; align-items: stretch; }
.calendar-wrapper { flex: 3; min-width: 320px; height: 500px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; box-sizing: border-box; }
.reservation-form { flex: 2; min-width: 280px; background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-sizing: border-box; }
.help-text { font-size: 13px; color: #0284c7; background: #f0f9ff; padding: 10px; border-radius: 6px; border: 1px solid #bae6fd; margin-bottom: 15px; font-style: italic; }

/* Lists */
.scrollable-list { display: flex; flex-direction: column; gap: 15px; }
.list-card { display: flex; justify-content: space-between; align-items: flex-start; border: 1px solid #e2e8f0; border-left: 4px solid #e2e8f0; border-radius: 12px; padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.list-card.border-aprobada { border-left-color: #10b981; }
.list-card.border-rechazada { border-left-color: #ef4444; }
.list-card.border-pendiente { border-left-color: #f59e0b; }
.datetime-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 5px; font-size: 13px; }
.rejection-note { margin-top: 10px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px; font-size: 13px; color: #9f1239; }
.card-status { flex-shrink: 0; margin-left: 15px; }
.empty-state { text-align: center; color: #94a3b8; padding: 50px 0; font-size: 16px; font-style: italic; }

@media (max-width: 768px) {
    .calendar-layout { flex-direction: column; }
    .calendar-wrapper { height: 380px; }
    .list-card { flex-direction: column; gap: 12px; }
}

:deep(.fc) { font-family: inherit; font-size: 13px; }
:deep(.fc-event) { font-size: 11px; padding: 2px 4px; border-radius: 4px; font-weight: bold; border: none; }
:deep(.fc-toolbar-title) { font-size: 18px !important; font-weight: 800; text-transform: capitalize; color: #1e293b; }
:deep(.fc .fc-button-primary) { background-color: #fff !important; color: #4f46e5 !important; border: 1px solid #cbd5e1 !important; font-weight: bold; text-transform: capitalize; border-radius: 6px; }
:deep(.fc .fc-button-primary:hover) { background-color: #f8fafc !important; }
:deep(.fc .fc-button-active) { background-color: #e0e7ff !important; border-color: #a5b4fc !important; color: #3730a3 !important; }
:deep(.fc-theme-standard td), :deep(.fc-theme-standard th) { border-color: #e2e8f0; }
:deep(.fc-col-header-cell) { background-color: #f8fafc; padding: 8px 0; color: #475569; }
</style>
