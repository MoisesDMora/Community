<template>
  <div class="login-container" :style="{ backgroundImage: `url(${settings.login_background_image || defaultBg})` }">
    <div class="overlay"></div>
    
    <div class="login-card-wrapper">
      <div class="glass-login-card">
        <div class="login-branding">
          <div class="app-logo">
            <div class="logo-icon">🏙️</div>
          </div>
          <h1>{{ settings.app_name || 'ResidencialApp' }}</h1>
          <p>Tu comunidad, mejor organizada. <br> Inicia sesión de forma rápida y segura.</p>
        </div>

        <div class="login-main">
          <button class="google-btn" @click="loginWithGoogle" :disabled="loading">
            <svg viewBox="0 0 48 48" class="google-logo">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
            </svg>
            <span>Continuar con Google</span>
          </button>
        </div>

        <div class="login-footer">
          <p>© 2026 Gestión Residencial Multi-Torre</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const settings = ref({});
const loading = ref(false);
const defaultBg = 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&q=80&w=1920';

const loginWithGoogle = () => {
    loading.value = true;
    window.location.href = '/auth/google/redirect';
};

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/settings');
        settings.value = data.settings;
    } catch (e) {
        console.error("Error loading settings", e);
    }
});
</script>

<style scoped>
.login-container {
  height: 100vh;
  width: 100vw;
  background-size: cover;
  background-position: center;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at center, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.95) 100%);
  z-index: 1;
}

.login-card-wrapper {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 440px;
  padding: 20px;
}

.glass-login-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 32px;
  padding: 3rem 2.5rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.login-branding {
  margin-bottom: 2.5rem;
}

.logo-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: inline-block;
  background: white;
  width: 80px;
  height: 80px;
  line-height: 80px;
  border-radius: 24px;
  box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
}

h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.75rem;
  letter-spacing: -1px;
}

p {
  color: #64748b;
  font-size: 1.05rem;
  line-height: 1.6;
}

.login-main {
  margin-top: 2rem;
}

.google-btn {
  width: 100%;
  background: #ffffff;
  color: #334155;
  border: 1px solid #e2e8f0;
  padding: 1rem;
  border-radius: 16px;
  font-weight: 600;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.google-btn:hover {
  background: #f8fafc;
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
  border-color: #cbd5e1;
}

.google-btn:active {
  transform: translateY(0);
}

.google-logo {
  width: 24px;
  height: 24px;
}

.login-footer {
  margin-top: 3rem;
  font-size: 0.85rem;
  color: #94a3b8;
}

@media (max-width: 480px) {
  .glass-login-card {
    padding: 2.5rem 1.5rem;
    border-radius: 24px;
  }
  
  h1 {
    font-size: 1.8rem;
  }
}
</style>
