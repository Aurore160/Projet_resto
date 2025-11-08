<template>
  <Commandemodif 
    :orders="commandesData"
    @status-updated="handleStatusUpdate"
  />
</template>

<script setup>
import Commandemodif from '../components/Commandemodif.vue'

const commandesData = [
  // Vos données de commandes existantes...
]

const handleStatusUpdate = (event) => {
  console.log('Mise à jour du statut:', event)
}
</script>

<!-- SUPPRIMER les styles s'il y en a, car gérés par le parent -->