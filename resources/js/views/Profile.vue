<template>
  <div class="profile-page">
    <header class="profile-header">
        <h1>Mi Perfil de Residente</h1>
        <p>Mantén tus datos y preferencias actualizados.</p>
    </header>

    <div class="profile-tabs flex gap-4 mb-6">
        <button @click="activeTab = 'info'" class="btn" :class="activeTab === 'info' ? 'btn-primary' : 'btn-secondary'">Mi Información</button>
        <button @click="activeTab = 'requests'" class="btn" :class="activeTab === 'requests' ? 'btn-primary' : 'btn-secondary'">Mis Solicitudes</button>
        <button @click="activeTab = 'prefs'" class="btn" :class="activeTab === 'prefs' ? 'btn-primary' : 'btn-secondary'">Preferencias</button>
    </div>

    <div v-show="activeTab === 'info'" class="profile-grid">
        <!-- Left: Basic Info -->
        <div class="glass-panel info-card">
            <div class="avatar-edit">
                <div class="avatar-container" @click="triggerAvatarUpload">
                    <img :src="form.avatar || 'https://via.placeholder.com/150'" class="profile-avatar" />
                    <div class="avatar-overlay"><span>Cambiar Foto</span></div>
                </div>
                <input type="file" ref="avatarInput" @change="handleAvatarUpload" style="display: none" accept="image/*" />
                <div class="avatar-info mt-2">
                    <span v-if="uploadingAvatar" class="upload-badge">Subiendo...</span>
                </div>
            </div>

            <hr class="divider" />

            <div class="form-group mt-4">
                <label>Nombres</label>
                <input type="text" v-model="form.name" />
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" v-model="form.last_name" />
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" v-model="form.phone" />
            </div>
            <div class="form-group">
                <label>WhatsApp</label>
                <input type="text" v-model="form.whatsapp" />
            </div>
        </div>

        <!-- Right: Properties -->
        <div class="glass-panel properties-card">
            <div class="card-header flex justify-between items-center mb-4">
                <h3>Mis Propiedades</h3>
                <button v-if="canManageProperties" @click="addProperty" class="btn btn-secondary btn-sm">+ Agregar</button>
            </div>

            <div class="properties-list">
                <div v-for="(prop, index) in form.properties" :key="index" class="property-item">
                    <div class="prop-inputs">
                        <div class="input-group"><label>Torre</label><input type="text" v-model="prop.tower" :disabled="!canManageProperties" /></div>
                        <div class="input-group"><label>Apto</label><input type="text" v-model="prop.apartment" :disabled="!canManageProperties" /></div>
                        <div class="input-group">
                            <label>Vínculo</label>
                            <select v-model="prop.type" :disabled="!canManageProperties">
                                <option value="propietario">Propietario</option>
                                <option value="arrendatario">Arrendatario</option>
                            </select>
                        </div>
                    </div>
                    <button v-if="canManageProperties" @click="removeProperty(index)" class="btn-remove">×</button>
                </div>
            </div>

            <div class="mt-8 border-top pt-4">
                <div v-if="propertiesChanged && !isAdmin" class="security-warning mb-4">⚠️ Al guardar, tu perfil quedará pendiente de aprobación.</div>
                <button @click="saveProfile" class="btn btn-primary w-full" :disabled="loading">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <!-- Tab Solicitudes -->
    <div v-show="activeTab === 'requests'" class="requests-view">
        <div v-if="loadingRequests" class="text-center py-10">Cargando...</div>
        <div v-else class="requests-history">
            <div v-for="req in userRequests" :key="req.id" class="glass-panel mb-4 p-5 req-history-card">
                <div class="flex justify-between mb-2">
                    <span class="badge" :class="'badge-' + getStatusClass(req.status)">{{ req.status }}</span>
                    <span class="text-xs text-secondary">{{ formatDate(req.created_at) }}</span>
                </div>
                <strong>{{ req.title }}</strong>
                <p class="text-sm italic">"{{ req.description }}"</p>
                <div v-if="req.admin_notes" class="mt-3 p-3 bg-slate-100 rounded text-sm text-secondary">
                    <b>Admin:</b> {{ req.admin_notes }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Preferencias -->
    <div v-show="activeTab === 'prefs'" class="prefs-view glass-panel p-8">
        <h3>Ajustes de Notificación</h3>
        <p class="text-secondary mb-6">Configura cómo deseas recibir avisos de la administración.</p>

        <div class="setting-row flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
            <div>
                <strong class="block">Notificaciones de Escritorio</strong>
                <span class="text-sm text-secondary">Recibe alertas visuales en tu navegador incluso si no estás en esta pestaña.</span>
            </div>
            <div class="flex items-center gap-4">
                <label class="switch">
                    <input type="checkbox" v-model="form.wants_desktop_notifications" @change="toggleDesktopNotif">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div class="setting-row flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 mt-4">
            <div>
                <strong class="block">Alertas por Correo Electrónico</strong>
                <span class="text-sm text-secondary">Te avisaremos a tu email registrado sobre anuncios importantes y cambios de estado.</span>
            </div>
            <div class="flex items-center gap-4">
                <label class="switch">
                    <input type="checkbox" v-model="form.wants_email_notifications">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div class="setting-row flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 mt-4">
            <div>
                <strong class="block">Notificaciones por WhatsApp</strong>
                <span class="text-sm text-secondary">Recibe avisos y recordatorios directamente a tu celular.</span>
            </div>
            <div class="flex items-center gap-4">
                <label class="switch">
                    <input type="checkbox" v-model="form.wants_whatsapp_notifications">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div v-if="form.wants_desktop_notifications" class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100 text-blue-800 text-sm">
            💡 Para que funcionen correctamente, asegúrate de permitir las notificaciones en la barra de direcciones de tu navegador.
        </div>

        <div class="mt-8">
             <button @click="saveProfile" class="btn btn-primary" :disabled="loading">Guardar Preferencias</button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const auth = useAuthStore();
const loading = ref(false);
const activeTab = ref('info');
const userRequests = ref([]);
const loadingRequests = ref(false);
const form = ref({ name: '', last_name: '', phone: '', whatsapp: '', avatar: '', properties: [], wants_desktop_notifications: false });
const initialProperties = ref([]);
const propertiesChanged = ref(false);

const isAdmin = computed(() => auth.user?.roles?.some(r => r.name === 'admin' || r.name === 'master'));
const canManageProperties = computed(() => isAdmin.value || (auth.user?.all_permissions?.some(p => p.name === 'propiedades.gestionar')));

const fetchUserRequests = async () => {
    loadingRequests.value = true;
    try { const { data } = await axios.get('/api/community-requests'); userRequests.value = data.requests.data; }
    catch (e) { console.error(e); } finally { loadingRequests.value = false; }
};

watch(activeTab, (t) => { if (t === 'requests') fetchUserRequests(); });

onMounted(() => {
    if (auth.user) {
        form.value = { ...form.value, ...auth.user };
        // Sync preferences
        form.value.wants_desktop_notifications = !!auth.user.wants_desktop_notifications;
        form.value.wants_email_notifications = !!auth.user.wants_email_notifications;
        form.value.wants_whatsapp_notifications = !!auth.user.wants_whatsapp_notifications;
        
        const props = auth.user.properties?.map(p => ({ tower: p.tower, apartment: p.apartment, type: p.type })) || [];
        form.value.properties = JSON.parse(JSON.stringify(props));
        initialProperties.value = JSON.parse(JSON.stringify(props));
    }
});

watch(() => form.value.properties, (val) => { propertiesChanged.value = JSON.stringify(val) !== JSON.stringify(initialProperties.value); }, { deep: true });

const toggleDesktopNotif = async (e) => {
    if (e.target.checked) {
        if (!("Notification" in window)) { Swal.fire('Error', 'Tu navegador no soporta notificaciones', 'error'); form.value.wants_desktop_notifications = false; return; }
        const permission = await Notification.requestPermission();
        if (permission !== "granted") { Swal.fire('Permiso Denegado', 'Debes habilitar las notificaciones en el navegador.', 'warning'); form.value.wants_desktop_notifications = false; }
    }
};

const saveProfile = async () => {
    let observation = '';
    if (propertiesChanged.value && !isAdmin.value) {
        const { value: obs, isConfirmed } = await Swal.fire({ title: 'Motivo del Cambio', input: 'textarea', showCancelButton: true, preConfirm: (v) => v || Swal.showValidationMessage('Obligatorio') });
        if (!isConfirmed) return;
        observation = obs;
    }
    loading.value = true;
    try {
        await auth.updateProfile({ ...form.value, observation });
        Swal.fire({ title: '¡Guardado!', icon: 'success', shadow: true, timer: 1500, showConfirmButton: false });
    } catch (e) { Swal.fire('Error', 'Fallo al guardar', 'error'); } finally { loading.value = false; }
};
const formatDate = (d) => new Date(d).toLocaleString();
const getStatusClass = (s) => s === 'pendiente' ? 'warning' : (s === 'aprobada' ? 'success' : 'danger');
const triggerAvatarUpload = () => document.querySelector('input[type="file"]').click();
const handleAvatarUpload = async (e) => {
    const f = e.target.files[0]; if (!f) return;
    const fd = new FormData(); fd.append('avatar', f);
    try { const { data } = await axios.post('/api/user/avatar', fd); form.value.avatar = data.avatar; } catch (e) { Swal.fire('Error', 'Subida fallida', 'error'); }
};
const addProperty = () => form.value.properties.push({ tower: '', apartment: '', type: 'propietario' });
const removeProperty = (i) => form.value.properties.splice(i, 1);
</script>

<style scoped>
.profile-page { max-width: 1100px; }
.avatar-container { position: relative; width: 140px; height: 140px; border-radius: 40px; overflow: hidden; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.profile-avatar { width: 100%; height: 100%; object-fit: cover; }
.avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: white; opacity: 0; transition: 0.3s; }
.avatar-container:hover .avatar-overlay { opacity: 1; }
.property-item { background: #f8fafc; padding: 1rem; border-radius: 12px; display: flex; gap: 15px; margin-bottom: 10px; align-items: center; }
.prop-inputs { display: grid; grid-template-columns: 80px 80px 1fr; gap: 10px; flex: 1; }
.btn-remove { background: #fee2e2; color: #ef4444; border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; }

/* Switch Style */
.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: var(--primary); }
input:checked + .slider:before { transform: translateX(24px); }

.req-history-card { border-left: 5px solid #cbd5e1; }
.badge-warning { background: #fef9c3; color: #854d0e; }
.badge-success { background: #dcfce7; color: #166534; }
.badge-danger { background: #fee2e2; color: #991b1b; }
@media (min-width: 900px) { .profile-grid { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; } }
</style>
