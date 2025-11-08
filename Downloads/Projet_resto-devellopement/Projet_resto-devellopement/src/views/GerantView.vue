<template>
  <div class="gerant-layout">
    <SidebarGerant />
    <main class="main-content">
      <router-view></router-view>
    </main>
  </div>
</template>

<script setup>
import SidebarGerant from '../components/SidebarGerant.vue';
</script>

<style scoped>
.gerant-layout {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  margin-left: 280px; /* Largeur de la sidebar */
  background: var(--success-color);
  min-height: 100vh;
}

/* Responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 70px;
  }
}
</style>