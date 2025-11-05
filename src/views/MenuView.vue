<template>
  <div class="menu-page">
    <Header />

    <section class="menu-hero">
      <h1>Notre Menu</h1>
      <p>Découvrez nos plats par catégorie : des boissons fraîches, des petits-déjeuners savoureux, des diners copieux et des desserts faits maison.</p>
    </section>

    <section class="menu-tabs">
      <div class="tabs-row">
        <button
          v-for="t in categories"
          :key="t.key"
          :class="['tab-btn', { active: activeTab === t.key }]"
          @click="activeTab = t.key"
        >
          <img :src="t.image" :alt="t.label" />
          <span>{{ t.label }}</span>
        </button>
      </div>

     
      <div class="tab-content">
        <div v-if="filteredItems.length === 0" class="empty">Aucun plat trouvé pour cette catégorie.</div>

        <div class="menu-grid">
          <article v-for="item in filteredItems" :key="item.id" class="menu-card">
            <img class="menu-img" :src="item.image" :alt="item.name" />
            <div class="menu-info">
              <h3 class="menu-title">{{ item.name }}</h3>
              <p class="menu-desc">{{ item.description }}</p>
            </div>
            <div class="menu-price">{{ item.price }}</div>
          </article>
        </div>
      </div>
    </section>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import Header from "@/component/Header.vue";
import Footer from "@/component/Footer.vue";

import margaritaImage from "@/assets/margarita.jpg";
import jusImage from "@/assets/jus.jpg";
import glaceImage from "@/assets/glace.jpg";
import brownieImage from "@/assets/brownie.jpg";
import gaufreImage from "@/assets/gaufre.jpg";
import crepeImage from "@/assets/crepe.jpg";
import pouletImage from "@/assets/poulet.jpg";
import viandeImage from "@/assets/viande.jpg";
import brocoliImage from "@/assets/brocoli.jpg";
import poireImage from "@/assets/poire.jpg";



const categories = [
    { key: "drink", label: "Drink", img: "@/assets/drink1.jpg" },
    { key: "salade", label: "Salade", img:"@/assets/salade1.jpg" },
    { key: "dinner", label: "Dinner", img: "@/assets/dinner1.jpg" },
    { key: "breakfast", label: "Breakfast", img: "@/assets/breakfast1.jpg" },
    { key: "desserts", label: "Desserts", img: "@/assets/dessert1.jpg" }
];


const menuItems = [
  // Drinks
  { id: 1, category: "drink", name: "Margarita Menthe", description: "Midori-liqueur, Menthe, Citron", price: "$4.60", image: margaritaImage },
  { id: 2, category: "drink", name: "Jus d'orange", description: "Orange", price: "$4.00", image: jusImage },

  // Desserts
  { id: 3, category: "desserts", name: "Glace à la fraise et au cacao", description: "Glace, Cacao, Fraise", price: "$1.60", image: glaceImage },
  { id: 4, category: "desserts", name: "Brownie chocolat", description: "Chocolat, Oeufs, Bananes, Sucre, Farine", price: "$3.70", image: brownieImage },

  // Breakfast
  { id: 5, category: "breakfast", name: "Gaufre à la fraise", description: "Gaufre, Chocolat, Fraise", price: "$2.60", image: gaufreImage  },
  { id: 6, category: "breakfast", name: "Crêpes aux chocolats", description: "Crêpes, Chocolat, fraise", price: "$1.90", image: crepeImage },

  // Dinner
  { id: 7, category: "dinner", name: "Poulet frit avec pommes de terre", description: "Poulet, Pomme de terre", price:"$10.60", image: pouletImage },
  { id: 8, category: "dinner", name: "Viande rotie", description: "Viande rotie, legumes", price: "$16.77", image: viandeImage },

  // Salade
  { id: 9, category: "salade", name: "Brocoli aux tomates fraîches", description: "brocoli, tomate, poivre jaune", price: "$6.80", image: brocoliImage },
  { id: 10, category: "salade", name: "Poire mijoté dans des legumes fraîches", description: "Tomates, Poire, olive, concombre.", price: "$4.5", image: poireImage },
]


const activeTab = ref('drink')


const filteredItems = computed(() => menuItems.filter(item => item.category === activeTab.value))
</script>

<style scoped>


/* Style spécifique à la page Menu */
.menu-page {
  background: #cfbd97;
}

/* Titre principal */
.menu-hero {
  text-align: center;
  background: url('@/assets/menu.jpg') center/cover no-repeat;
  color: #fff;
  padding: 80px 20px;
}

.menu-hero h1 {
  font-size: 48px;
  margin-bottom: 8px;
}

.menu-hero p {
  font-size: 16px;
  max-width: 700px;
  margin: 0 auto;
  line-height: 1.6;
  color: var(--text-light);
}

/* Onglets catégorie */
.menu-tabs {
  max-width: 1200px;
  margin: 24px auto;
  background: #fff;
  border-radius: 8px;
  padding: 20px 16px;
}

.tabs-row {
  display: flex;
  gap: 14px;
  margin-bottom: 18px;
  flex-wrap: wrap;
  justify-content: center;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #cfbd97;
  border-radius: 40px;
  padding: 8px 12px;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.tab-btn img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
}

.tab-btn.active {
  outline: 3px solid var(--accent);
}

/* Grille de plats */
.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
  background: #fff;
  border-radius: 8px;
  padding: 12px;
}

.menu-card {
  background: #cfbd97;
  border-radius: 12px;
  padding: 12px;
  display: grid;
  grid-template-columns: 96px 1fr;
  grid-template-rows: auto 1fr;
  gap: 8px;
  align-items: start;
  position: relative;
}

.menu-img {
  width: 96px;
  height: 96px;
  border-radius: 10px;
  object-fit: cover;
  grid-row: 1 / span 2;
}

.menu-title {
  margin: 0;
  color: var(--text-dark);
}

.menu-desc {
  color: #444;
  font-size: 13px;
}

.menu-price {
  position: absolute;
  bottom: 8px;
  right: 12px;
  color: var(--accent);
  font-weight: 600;
}


@media (max-width: 768px) {
  .menu-hero h1 {
    font-size: 32px;
  }
  .menu-hero p {
    font-size: 14px;
  }
  .menu-card {
    grid-template-columns: 80px 1fr;
  }
}
</style>