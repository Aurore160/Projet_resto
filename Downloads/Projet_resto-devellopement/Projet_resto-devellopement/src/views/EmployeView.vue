<template>
  <div class="employe-view">
    <Navbaremploye />
    <div class="content">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import Navbaremploye from '../components/Navbaremploye.vue';
</script>

<style scoped>
.employe-view {
  min-height: 100vh;
}
.content {
  margin-top: 70px; /* Compensation pour la navbar fixe */
}
</style>