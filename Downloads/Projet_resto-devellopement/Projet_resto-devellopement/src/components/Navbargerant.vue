<template>
  <nav class="navbar fixed-top">
    <!-- Partie gauche -->
    <div class="nav-left">
      <Logo />
    </div>
    
    <!-- Partie centrale -->
    <div class="nav-center">
      <Menunavgerant/>
    </div>
    
    <!-- Partie droite -->
    <div class="nav-right">
      <Profil/>
    </div>
  </nav>
</template>

<script setup>

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

.nav-link {
  color: white;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 5px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}
</style>