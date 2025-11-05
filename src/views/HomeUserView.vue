<template>
  <div class="home-wrap user-home">
    <HeaderSpecified />

    
    <section class="hero user-hero">
      <div class="hero-content">
        <h1>Bienvenue, {{ store.state.user?.firstName || store.state.user?.email }}</h1>
        <div class="parrainage"> 
          <h3> Votre code de parrainage</h3>
          <p> Partagez ce code avec vos amis pour gagner des points bonus ! </p>
          <div class="referral-code" @click="copyCode">
            {{ user.referralCode }}
          </div>
          <small> (Cliquez pour copier le code)</small>
        </div>
        
        <p>Heureux de vous compter parmi nous! Explorez, jouez, gagnez des points et savourez vos plats favoris grâce à Zeduc-space </p>
      </div>
    </section>

    
    <section class="tabs-section">
      <div class="tabs-row">
        <button v-for="c in categories" :key="c.key" :class="['tab-btn', { active: activeTab === c.key }]" @click="activeTab = c.key">
          <img :src="c.image" :alt="c.label" />
          <span>{{ c.label }}</span>
        </button>
      </div>

      <div class="menu-grid">
        <article v-for="item in filteredItems" :key="item.id" class="menu-card">
          <img class="menu-img" :src="item.image" :alt="item.name" />
          <div class="menu-info">
            <h3 class="menu-title">{{ item.name }}</h3>
            <p class="menu-desc">{{ item.description }}</p>

            <div class="controls">
              <div class="qty-controls">
                <button @click="decrement(item)">-</button>
                <span>{{ qtys[item.id] || 0 }}</span>
                <button @click="increment(item)">+</button>
              </div>

              <button class="add" @click="add(item)">Ajouter</button>

             
              <button class="fav" @click="toggleFav(item)">
                <i :class="store.isFavorite(item.id) ? 'fas fa-heart' : 'far fa-heart'"></i>
              </button>

              
              <button class="review-btn" @click="openReview(item)">
                Donner un avis
              </button>
            </div>
          </div>

          <div class="menu-price-left">{{ item.price }}</div>
        </article>
      </div>
    </section>

   
    <section class="alternating">
      <div class="alt-row">
        <div class="alt-text">
          <h3>Promotion & Événements</h3>
          <p>Découvrez les promos du moment sur vos plats préférés !</p>
          <router-link to="/promotion" class="btn transparent">Voir les promotions</router-link>
        </div>
        <img src="@/assets/left1.jpg" alt="Promo" />
      </div>

      <div class="alt-row reverse">
        <img src="@/assets/right1.jpg" alt="Quiz Time" />
        <div class="alt-text">
          <h3>Quiz Time</h3>
          <p>Participez à notre quiz culinaire et gagnez des points !</p>
          <router-link to="/quiz" class="btn transparent">Jouer</router-link>
        </div>
      </div>
    </section>

    
    <section class="top10">
      <div class="container">
        <h2>Top 10 des meilleurs clients</h2>
        <ul class="top-list">
          <li v-for="(u, idx) in top10" :key="idx">
            <span class="rank">{{ idx + 1 }}</span>
            <span class="name">{{ u.name }}</span>
            <span class="score">{{ u.score }}</span>
          </li>
        </ul>
      </div>
    </section>

   
    <div v-if="reviewOpen" class="review-overlay" @click.self="reviewOpen = false">
      <div class="review-card">
        <h3>Votre avis</h3>
        <label>Note (1 à 5)</label>
        <input type="number" min="1" max="5" v-model="currentReview.note" />
        <label>Commentaire</label>
        <textarea v-model="currentReview.message" placeholder="Votre avis sur le plat..."></textarea>
        <button @click="sendReview">Envoyer</button>
      </div>
    </div>

    <FooterSpecified />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from "vue";
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore} from "@/stores/userStore.js";

const useStore = useUserStore();


import drinkImg from "@/assets/drink.jpg";
import dessertImg from "@/assets/dessert.jpg";
import breakfastImg from "@/assets/breakfast.jpg";
import dinnerImg from "@/assets/dinner.jpg";
import saladeImg from "@/assets/salade.jpg";

const categories = [
  { key: "drink", label: "Drink", image: drinkImg },
  { key: "desserts", label: "Desserts", image: dessertImg },
  { key: "breakfast", label: "Breakfast", image: breakfastImg },
  { key: "dinner", label: "Dinner", image: dinnerImg },
  { key: "salade", label: "Salade", image: saladeImg },
];


import smoothieImg from "@/assets/drink.jpg";
import burgerImg from "@/assets/dinner.jpg";
import pancakesImg from "@/assets/breakfast.jpg";
import saladImg from "@/assets/salade.jpg";
import brownieImg from "@/assets/dessert.jpg";

const menuItems = [
  {
    id: 1,
    category: "drink",
    name: "Smoothie Tropical",
    description: "Ananas, mangue, banane et lait de coco.",
    price: "$4.50",
    image: smoothieImg,
  },
  {
    id: 2,
    category: "dinner",
    name: "Burger Classique",
    description: "Boeuf grillé, cheddar, tomate et laitue.",
    price: "$8.50",
    image: burgerImg,
  },
  {
    id: 3,
    category: "breakfast",
    name: "Pancakes Banane",
    description: "Servis avec miel et fruits frais.",
    price: "$5.00",
    image: pancakesImg,
  },
  {
    id: 4,
    category: "salade",
    name: "Salade César",
    description: "Poulet grillé, parmesan et croûtons.",
    price: "$6.00",
    image: saladImg,
  },
  {
    id: 5,
    category: "desserts",
    name: "Brownie Maison",
    description: "Chocolat noir et noix de pécan.",
    price: "$3.50",
    image: brownieImg,
  },
];
// Pour le chargement des produits et promos mis à jour 
store.loadProductsFromLocal()
store.loadPromotionsFromLocal()

// on veut utiliser ces listes pour le rendu 

const products = computed(() => store.products)
const promotions = computed(() => store.promotions)


const activeTab = ref("drink");
const qtys = reactive({});


function increment(item) {
  qtys[item.id] = (qtys[item.id] || 0) + 1;
}
function decrement(item) {
  qtys[item.id] = Math.max(0, (qtys[item.id] || 0) - 1);
}


function add(item) {
  const q = qtys[item.id] || 1;
  store.addToCart(item, q);
  store.addNotification(`${q} x ${item.name} ajouté(s) au panier.`);
}
//  Pour copier le code de parrainage dans le presse-papier 

function copyCode() {
  navigator.clipboard.writeText(user.value.refferalCode);
  store.addNotification("Code copié dans le presse-papiers !")
}


function toggleFav(item) {
  store.toggleFavorite(item);
  const msg = store.isFavorite(item.id)
    ? `${item.name} ajouté aux favoris.`
    : `${item.name} retiré des favoris.`;
  store.addNotification(msg);
}


const reviewOpen = ref(false);
const currentReview = reactive({ id: null, note: 0, message: "" });

function openReview(item) {
  currentReview.id = item.id;
  reviewOpen.value = true;
}

function sendReview() {
  if (!currentReview.note || !currentReview.message) {
    alert("Veuillez compléter la note et le message.");
    return;
  }
  store.addReview({
    idPlat: currentReview.id,
    note: currentReview.note,
    message: currentReview.message,
  });
  store.addNotification("Merci pour votre avis !");
  reviewOpen.value = false;
  currentReview.note = 0;
  currentReview.message = "";
}


const filteredItems = computed(() =>
  menuItems.filter((m) => m.category === activeTab.value)
);


const top10 = [
  { name: "Justice M.", score: 5487 },
  { name: "Marie P.", score: 4260 },
  { name: "Alex B.", score: 3898 },
  { name: "Lina S.", score: 3420 },
  { name: "Tom R.", score: 3200 },
  { name: "Ana K.", score: 3100 },
  { name: "Noah D.", score: 2980 },
  { name: "Sara L.", score: 2800 },
  { name: "Paul G.", score: 2700 },
  { name: "Nora M.", score: 2600 },
];
</script>

<style scoped>
@import "@/assets/site.css";

.user-hero {
  height: 300px;
  background: url("@/assets/hero.jpg") center/cover no-repeat;
  color: var(--text-light);
  display: flex;
  align-items: center;
}


.menu-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  padding: 16px;
}
.menu-card {
  position: relative;
  background: beige;
  border-radius: 12px;
  overflow: hidden;
  padding: 10px;
}
.menu-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 8px;
}
.menu-info {
  margin-top: 8px;
}
.menu-price-left {
  position: absolute;
  left: 12px;
  bottom: 12px;
  font-weight: 700;
  color: var(--accent);
}


.controls {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
}
.qty-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}
.qty-controls button {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid #ddd;
  background: #f6f6f6;
  cursor: pointer;
}
.add {
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
}
.fav {
  background: none;
  border: none;
  color: crimson;
  font-size: 20px;
}
.review-btn {
  border: 1px solid #ccc;
  background: white;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
}


.review-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 90;
}
.review-card {
  background: #fff;
  width: 360px;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}
.review-card textarea {
  width: 100%;
  height: 80px;
  resize: none;
  border-radius: 6px;
  border: 1px solid #ccc;
  margin-top: 4px;
}
.review-card input {
  width: 100%;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
</style>