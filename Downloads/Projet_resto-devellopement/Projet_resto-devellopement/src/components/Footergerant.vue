<template>
  <Footer :menus="customMenus" />
</template>

<script setup>
// Définition de customMenus
const customMenus = [
  {
    title: 'Menus',
    items: [
      { text: 'Accueil', url: '/' },
      { text: 'Commandes', url: '#' },
      { text: 'Comptes', url: '#' },
      { text: 'Evenements', url: '#' },
      { text: 'Statistiques', url: '#' }
    ]
  }
]
</script>

<style scoped>
@import "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";

/* Couleur de fond ajustée comme dans l’image */
footer {
  background-color: var(--primary-color) !important;
}
.text{
  color: var(--success-color);
}

</style>