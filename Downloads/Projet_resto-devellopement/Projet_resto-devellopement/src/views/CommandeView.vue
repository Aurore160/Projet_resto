<template>
  <div class="commande-view">
    <Commande :orders="commandesData" />
  </div>
</template>

<script setup>
import Commande from '../components/Commande.vue';

const commandesData = [
  {
    id: 1,
    dishName: "Poulet Braisé",
    dishDescription: "Poulet accompagné de plantain et légumes",
    dishImage: "/images/poulet.jpg",
    unitPrice: "5,000",
    quantity: 2,
    authorName: "Jean Dupont",
    authorAvatar: "/images/avatar1.jpg",
    status: "en_cours",
    date: "2024-01-15"
  }
  // ... autres données
];
</script>

<style scoped>
.commande-view {
  padding: 2rem;
  min-height: 100vh;
}
</style>