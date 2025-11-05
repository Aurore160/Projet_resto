<template>
  <div class="home-employee">
    <HeaderSpecified />

    <section class="hero-section">
      <div class="hero-text">
        <h1>Bienvenue {{ employe.nom }}</h1>
        <p>Suivez vos commandes, signalez des ruptures de stock et échangez avec votre manager.</p>
      </div>
    </section>

    <section class="orders-section">
      <h2>Commandes en attente</h2>
      <table class="styled-table">
        <thead>
          <tr><th>Plat</th><th>Quantité</th><th>Client</th><th>Statut</th><th>Action</th></tr>
        </thead>
        <tbody>
          <tr v-for="cmd in commandes" :key="cmd.id">
            <td>{{ cmd.plat }}</td>
            <td>{{ cmd.qte }}</td>
            <td>{{ cmd.client }}</td>
            <td>{{ cmd.status }}</td>
            <td>
              <button @click="changerStatut(cmd)">Changer statut</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="communication">
      <h2>Communication</h2>
      <textarea placeholder="Message au manager..." v-model="message"></textarea>
      <button @click="envoyerMessage">Envoyer</button>
    </section>

    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import { ref } from "vue";

const employe = ref({ nom: "Jean Dupont" });
const commandes = ref([
  { id: 1, plat: "Burger", qte: 2, client: "Sarah", status: "En attente" },
  { id: 2, plat: "Pizza", qte: 1, client: "Alex", status: "En cours" },
]);

function changerStatut(cmd) {
  cmd.status = cmd.status === "En attente" ? "Livré" : "En attente";
}

const message = ref("");
function envoyerMessage() {
  alert("Message envoyé au manager !");
  message.value = "";
}
</script>

<style scoped>
.hero-section {
  background: url("@/assets/burger.jpg") center/cover no-repeat;
  color: black;
  padding: 80px;
}

.orders-section, .communication {
  background: #d6c7a6;
  padding: 40px;
  border-radius: 16px;
  margin: 30px;
  color: #222;
}

textarea {
  width: 100%;
  min-height: 100px;
  border-radius: 8px;
  border: 1px solid #ccc;
  margin-bottom: 10px;
}
button {
  background: #cfbd97;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
}
</style>
