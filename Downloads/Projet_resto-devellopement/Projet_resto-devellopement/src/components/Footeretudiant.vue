<template>
  <Footer :menus="customMenus" />
</template>

<script setup>
const customMenus = [
  {
    title: 'Menus',
    items: [
      { text: 'Accueil', url: '/' },
      { text: 'Menu', url: '#' },
      { text: 'Evenements', url: '#' },
      { text: 'Historique', url: '#' },
    ]
  }
]
</script>

<style scoped>
.btn{
  background-color: var(--primary-color);
  /* box-shadow: 0 0px 6px rgba(0, 0, 0, 0.1); */
  border: var(--primary-color);
}
.btn:hover{
  background-color: var(--accent-color);
}
</style>