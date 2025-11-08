<template>
  <div id="app">
    <router-view/>
  </div>
</template>

<script setup>
// Suppression de l'import inutile de 't'
</script>

<style>
/* Styles globaux */
body {
  margin: 0;
  padding: 0;
  font-family: 'Arial', sans-serif;
  background-color: var(--primary-color);
}

#app {
  min-height: 100vh;
}
</style>