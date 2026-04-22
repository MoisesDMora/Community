<template>
  <div class="settings-page">
    <h1>Configuración del Sistema</h1>
    <p class="text-secondary mb-8">Personaliza la apariencia y el comportamiento de tu comunidad.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Columna 1: General y Colores -->
        <div class="glass-panel">
            <h3 class="mb-6 flex items-center gap-2"><span>🎨</span> Apariencia y Marca</h3>
            
            <form @submit.prevent="saveGeneralSettings">
                <div class="form-group">
                    <label>Nombre del Residencial / Conjunto</label>
                    <input type="text" v-model="form.app_name" placeholder="Ej: Mirador del Parque" />
                </div>

                <div class="color-grid mt-6">
                    <div class="form-group">
                        <label>Color Primario (Botones, Acciones)</label>
                        <div class="flex items-center gap-4">
                            <input type="color" v-model="form.app_primary_color" class="color-picker" @change="updateGlow" />
                            <input type="text" v-model="form.app_primary_color" class="hex-text" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Color Destacado / Hover</label>
                        <div class="flex items-center gap-4">
                            <input type="color" v-model="form.app_primary_hover_color" class="color-picker" />
                            <input type="text" v-model="form.app_primary_hover_color" class="hex-text" />
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-top pt-6">
                    <button type="submit" class="btn btn-primary w-full" :disabled="saving">
                        {{ saving ? 'Guardando...' : 'Guardar Cambios Visuales' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Columna 2: Fondos y Login -->
        <div class="glass-panel">
            <h3 class="mb-6 flex items-center gap-2"><span>🖼️</span> Interfaz de Acceso</h3>
            
            <div class="form-group">
                <label>Fondo de Pantalla (Login)</label>
                <div class="background-preview-container mb-4" :style="backgroundPreviewStyle">
                    <div v-if="!form.login_background_image" class="empty-preview">Sin imagen de fondo</div>
                </div>
                
                <div class="file-upload-box">
                    <input type="file" @change="handleBackgroundUpload" accept="image/*" id="bg-upload" class="hidden-input" />
                    <label for="bg-upload" class="upload-trigger">
                        {{ uploadingBg ? 'Subiendo archivo...' : 'Seleccionar Nueva Imagen' }}
                    </label>
                </div>
                <p class="text-xs text-secondary mt-2">Se recomienda una resolución de 1920x1080px (Máx 4MB).</p>
            </div>

            <div class="mt-8 border-top pt-6">
                <p class="text-sm text-secondary">Esta imagen se mostrará a todos los residentes en la pantalla de inicio de sesión.</p>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const saving = ref(false);
const uploadingBg = ref(false);
const form = ref({
    app_name: '',
    app_primary_color: '#4f46e5',
    app_primary_hover_color: '#4338ca',
    login_background_image: ''
});

const backgroundPreviewStyle = computed(() => {
    return form.value.login_background_image 
        ? { backgroundImage: `url(${form.value.login_background_image})`, backgroundSize: 'cover', backgroundPosition: 'center' }
        : { background: '#f1f5f9' };
});

const fetchSettings = async () => {
    try {
        const { data } = await axios.get('/api/settings');
        form.value = { ...form.value, ...data.settings };
    } catch (e) { console.error(e); }
};

const saveGeneralSettings = async () => {
    saving.value = true;
    try {
        await axios.post('/api/admin/settings', form.value);
        
        // Apply changes immediately to the root style
        document.documentElement.style.setProperty('--primary', form.value.app_primary_color);
        document.documentElement.style.setProperty('--primary-hover', form.value.app_primary_hover_color);
        document.documentElement.style.setProperty('--primary-glow', form.value.app_primary_color + '26');
        
        Swal.fire({ title: 'Configuración Guardada', icon: 'success', timer: 1500, showConfirmButton: false });
    } catch (e) {
        Swal.fire('Error', 'No se pudo guardar la configuración', 'error');
    } finally {
        saving.value = false;
    }
};

const handleBackgroundUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 4 * 1024 * 1024) {
        Swal.fire('Archivo muy grande', 'El máximo permitido es 4MB', 'warning');
        return;
    }

    uploadingBg.value = true;
    const formData = new FormData();
    formData.append('background', file);

    try {
        const { data } = await axios.post('/api/admin/settings/background', formData);
        form.value.login_background_image = data.url;
        Swal.fire({ title: 'Imagen cargada', icon: 'success', timer: 1000, showConfirmButton: false });
    } catch (e) {
        Swal.fire('Error', 'No se pudo subir la imagen', 'error');
    } finally {
        uploadingBg.value = false;
    }
};

const updateGlow = () => {
    // Optional: could calculate auto-hover or auto-glow here if text is changed manually
};

onMounted(fetchSettings);
</script>

<style scoped>
.settings-page { padding: 1rem; }
.color-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
.color-picker { width: 50px; height: 50px; border: none; border-radius: 12px; cursor: pointer; padding: 0; background: transparent; }
.hex-text { flex: 1; font-family: monospace; text-transform: uppercase; }

.background-preview-container { height: 180px; border-radius: 16px; display: flex; align-items: center; justify-content: center; border: 2px dashed #e2e8f0; }
.empty-preview { color: #94a3b8; font-size: 0.9rem; font-weight: 600; }

.file-upload-box { position: relative; }
.hidden-input { opacity: 0; position: absolute; inset: 0; cursor: pointer; }
.upload-trigger { display: block; background: #eef2ff; color: #4f46e5; padding: 1rem; border-radius: 12px; text-align: center; font-weight: 700; cursor: pointer; transition: 0.2s; }
.upload-trigger:hover { background: #e0e7ff; }

.w-full { width: 100%; }
.mt-2 { margin-top: 0.5rem; }
.mt-6 { margin-top: 1.5rem; }
.pt-6 { padding-top: 1.5rem; }
.border-top { border-top: 1px solid #f1f5f9; }
</style>
