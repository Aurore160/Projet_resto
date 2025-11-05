<template>
  <div class="orders-page">
    <HeaderSpecified />

    <main class="container order-content">
      <h2>Mes commandes </h2>

      <div v-if="orders.length === 0" class="empty">
        <p>Aucune commande enregistrée pour le moment.</p>
        <router-link to="/user" class="btn accent">Voir le menu</router-link>
      </div>

      <div v-else class="orders-grid">
        <article
          v-for="(order, index) in orders"
          :key="order.id"
          class="order-card"
        >
          <header class="order-header">
            <strong>Commande #{{ order.id }}</strong>
            <span class="order-date">{{ formatDate(order.date) }}</span>
          </header>

          <ul class="order-items">
            <li v-for="it in order.items" :key="it.id">
              <img :src="it.image" alt="item" />
              <div class="item-info">
                <p class="item-name">{{ it.name }}</p>
                <small>{{ it.qty }} × {{ it.price }}</small>
              </div>
              <span class="item-total">
                ${{ (parseFloat(it.price.replace("$",)) * it.qty).toFixed(2) }}
              </span>
            </li>
          </ul>

          <footer class="order-footer">
            <div>
              <strong>Total :</strong> ${{ order.total }}
            </div>
            <div class="status">
              <span :class="['badge', order.status.toLowerCase()]">
                {{ order.status }}
              </span>
            </div>
          </footer>

          <div class="order-actions">
            <button
              v-if="order.status === 'En préparation'"
              @click="updateStatus(index, 'En livraison')"
              class="btn next"
            > Marquer en livraison </button>
            <button
              v-if="order.status === 'En livraison'"
              @click="updateStatus(index, 'Livrée')"
              class="btn success"
            > Marquer livrée </button>
            <button
              v-if="order.status !== 'Livrée'"
              @click="updateStatus(index, 'Annulée')"
              class="btn remove"
            > Annuler </button>
          </div>
        </article>
      </div>
    </main>

    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore} from "@/stores/userStore.js";
import { computed } from "vue";

const useStore = useUserStore();


const orders = computed(() =>
  store.state.invoices.map((inv) => ({
    ...inv,
    status: inv.status || "En préparation",
  }))
);


function updateStatus(index, newStatus) {
  const inv = store.state.invoices[index];
  inv.status = newStatus;
  localStorage.setItem("invoices", JSON.stringify(store.state.invoices));

  const notifText = {
    "En préparation": "Votre commande a été enregistrée ",
    "En livraison": "Votre commande est en livraison ",
    "Livrée": "Votre commande a été livrée ",
    "Annulée": "Votre commande a été annulée ",
  }[newStatus];

  store.addNotification(notifText);
}


function formatDate(date) {
  return new Date(date).toLocaleString();
}
</script>

<style scoped>
@import "@/assets/site.css";

.order-content {
  padding: 24px 16px;
  max-width: 1000px;
  margin: auto;
}

.orders-grid {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.order-card {
  background: beige;
  border-radius: 12px;
  padding: 14px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.order-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.order-items {
  list-style: none;
  padding: 0;
  margin: 0;
  border-top: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
}

.order-items li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
}

.order-items img {
  width: 50px;
  height: 50px;
  border-radius: 6px;
  object-fit: cover;
  margin-right: 10px;
}

.item-info {
  flex: 1;
}

.item-name {
  margin: 0;
  font-weight: 600;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  align-items: center;
}

.status {
  text-align: right;
}

.badge {
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: white;
}

.badge.en {
  background: #d6c8b0;
}

.badge.en-préparation {
  background: #d6c8b0;
}

.badge.en-livraison {
  background: #d6c8b0;
}


.badge.livrée {
  background: #27ae60;
}

.badge.annulée {
  background: #e74c3c;
}


.order-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.btn {
  padding: 8px 10px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.next {
  background: #d6c8b0;
  color: white;
}

.success {
  background: #2ecc71;
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

@media (max-width: 700px) {
  .order-items li {
    flex-direction: column;
    align-items: flex-start;
  }
  .item-total {
    align-self: flex-end;
    font-weight: 600;
  }
}
</style>
