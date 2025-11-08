<template>
  <div class="compte-gerant-view">
    <Compte :allowed-roles="['employe']" title="Création de Comptes Employés" />
  </div>
</template>

<script setup>
import Compte from '../components/Compte.vue';
</script>

<style scoped>
.compte-gerant-view {
  padding: 2rem;
  min-height: 100vh;
}
</style>