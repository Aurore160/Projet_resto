<script setup></script>

<template>
  <transition name="fade" mode="out-in">
    <router-view />
  </transition>
  
</template>

<style scoped>
body{
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background-color: #000;
  color: #cfbd97;

}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
