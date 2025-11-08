<template>
  <div class="sidebar">
    <!-- En-tête de la sidebar avec photo -->
    <div class="sidebar-header">
      <router-link to="/gerant" class="profile-link">
        <div class="profile-photo-container">
          <img 
            src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\jj.png" 
            alt="Photo de profil" 
            class="profile-photo"
          />
        </div>
        <div class="user-info">
          <span class="user-name">John Doe</span>
          <span class="user-role">Gérant</span>
        </div>
      </router-link>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <router-link 
        v-for="item in menuItems" 
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: $route.path === item.to }"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        <span class="nav-text">{{ item.text }}</span>
      </router-link>
    </nav>

    <!-- Déconnexion -->
    <div class="sidebar-footer">
      <button class="logout-btn">
        <span class="nav-icon">🚪</span>
        <span class="nav-text">Déconnexion</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const menuItems = ref([
  { to: '/gerant/commandes', icon: '📦', text: 'Commandes' },
  { to: '/gerant/comptes', icon: '👥', text: 'Comptes' },
  { to: '/gerant/reclamations', icon: '📝', text: 'Réclamations' },
  { to: '/gerant/statistiques', icon: '📊', text: 'Statistiques' },
  { to: '/gerant/profil', icon: '👤', text: 'Profil' }
])
</script>

<style scoped>
.sidebar {
  width: 280px;
  height: 100vh;
  background: var(--primary-color);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
  z-index: 1000;
}

.sidebar-header {
  padding: 2rem 1.5rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.profile-link {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  transition: all 0.3s ease;
  padding: 8px;
  border-radius: 12px;
}

.profile-link:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
}

.profile-photo-container {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid var(--accent-color);
  flex-shrink: 0;
}

.profile-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  color: white;
  font-weight: 600;
  font-size: 1rem;
}

.user-role {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.8rem;
}

.sidebar-nav {
  flex: 1;
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.5rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border-left-color: var(--accent-color);
  transform: translateX(5px);
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-left-color: var(--accent-color);
}

.nav-icon {
  font-size: 1.2rem;
  width: 24px;
  text-align: center;
}

.nav-text {
  font-weight: 500;
  font-size: 0.95rem;
}

.sidebar-footer {
  padding: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem;
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  transform: translateX(5px);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 70px;
  }
  
  .user-info,
  .nav-text {
    display: none;
  }
  
  .sidebar-header {
    padding: 1.5rem 0.5rem;
    text-align: center;
  }
  
  .profile-link {
    justify-content: center;
  }
  
  .nav-item {
    justify-content: center;
    padding: 1rem 0.5rem;
  }
  
  .sidebar-footer {
    padding: 1rem 0.5rem;
  }
  
  .logout-btn {
    justify-content: center;
    padding: 1rem 0.5rem;
  }
}
</style>