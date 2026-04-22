<template>
  <div class="login-wrapper">
    <div class="glass-panel text-center">
      <h2>Autenticando...</h2>
      <p>Espera mientras configuramos tu sesión.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
    const token = route.query.token;
    if (token) {
        await authStore.setToken(token);
        // Checking if user needs to update profile
        if (authStore.user?.status === 'pendiente') {
            router.push('/profile');
        } else {
            router.push('/');
        }
    } else {
        router.push('/login');
    }
});
</script>

<style scoped>
.login-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
</style>
