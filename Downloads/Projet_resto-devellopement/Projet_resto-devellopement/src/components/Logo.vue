<template>
  <a class="navbar-brand" href="#" @click.prevent="goHome">
    <span class="logo">MiamMiam</span>
  </a>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const goHome = () => {
  // Redirige vers la page d'accueil selon le contexte (gerant ou employe)
  if (router.currentRoute.value.path.includes('/gerant')) {
    router.push('/gerant');
  } else if (router.currentRoute.value.path.includes('/employe')) {
    router.push('/employe');
  } else {
    router.push('/'); // Redirection par défaut
  }
};
</script>

<style>
.logo {
  font-family: "Parisienne", cursive;
  font-weight: 400;
  font-style: normal;
  color: white; /* Assure la visibilité sur fond coloré */
  font-size: 1.5rem;
}

.navbar-brand {
  text-decoration: none;
  cursor: pointer;
}
</style>