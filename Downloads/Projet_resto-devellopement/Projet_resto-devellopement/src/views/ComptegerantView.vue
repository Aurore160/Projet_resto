<template>
  <div class="compte-gerant-view">
    <Compte :allowed-roles="['employe']" title="Création de Comptes Employés" />
  </div>
</template>

<script setup>
import Compte from '../components/Creation.vue';
</script>