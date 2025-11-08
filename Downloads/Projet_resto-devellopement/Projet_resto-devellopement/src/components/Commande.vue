<template>
  <Basecommande
    :orders="orders"
    @status-updated="handleStatusUpdate"
  />
</template>

<script setup>
import Basecommande from './Basecommande.vue';

// Props
const props = defineProps({
  orders: {
    type: Array,
    default: () => []
  }
})

// Gérer la mise à jour du statut (ne devrait pas être appelé en mode lecture)
const handleStatusUpdate = (event) => {
  console.log('Statut mis à jour (lecture seule):', event)
}
</script>