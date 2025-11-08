<template>
  <nav class="navbar fixed-top">
    <!-- Partie gauche avec Logo cliquable -->
    <div class="nav-left">
      <router-link to="/employe">
        <Logo />
      </router-link>
    </div>
    
    <!-- Partie centrale -->
    <div class="nav-center">
      <Menunavemploye/>
    </div>
    
    <!-- Partie droite avec Profil cliquable -->
    <div class="nav-right">
      <router-link to="/employe/profil">
        <Profil/>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import Logo from './Logo.vue';
import Menunavemploye from './Menunavemploye.vue';
import Profil from './Profil.vue';
</script>

<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  background: var(--primary-color);
  height: 70px;
  gap: 20px;
}

.nav-left {
  flex-shrink: 0;
}

.nav-left a {
  text-decoration: none;
}

.nav-center {
  display: flex;
  align-items: center;
  gap: 25px;
  flex: 1;
  justify-content: center;
}

.nav-right {
  flex-shrink: 0;
  margin-left: auto;
}

.nav-right a {
  text-decoration: none;
}
</style>