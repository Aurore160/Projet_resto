<template>
  <div class="home-manager">
    <HeaderSpecified />


    <section class="hero-section">
      <div class="hero-text">
        <h1>Bienvenue, <span>{{ manager.nom.toUpperCase() }}</span></h1>
        <p>Gérez votre restaurant, vos employés et vos ventes efficacement.</p>
        <button class="btn accent">Découvrir</button>
      </div>
    </section>


    <section class="categories">
      <div class="category" v-for="cat in categories" :key="cat.name">
        <h3>{{ cat.name }}</h3>
        <div class="menu-grid">
          <div class="menu-item" v-for="plat in cat.items" :key="plat.id">
            <img :src="plat.image" alt="" />
            <p class="plat-name">{{ plat.name }}</p>
            <p class="price">${{ plat.price }}</p>
          </div>
        </div>
      </div>
    </section>

  
    <section class="orders-section">
      <div class="section-header">
        <h2>Commandes</h2>
        <p>Suivez les commandes passées par vos clients et leur état.</p>
        <img src="@/assets/cheese.png" alt="image" />
      </div>

      <div class="filters">
        <input type="text" placeholder="Rechercher une commande..." v-model="search" />
        <select v-model="day">
          <option v-for="n in 31" :key="n" :value="n">{{ n }}</option>
        </select>
        <select v-model="month">
          <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
        </select>
        <select v-model="year">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

      <table class="styled-table">
        <thead>
          <tr>
            <th>Plats</th>
            <th>Prix</th>
            <th>Qte</th>
            <th>Auteur</th>
            <th>Statut</th>
            <th>Montant total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cmd in filteredOrders" :key="cmd.id">
            <td>{{ cmd.plat }}</td>
            <td>${{ cmd.price }}</td>
            <td>{{ cmd.qte }}</td>
            <td>{{ cmd.client }}</td>
            <td :class="cmd.status === 'Livré' ? 'done' : 'pending'">{{ cmd.status }}</td>
            <td>${{ cmd.total }}</td>
          </tr>
        </tbody>
      </table>
    </section>


    <section class="top-clients">
      <div class="section-header">
        <h2>Top 10 des meilleurs clients</h2>
        <p>Les plus fidèles et les plus actifs.</p>
        <img src="@/assets/burger.jpg" alt="burger" />
      </div>

      <table class="styled-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Points</th>
            <th>Montant total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in topClients" :key="client.nom">
            <td>{{ client.nom }}</td>
            <td>{{ client.points }}</td>
            <td>${{ client.total }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    
    <section class="promo-section">
      <img src="@/assets/left2.jpg" alt="promo" />
      <div class="promo-text">
        <h3>Événements et promotions</h3>
        <p>
          Organisez des offres exclusives et des réductions pour attirer plus de clients.
        </p>
      </div>
    </section>

    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import { ref, computed } from "vue";

const manager = ref({
  nom: "Justice Musau",
});

const categories = ref([
  {
    name: "Salades",
    items: [{ id: 1, name: "Salade César", image: "@/assets/salade.jpg", price: 10 }],
  },
  {
    name: "Desserts",
    items: [{ id: 2, name: "Brownie", image: "@/assets/desserts.jpg", price: 5 }],
  },
]);

const search = ref("");
const day = ref("");
const month = ref("");
const year = ref("");

const months = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];

const years = [2019, 2020, 2021, 2022, 2023, 2024, 2025];

const orders = ref([
  { id: 1, plat: "Burger", price: 20, qte: 2, client: "Jean Dupont", status: "En cours", total: 40 },
  { id: 2, plat: "Poulet", price: 15, qte: 1, client: "Sarah", status: "Livré", total: 15 },
]);

const filteredOrders = computed(() =>
  orders.value.filter((cmd) =>
    cmd.plat.toLowerCase().includes(search.value.toLowerCase())
  )
);

const topClients = ref([
  { nom: "JUSTICE MUSAU", points: 185, total: 467 },
  { nom: "KALALA LUCAS", points: 172, total: 421 },
]);
</script>

<style scoped>
.hero-section {
  background: url("@/assets/burger.jpg") center/cover no-repeat;
  color: black;
  padding: 80px 60px;
  border-radius: 0 0 40px 40px;
}

.hero-text h1 span {
  color: #222;
}

.categories {
  background: #d6c7a6;
  padding: 30px;
}

.orders-section, .top-clients {
  background: #111;
  color: white;
  padding: 40px;
  border-radius: 16px;
  margin: 40px 0;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.filters {
  display: flex;
  gap: 12px;
  margin: 20px 0;
}

.filters input, .filters select {
  padding: 8px;
  border-radius: 8px;
  border: none;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
}

.styled-table th, .styled-table td {
  border-bottom: 1px solid #444;
  padding: 8px;
  text-align: left;
}

.promo-section {
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: beige;
  padding: 40px;
  border-radius: 16px;
  color: #222;
}

.promo-section img {
  width: 40%;
  border-radius: 12px;
}

.promo-text {
  width: 45%;
}
</style>
