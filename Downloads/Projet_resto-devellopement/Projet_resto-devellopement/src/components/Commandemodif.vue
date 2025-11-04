<template>
  <Basecommande
    :orders="orders"
    @status-updated="handleStatusUpdate"
  >
    <!-- Override du slot pour le statut avec possibilité de modification -->
    <template #status-cell="{ order, updateStatus }">
      <select 
        class="status-select"
        :value="order.status"
        @change="updateStatus(order.id, $event.target.value)"
        :class="getStatusClass(order.status)"
      >
        <option value="en_cours">En cours</option>
        <option value="livre">Livré</option>
        <option value="annule">Annulé</option>
      </select>
    </template>
  </BaseCommande>
</template>

<script setup>
import Basecommande from './Basecommande.vue';

// Props
defineProps({
  orders: {
    type: Array,
    default: () => []
  }
})

// Émettre les événements
const emit = defineEmits(['status-updated'])

// Gérer la mise à jour du statut
const handleStatusUpdate = (event) => {
  emit('status-updated', event)
  // Ici vous pouvez envoyer à votre API
  console.log('Statut mis à jour:', event)
}

// Obtenir la classe CSS pour le statut
const getStatusClass = (status) => {
  return `status-${status}`
}
</script>