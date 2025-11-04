<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <img :src="logoImage" alt="ZEDUC" class="logo-image" v-if="logoImage">
        <span class="logo-text">ZEDUC</span>
      </div>
      
      <nav class="sidebar-nav">
        <router-link v-for="item in navItems" :key="item.to" :to="item.to" class="nav-item">
          <span class="nav-icon">
            <span class="icon-placeholder">{{ getInitials(item.text) }}</span>
          </span>
          <span class="nav-text">{{ item.text }}</span>
        </router-link>
      </nav>
      
      <div class="sidebar-footer">
        <button @click="deconnexion" class="btn-deconnexion">
          <span class="nav-icon">
            <span class="icon-placeholder">D</span>
          </span>
          <span class="nav-text">Déconnexion</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
      <header class="admin-header">
        <h1 class="page-title">{{ currentPageTitle }}</h1>
        <div class="user-info">
          <span>Admin</span>
          <div class="user-avatar">A</div>
        </div>
      </header>
      
      <div class="admin-content">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const logoImage = ref('/src/images/ZEDUC.png')

// navigation items sans icônes images
const navItems = ref([
  { to: '/admin/dashboard', text: 'Dashboard' },
  { to: '/admin/utilisateurs', text: 'Utilisateurs' },
  { to: '/admin/employes', text: 'Employés' },
  { to: '/admin/commandes', text: 'Commandes' },
  { to: '/admin/menu', text: 'Menu & Promotions' }, // Nouvel item
  { to: '/admin/statistiques', text: 'Statistiques' },
  { to: '/admin/parametres', text: 'Paramètres' }
])

const pageTitles = {
  '/admin/dashboard': 'Tableau de Bord',
  '/admin/utilisateurs': 'Gestion des Utilisateurs',
  '/admin/employes': 'Gestion des Employés',
  '/admin/commandes': 'Gestion des Commandes',
  '/admin/statistiques': 'Statistiques Avancées',
  '/admin/menu': 'Menu et Promotions',
  '/admin/parametres': 'Paramètres'
}

const currentPageTitle = computed(() => {
  return pageTitles[route.path] || 'Tableau de Bord'
})

// Fonction pour obtenir les initiales du texte
const getInitials = (text) => {
  return text.charAt(0).toUpperCase()
}

const deconnexion = () => {
  // Logique de déconnexion
  router.push('/')
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  height: 100vh; /* Empêche le scroll global */
  overflow: hidden; /* Cache tout débordement global */
}

/* Sidebar */
.admin-sidebar {
  width: 280px;
  background: #060606;
  color: #cfbd97;
  display: flex;
  flex-direction: column;
  flex-shrink: 0; /* Empêche la sidebar de rétrécir */
}

.sidebar-header {
  padding: 2rem 1.0rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.logo-image {
  height: 50px; /* Taille appropriée pour le logo */
  width: auto;
  margin-bottom: 0;
}

.logo-text {
  font-size: 1.0rem;
  font-weight: bold;
  color: #E4DBC6;
  margin: 0;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem; /* Espacement réduit pour mieux s'adapter */
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 1rem 0.5rem;
  color: #E4DBC6; /* Texte en blanc cassé */
  text-decoration: none;
  transition: all 0.3s ease;
  border-radius: 10px; /* Bords arrondis pour l'effet souhaité */
  margin: 0 1rem;
  font-weight: bold;
  border: 1px solid transparent;
}

.nav-item:hover {
  background-color: #a89f91;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.nav-item.router-link-active {
  background-color: #a89f91;
  color: white;
  border-color: #E4DBC6;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.nav-icon {
  margin-right: 1rem;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-placeholder {
  width: 30px;
  height: 30px;
  background-color: #E4DBC6;
  color: #060606;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.9rem;
}

.nav-text {
  font-weight: 600;
}

/* Règles pour le texte en gras */
.sidebar-nav .nav-item .nav-text,
.sidebar-nav .nav-item {
  font-weight: 600;
}

.nav-item.router-link-active .nav-text {
  font-weight: 700;
}

.sidebar-footer {
  padding: 1rem 1.5rem;
  margin-top: auto;
}

.btn-deconnexion {
  display: flex;
  align-items: center;
  width: 100%;
  background: none;
  border: none;
  color: #E4DBC6;
  padding: 1rem 1.5rem;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin: 0;
  font-weight: bold;
  border: 2px solid transparent;
}

.btn-deconnexion:hover {
  background-color: #a89f91;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Main Content */
.admin-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden; /* Empêche le débordement du main */
}

.admin-header {
  background: white;
  padding: 1rem 2rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  flex-shrink: 0; /* Empêche le header de rétrécir */
  z-index: 10; /* Garde le header au-dessus du contenu */
}

.page-title {
  font-size: 1.5rem;
  font-weight: bold;
  color: #2c3e50;
  margin: 0;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #6c757d;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #E4DBC6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: #2c3e50;
}

.admin-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto; /* Scroll uniquement pour le contenu interne */
  background-color: #f8f9fa;
}
</style>