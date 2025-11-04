<template>
  <div class="profile-zone">
    <button class="profile-btn" @click="toggleProfileMenu">
      <div class="profile-avatar">
        <img src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\jj.png" alt="Profil utilisateur" class="avatar-img">
        <div class="avatar-status"></div>
      </div>
      <div class="profile-info">
        <span class="profile-name">John Doe</span>
        <!-- <span class="profile-role">Client</span> -->
      </div>
      <div class="profile-arrow">
        <i class="fas fa-chevron-down"></i>
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const isProfileMenuOpen = ref(false)

const toggleProfileMenu = () => {
  isProfileMenuOpen.value = !isProfileMenuOpen.value
  // Le menu profil sera implémenté plus tard
  console.log('Menu profil ouvert:', isProfileMenuOpen.value)
}
</script>

<style scoped>
.profile-zone {
  display: flex;
  align-items: center;
}

.profile-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  background: none;
  border: none;
  padding: 8px 16px;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

.profile-avatar {
  position: relative;
  width: 40px;
  height: 40px;
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.avatar-status {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 10px;
  height: 10px;
  background: #4ade80;
  border: 2px solid white;
  border-radius: 50%;
}

.profile-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.profile-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: white;
  line-height: 1;
}

.profile-role {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1;
}

.profile-arrow {
  color: rgba(255, 255, 255, 0.7);
  transition: transform 0.3s ease;
  font-size: 0.8rem;
}

.profile-btn:hover .profile-arrow {
  color: white;
}

/* Animation pour la flèche quand le menu est ouvert */
.profile-zone.menu-open .profile-arrow {
  transform: rotate(180deg);
}

/* Version compacte pour les écrans mobiles */
@media (max-width: 768px) {
  .profile-btn {
    padding: 6px 12px;
    gap: 8px;
  }
  
  .profile-avatar {
    width: 32px;
    height: 32px;
  }
  
  .profile-info {
    display: none; /* Cache les infos sur mobile pour gagner de l'espace */
  }
  
  .profile-name,
  .profile-role {
    font-size: 0.8rem;
  }
}

/* Version très compacte pour les très petits écrans */
@media (max-width: 480px) {
  .profile-btn {
    padding: 4px 8px;
  }
  
  .profile-avatar {
    width: 28px;
    height: 28px;
  }
}
</style>