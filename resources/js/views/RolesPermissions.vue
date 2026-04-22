<template>
  <div class="roles-permissions-page">
    <div class="header flex justify-between items-center mb-8">
        <div>
            <h1>Roles y Permisos Granulares</h1>
            <p>Controla exactamente qué puede hacer cada perfil en la plataforma.</p>
        </div>
        <div class="flex gap-4">
            <button @click="openNewPermissionPrompt" class="btn btn-secondary btn-sm">+ Nuevo Permiso</button>
            <button @click="openNewRolePrompt" class="btn btn-primary btn-sm">+ Nuevo Rol</button>
        </div>
    </div>

    <div class="grid-layout">
        <!-- Roles List -->
        <div class="glass-panel">
            <h3>Perfiles Disponibles</h3>
            <div class="roles-list mt-4">
                <div v-for="role in roles" :key="role.id" 
                     class="role-card" 
                     :class="{ active: selectedRole?.id === role.id }"
                     @click="selectRole(role)">
                    <div class="role-info">
                        <span class="role-name">{{ role.name }}</span>
                        <span class="permission-count">{{ role.permissions?.length }} permisos</span>
                    </div>
                    <div class="flex gap-2 items-center">
                        <button v-if="role.name !== 'master' && role.name !== 'admin'" 
                                @click.stop="deleteRole(role)" 
                                class="btn-icon-delete" title="Eliminar Rol">🗑️</button>
                        <span class="arrow" v-if="selectedRole?.id === role.id">➔</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Editor -->
        <div class="glass-panel main-editor" v-if="selectedRole">
            <div class="editor-header flex justify-between items-center">
                <h2>Permisos para: <span class="text-primary">{{ selectedRole.name }}</span></h2>
                <button @click="saveRolePermissions" class="btn btn-primary" :disabled="saving">
                    {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </div>

            <div class="permissions-grid mt-6">
                <div v-for="permission in permissions" :key="permission.id" class="permission-item">
                    <div class="flex justify-between items-center w-full">
                        <label class="checkbox-container">
                            <input type="checkbox" 
                                   :value="permission.name" 
                                   v-model="selectedRolePermissions" />
                            <span class="checkmark"></span>
                            <span class="permission-label">{{ permission.name }}</span>
                        </label>
                        <button @click="deletePermission(permission)" class="btn-icon-delete-small" title="Eliminar Permiso Definitivamente">×</button>
                    </div>
                </div>
                <div v-if="!permissions.length" class="empty-state" style="grid-column: span 2;">
                    No hay permisos creados. Crea uno arriba.
                </div>
            </div>
        </div>

        <div class="glass-panel placeholder-editor" v-else>
            <div class="empty-state">
                <span>🛡️</span>
                <p>Selecciona un rol de la izquierda para editar sus permisos granulares.</p>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const roles = ref([]);
const permissions = ref([]);
const selectedRole = ref(null);
const selectedRolePermissions = ref([]);
const saving = ref(false);

const fetchData = async () => {
    try {
        const { data } = await axios.get('/api/admin/roles-permissions');
        roles.value = data.roles;
        permissions.value = data.permissions;
        
        // Refresh selected role if it exists
        if (selectedRole.value) {
            const updated = roles.value.find(r => r.id === selectedRole.value.id);
            if (updated) selectRole(updated);
            else selectedRole.value = null;
        }
    } catch (e) {
        Swal.fire('Error', 'No se cargaron los roles', 'error');
    }
};

const selectRole = (role) => {
    selectedRole.value = role;
    selectedRolePermissions.value = role.permissions.map(p => p.name);
};

const saveRolePermissions = async () => {
    saving.value = true;
    try {
        await axios.post(`/api/admin/roles/${selectedRole.value.id}/sync`, {
            permissions: selectedRolePermissions.value
        });
        Swal.fire({ title: '¡Actualizado!', text: 'Permisos sincronizados', icon: 'success', timer: 1500, showConfirmButton: false });
        fetchData();
    } catch (e) {
        Swal.fire('Error', 'No se pudieron guardar los cambios', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteRole = async (role) => {
    const { isConfirmed } = await Swal.fire({
        title: `¿Eliminar rol "${role.name}"?`,
        text: "Esta acción no se puede deshacer y afectará a los usuarios que tengan este rol.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (isConfirmed) {
        try {
            await axios.delete(`/api/admin/roles/${role.id}`);
            Swal.fire('Eliminado', 'El rol ha sido removido.', 'success');
            fetchData();
        } catch (e) {
            Swal.fire('Error', 'No se pudo eliminar el rol.', 'error');
        }
    }
};

const deletePermission = async (permission) => {
    const { isConfirmed } = await Swal.fire({
        title: `¿Eliminar permiso "${permission.name}"?`,
        text: "Se eliminará de todos los roles y usuarios que lo tengan asignado.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (isConfirmed) {
        try {
            await axios.delete(`/api/admin/permissions/${permission.id}`);
            Swal.fire('Eliminado', 'El permiso ha sido borrado del sistema.', 'success');
            fetchData();
        } catch (e) {
            Swal.fire('Error', 'No se pudo borrar el permiso.', 'error');
        }
    }
};

const openNewPermissionPrompt = async () => {
    const { value: name } = await Swal.fire({
        title: 'Crear Nuevo Permiso',
        input: 'text',
        inputLabel: 'Nombre del permiso (ej. usuarios.borrar)',
        inputPlaceholder: 'Ingresa el nombre...',
        showCancelButton: true
    });

    if (name) {
        try {
            await axios.post('/api/admin/permissions', { name });
            Swal.fire('Creado', 'Permiso disponible', 'success');
            fetchData();
        } catch (e) {
            Swal.fire('Error', 'Ese permiso ya existe o es inválido', 'error');
        }
    }
};

const openNewRolePrompt = async () => {
    const { value: name } = await Swal.fire({
        title: 'Crear Nuevo Rol',
        input: 'text',
        inputLabel: 'Nombre del rol (ej. supervisor)',
        inputPlaceholder: 'Ingresa el nombre...',
        showCancelButton: true
    });

    if (name) {
        try {
            await axios.post('/api/admin/roles', { name });
            Swal.fire('Creado', 'Rol disponible', 'success');
            fetchData();
        } catch (e) {
            Swal.fire('Error', 'Ese rol ya existe', 'error');
        }
    }
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.grid-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 2rem;
}

@media (max-width: 900px) {
    .grid-layout {
        grid-template-columns: 1fr;
    }
}

.role-card {
    background: #f8fafc;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    cursor: pointer;
    border: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.role-card:hover {
    border-color: var(--primary);
    background: #ffffff;
}

.role-card.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.role-info { display: flex; flex-direction: column; }
.role-name { font-weight: 700; text-transform: capitalize; }
.permission-count { font-size: 0.75rem; opacity: 0.8; }

.btn-icon-delete {
    background: transparent;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 5px;
    border-radius: 6px;
    transition: background 0.2s;
}
.role-card:not(.active) .btn-icon-delete:hover { background: #fee2e2; }
.role-card.active .btn-icon-delete { filter: brightness(0) invert(1); }

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.permission-item {
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
    display: flex;
}

.btn-icon-delete-small {
    background: #fee2e2;
    color: #ef4444;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Custom Checkbox */
.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    position: relative;
    padding-left: 35px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #475569;
}
.checkbox-container input { visibility: hidden; position: absolute; }
.checkmark {
    position: absolute;
    top: -2px; left: 0;
    height: 22px; width: 22px;
    background-color: #e2e8f0;
    border-radius: 6px;
}
.checkbox-container:hover input ~ .checkmark { background-color: #cbd5e1; }
.checkbox-container input:checked ~ .checkmark { background-color: var(--primary); }
.checkmark:after {
    content: ""; position: absolute; display: none;
    left: 8px; top: 4px; width: 5px; height: 10px;
    border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
}
.checkbox-container input:checked ~ .checkmark:after { display: block; }

.empty-state { text-align: center; color: #94a3b8; }
</style>
