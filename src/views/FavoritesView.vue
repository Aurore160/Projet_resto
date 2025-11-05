<template>
  <div class="favorites-page">
    <HeaderSpecified />

    <main class="container fav-content">
      <h2>Mes plats favoris </h2>

      <div v-if="store.state.favorites.length === 0" class="empty">
        <p>Vous n’avez encore ajouté aucun plat en favori.</p>
        <router-link to="/user" class="btn accent">Voir le menu</router-link>
      </div>

      <div v-else class="fav-grid">
        <article
          v-for="item in store.state.favorites"
          :key="item.id"
          class="fav-card"
        >
          <img :src="item.image" :alt="item.name" />
          <div class="fav-info">
            <h3>{{ item.name }}</h3>
            <p>{{ item.description }}</p>
            <div class="fav-actions">
              <button class="btn add" @click="addToCart(item)">
                Ajouter au panier
              </button>
              <button class="btn remove" @click="removeFav(item)">
                Retirer des favoris
              </button>
            </div>
          </div>
        </article>
      </div>
    </main>

    <FooterSpecified />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore} from "@/stores/userStore.js";
const useStore = useUserStore();


function addToCart(item) {
  store.addToCart(item, 1);
  store.addNotification(`${item.name} ajouté au panier depuis les favoris.`);
}


function removeFav(item) {
  store.toggleFavorite(item);
  store.addNotification(`${item.name} retiré de vos favoris.`);
}
</script>

<style scoped>


.fav-content {
  padding: 24px 16px;
  max-width: 1200px;
  margin: auto;
}

.fav-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.fav-card {
  background: beige;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.fav-card img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

.fav-info {
  padding: 12px;
}

.fav-info h3 {
  margin: 0 0 4px;
}

.fav-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.btn {
  padding: 8px 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.add {
  background: var(--accent);
  color: white;
}

.remove {
  background: #e74c3c;
  color: white;
}

.empty {
  text-align: center;
  padding: 60px 20px;
}

.empty p {
  font-size: 1.1rem;
  margin-bottom: 12px;
}

@media (max-width: 600px) {
  .fav-card img {
    height: 160px;
  }
}
</style>