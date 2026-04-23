<template>
  <div class="notifications-page">
    <header class="flex justify-between items-center mb-6">
        <div>
            <h1>{{ isAdminMode ? 'Notificaciones del Conjunto' : 'Mis Notificaciones' }}</h1>
            <p class="text-secondary">{{ isAdminMode ? 'Solicitudes y actividad del sistema.' : 'Mensajes y anuncios recibidos.' }}</p>
        </div>
        <div class="flex gap-4">
            <button v-if="isAdminMode" @click="showCompose = true" class="btn btn-primary btn-sm">Nueva Notificación</button>
            <button v-if="notifications.length" @click="markAllAsRead" class="btn btn-secondary btn-sm">Marcar todas como leídas</button>
        </div>
    </header>

    <!-- Modal for Compose -->
    <div v-if="showCompose" class="modal-overlay" @click.self="showCompose = false">
        <div class="modal-content glass-panel p-6">
            <h2 class="mb-4">Enviar Notificación a Todos</h2>
            <form @submit.prevent="sendBroadcast">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" v-model="compose.title" class="form-control" required />
                </div>
                <div class="form-group mt-4">
                    <label>Mensaje</label>
                    <textarea v-model="compose.message" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group mt-4">
                    <label>Imagen (Opcional)</label>
                    <input type="file" @change="e => compose.image = e.target.files[0]" accept="image/*" class="form-control" />
                </div>
                <div class="form-group mt-4">
                    <label>Adjunto (Opcional)</label>
                    <input type="file" @change="e => compose.attachment = e.target.files[0]" class="form-control" />
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="showCompose = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="sending">
                        {{ sending ? 'Enviando...' : 'Enviar a Todos' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="loading" class="text-center py-10">
        <span class="badge badge-warning">Cargando...</span>
    </div>

    <div v-else-if="!notifications.length" class="glass-panel text-center py-12">
        <div class="empty-icon">📭</div>
        <h3>No hay notificaciones</h3>
        <p class="text-secondary">Todo está al día.</p>
    </div>

    <div v-else class="notifications-list">
        <div v-for="note in notifications" :key="note.id" 
             class="glass-panel notification-card" 
             :class="{ 'unread': !note.is_read }">
            
            <div class="card-header">
                <div class="sender-info">
                    <img :src="note.sender?.avatar || 'https://via.placeholder.com/40'" class="avatar-mini" />
                    <div class="sender-details">
                        <span class="sender-name">{{ note.sender?.name }} {{ note.sender?.last_name || '' }}</span>
                        <span class="note-time">{{ formatDate(note.created_at) }}</span>
                    </div>
                </div>
                <div class="card-actions flex gap-2">
                    <button v-if="!note.is_read" @click="markRead(note.id)" class="btn-icon" title="Marcar leída">✔️</button>
                    <button @click="deleteNote(note.id)" class="btn-icon text-danger" title="Eliminar">🗑️</button>
                </div>
            </div>

            <div class="card-body">
                <h4 class="note-title">{{ note.title }}</h4>
                <p class="note-message">{{ note.message }}</p>

                <div v-if="note.image_url" class="mt-4">
                    <img :src="note.image_url" alt="Imagen Adjunta" class="rounded max-h-60 object-contain shadow" />
                </div>
                <div v-if="note.attachment_url" class="mt-4">
                    <a :href="note.attachment_url" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-2 inline-flex">
                        <span>📄</span> Descargar Archivo Adjunto
                    </a>
                </div>

                <div v-if="note.data?.properties" class="property-detail mt-4">
                    <span class="detail-label">Detalles de Unidades:</span>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <div v-for="(p, idx) in note.data.properties" :key="idx" class="prop-badge">
                            T{{ p.tower }} - {{ p.apartment }} ({{ p.type }})
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer mt-4" v-if="isAdminMode && note.type === 'property_change'">
                <router-link :to="'/admin?search=' + note.sender?.email" class="btn btn-primary btn-sm">Gestionar Usuario</router-link>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const auth = useAuthStore();
const route = useRoute();
const notifications = ref([]);
const loading = ref(true);

const showCompose = ref(false);
const sending = ref(false);
const compose = reactive({
    title: '',
    message: '',
    image: null,
    attachment: null
});

const isAdminMode = computed(() => {
    // Determine if we are in admin "general notifications" view
    return route.path.startsWith('/admin') && auth.user?.roles?.some(r => r.name === 'admin' || r.name === 'master');
});

const fetchNotifications = async () => {
    try {
        const { data } = await axios.get('/api/notifications');
        notifications.value = data.notifications.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
};

const sendBroadcast = async () => {
    sending.value = true;
    try {
        const formData = new FormData();
        formData.append('title', compose.title);
        formData.append('message', compose.message);
        if (compose.image) formData.append('image', compose.image);
        if (compose.attachment) formData.append('attachment', compose.attachment);

        const response = await axios.post('/api/admin/notifications/send-all', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        Swal.fire({
            title: '¡Evidenciado!',
            text: response.data.message || 'Notificación enviada con éxito',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });

        showCompose.value = false;
        compose.title = '';
        compose.message = '';
        compose.image = null;
        compose.attachment = null;
    } catch (error) {
        console.error(error);
        Swal.fire({
            title: 'Error',
            text: error.response?.data?.message || 'Error al enviar la notificación',
            icon: 'error'
        });
    } finally {
        sending.value = false;
    }
};

const markRead = async (id) => {
    try {
        await axios.post(`/api/notifications/${id}/read`);
        const note = notifications.value.find(n => n.id === id);
        if (note) note.is_read = true;
        window.dispatchEvent(new Event('notifications-updated'));
    } catch (e) { console.error(e); }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.is_read = true);
        window.dispatchEvent(new Event('notifications-updated'));
        Swal.fire({ title: '¡Listo!', text: 'Todas marcadas como leídas', icon: 'success', timer: 1500, showConfirmButton: false });
    } catch (e) { console.error(e); }
};

const deleteNote = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar notificación?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/notifications/${id}`);
            notifications.value = notifications.value.filter(n => n.id !== id);
            window.dispatchEvent(new Event('notifications-updated'));
        } catch (e) { console.error(e); }
    }
};

const formatDate = (dateStr) => new Date(dateStr).toLocaleString();

onMounted(fetchNotifications);
</script>

<style scoped>
.notifications-page { max-width: 900px; padding: 2rem; }
.notifications-list { display: flex; flex-direction: column; gap: 1.5rem; }

.notification-card { border-left: 4px solid transparent; transition: all 0.3s; padding: 1.5rem; border-radius: 20px; }
.notification-card.unread { border-left-color: var(--primary); background: #fdfdff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.sender-info { display: flex; align-items: center; gap: 12px; }
.avatar-mini { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; }
.sender-details { display: flex; flex-direction: column; }
.sender-name { font-weight: 700; color: #1e293b; }
.note-time { font-size: 0.75rem; color: #94a3b8; }

.note-title { font-size: 1.1rem; color: #334155; margin-bottom: 0.5rem; font-weight: 700; }
.note-message { color: #64748b; font-style: italic; background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #f1f5f9; line-height: 1.5; }

.prop-badge { background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.btn-icon { background: none; border: none; font-size: 1.2rem; cursor: pointer; padding: 4px; opacity: 0.6; transition: 0.2s; }
.btn-icon:hover { opacity: 1; }
.text-danger { color: #ef4444; }
.empty-icon { font-size: 4rem; margin-bottom: 1rem; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; }
.modal-content { background: white; width: 90%; max-width: 500px; border-radius: 20px; overflow-y: auto; max-height: 90vh; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; font-size: 0.9rem; }
.form-control { padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; }
</style>
