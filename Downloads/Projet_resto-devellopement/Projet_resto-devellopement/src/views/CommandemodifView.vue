<template>
  <Commandemodif 
    :orders="commandesData"
    @status-updated="handleStatusUpdate"
  />
</template>

<script setup>
import Commandemodif from '../components/Commandemodif.vue'

const commandesData = [
  // Vos données de commandes...
]

const handleStatusUpdate = (event) => {
  // Mettre à jour dans votre store ou API
  console.log('Mise à jour du statut:', event)
}
</script>