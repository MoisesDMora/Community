<template>
  <div>
    <h1>Panel de Administración</h1>

    <!-- Gestión Usuarios -->
    <div class="glass-panel" style="margin-top: 1rem;">
      <div class="flex justify-between items-center mb-6">
          <h3>Gestión de Usuarios Residentes</h3>
          <button @click="fetchUsers" class="btn btn-secondary btn-sm">Actualizar Lista</button>
      </div>

      <!-- Filtros -->
      <div class="filters-grid mb-6">
          <div class="filter-item">
              <label>Buscar por Nombre/Email</label>
              <input type="text" v-model="filters.search" placeholder="Buscar..." @input="debouncedFetch" />
          </div>
          <div class="filter-item">
              <label>Torre</label>
              <input type="text" v-model="filters.tower" placeholder="Ej: 1" @input="debouncedFetch" />
          </div>
          <div class="filter-item">
              <label>Apto</label>
              <input type="text" v-model="filters.apartment" placeholder="Ej: 101" @input="debouncedFetch" />
          </div>
          <div class="filter-item">
              <label>Estado</label>
              <select v-model="filters.status" @change="fetchUsers">
                  <option value="">Todos</option>
                  <option value="activo">Activo</option>
                  <option value="pendiente">Pendiente</option>
                  <option value="inactivo">Inactivo</option>
              </select>
          </div>
          <div class="filter-item">
            <label>Rol</label>
            <select v-model="filters.role" @change="fetchUsers">
                <option value="">Todos</option>
                <option v-for="role in availableRoles" :key="role.id" :value="role.name">{{ role.name }}</option>
            </select>
          </div>
      </div>
      
      <div v-if="loading" class="text-center mt-4 py-8">
        <span class="badge badge-warning">Cargando residentes...</span>
      </div>
      
      <div class="table-container" v-else>
        <table>
          <thead>
            <tr>
              <th>Residente</th>
              <th>Propiedades</th>
              <th>Rol Principal</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>
                <div class="user-info">
                  <img :src="user.avatar || 'https://via.placeholder.com/40'" class="avatar" />
                  <div class="name-box">
                    <span class="full-name">{{ user.name }} {{ user.last_name || '' }}</span>
                    <div class="text-xs text-secondary">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="flex flex-wrap gap-1">
                    <div v-for="prop in user.properties" :key="prop.id" class="prop-tag">
                        T{{ prop.tower }}-{{ prop.apartment }}
                    </div>
                </div>
                <button @click="manageProperties(user)" class="btn-link mt-1">+ Gestionar Unidades</button>
              </td>
              <td>
                <select 
                    class="role-select" 
                    :value="user.roles?.[0]?.name" 
                    @change="changeUserRole(user, $event.target.value)">
                    <option v-for="role in availableRoles" :key="role.id" :value="role.name">
                        {{ role.name }}
                    </option>
                </select>
              </td>
              <td>
                <span class="badge" 
                      :class="{'badge-success': user.status === 'activo', 'badge-warning': user.status === 'pendiente', 'badge-danger': user.status === 'inactivo'}">
                  {{ user.status }}
                </span>
              </td>
              <td>
                <div class="flex gap-2">
                  <button v-if="user.wants_whatsapp_notifications && user.whatsapp" @click="openWhatsApp(user)" class="btn btn-secondary p-xs" title="Enviar WhatsApp">💬</button>
                  <button @click="notifyUser(user)" class="btn btn-secondary p-xs" title="Enviar Notificación">📧</button>
                  <button @click="manageUserPermissions(user)" class="btn btn-secondary p-xs" title="Permisos Granulares">🔑</button>
                  <button v-if="user.status !== 'activo'" @click="approveUser(user.id)" class="btn btn-success p-xs">Aprobar</button>
                  <button v-if="user.status !== 'inactivo'" @click="disapproveUser(user.id)" class="btn btn-danger p-xs">Bloquear</button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.length">
                <td colspan="5" class="text-center py-8">Ningún usuario coincide con los filtros.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const users = ref([]);
const loading = ref(true);
const availableRoles = ref([]);
const allPermissions = ref([]);
const filters = ref({
    search: '',
    tower: '',
    apartment: '',
    status: '',
    role: ''
});

let timeout = null;
const debouncedFetch = () => {
    clearTimeout(timeout);
    timeout = setTimeout(fetchUsers, 500);
};

const fetchUsers = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/admin/users', { params: filters.value });
        users.value = data.data;
    } catch (e) { console.error(e); } finally { loading.value = false; }
};

const fetchRolesAndPermissions = async () => {
    const { data } = await axios.get('/api/admin/roles-permissions');
    allPermissions.value = data.permissions;
    availableRoles.value = data.roles;
};

const manageProperties = async (user) => {
    let currentProps = JSON.parse(JSON.stringify(user.properties || []));
    const renderList = () => {
        let html = '<div id="swal-props-list" style="max-height: 300px; overflow-y: auto; text-align: left;">';
        currentProps.forEach((p, i) => {
            html += `<div style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px;">
                        <div style="flex: 1">T<strong>${p.tower}</strong> - Apt <strong>${p.apartment}</strong></div>
                        <button class="btn-swal-remove" data-index="${i}" style="background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; padding: 4px 8px; cursor: pointer;">×</button>
                    </div>`;
        });
        if (!currentProps.length) html += '<p style="color: #94a3b8; text-align: center;">Sin unidades asignadas.</p>';
        html += `</div><div style="margin-top: 20px; border-top: 1px solid #e2e8f0; pt-4; display: grid; grid-template-columns: 1fr 1fr 80px; gap: 8px;">
                <input id="new-tower" placeholder="Torre" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <input id="new-apt" placeholder="Apto" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <button id="btn-add-prop" style="background: #e0e7ff; color: #4338ca; border: none; border-radius: 6px; cursor: pointer;">Añadir</button></div>`;
        return html;
    };
    const { isConfirmed } = await Swal.fire({
        title: `Gestionar Unidades: ${user.name}`,
        html: renderList(),
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        didOpen: (popup) => {
            popup.querySelector('#btn-add-prop').addEventListener('click', () => {
                const t = popup.querySelector('#new-tower').value, a = popup.querySelector('#new-apt').value;
                if (t && a) { currentProps.push({ tower: t, apartment: a, type: 'propietario' }); Swal.update({ html: renderList() }); }
            });
            popup.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-swal-remove')) { currentProps.splice(e.target.dataset.index, 1); Swal.update({ html: renderList() }); }
            });
        }
    });

    if (isConfirmed) {
        try { await axios.post(`/api/admin/users/${user.id}/properties`, { properties: currentProps }); Swal.fire('Actualizado', 'Las unidades han sido sincronizadas', 'success'); fetchUsers(); }
        catch (e) { Swal.fire('Error', 'No se pudieron actualizar las unidades', 'error'); }
    }
};

const changeUserRole = async (user, role) => {
    try { await axios.post(`/api/admin/users/${user.id}/role`, { role }); Swal.fire({ title: 'Rol cambiado', icon: 'success', timer: 1000, showConfirmButton: false }); fetchUsers(); }
    catch (e) { Swal.fire('Error', 'Fallo al cambiar rol', 'error'); }
};

const approveUser = async (id) => { await axios.post(`/api/admin/users/${id}/approve`); fetchUsers(); };
const disapproveUser = async (id) => { await axios.post(`/api/admin/users/${id}/disapprove`); fetchUsers(); };

const notifyUser = async (user) => {
    const { value: form } = await Swal.fire({
        title: `Notificar a ${user.name}`,
        html: '<input id="sw-t" class="swal2-input" placeholder="Título"><textarea id="sw-m" class="swal2-textarea" placeholder="Mensaje"></textarea>',
        preConfirm: () => {
            const t = document.getElementById('sw-t').value, m = document.getElementById('sw-m').value;
            if(!t || !m) Swal.showValidationMessage('Obligatorio');
            return { title: t, message: m };
        }
    });
    if (form) {
        try {
            await axios.post('/api/admin/notifications/send', { user_id: user.id, title: form.title, message: form.message });
            Swal.fire({ title: 'Enviada', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire('Error', 'Fallo envío', 'error'); }
    }
};

const openWhatsApp = (u) => { if (!u.whatsapp) return; window.open(`https://wa.me/${u.whatsapp.replace(/\D/g, '')}`, '_blank'); };

const manageUserPermissions = async (u) => {
    const userPermissions = u.permissions?.map(p => p.name) || [];
    let html = `<div style="display: grid; grid-template-columns: 1fr 1fr; text-align: left;">`;
    allPermissions.value.forEach(p => {
        html += `<label><input type="checkbox" class="sw-p" value="${p.name}" ${userPermissions.includes(p.name)?'checked':''}> ${p.name}</label>`;
    });
    const { value: selected } = await Swal.fire({ title: 'Permisos', html: html + '</div>', preConfirm: () => Array.from(document.querySelectorAll('.sw-p:checked')).map(i => i.value) });
    if (selected !== undefined) { await axios.post(`/api/admin/users/${u.id}/sync-permissions`, { permissions: selected }); fetchUsers(); }
};

onMounted(() => { fetchUsers(); fetchRolesAndPermissions(); });
</script>

<style scoped>
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; }
.filter-item label { font-size: 0.75rem; margin-bottom: 6px; }
.table-container { overflow-x: auto; }
.user-info { display: flex; align-items: center; }
.avatar { width: 38px; height: 38px; border-radius: 10px; margin-right: 12px; }
.full-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
.prop-tag { background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
.btn-link { background: none; border: none; color: var(--primary); font-size: 0.75rem; font-weight: 800; cursor: pointer; padding: 0; }
.btn-link:hover { text-decoration: underline; }
.role-select { padding: 4px 8px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: var(--primary); }
.p-xs { padding: 0.35rem; font-size: 0.75rem; min-width: 32px; }
.text-xs { font-size: 0.75rem; }
</style>
