<template>
  <div class="accounts-view">
    <HeaderSpecified />

    <section class="hero-section">
      <div class="hero-text">
        <h1>Créez des comptes à vos employés</h1>
        <p>Gérez l’équipe facilement et attribuez-leur des accès sécurisés.</p>
        <button class="btn accent">Découvrir</button>
      </div>
    </section>

    <section class="accounts-section">
      <div class="search-bar">
        <input type="text" placeholder="Rechercher un employé..." v-model="search" />
      </div>

      <table class="styled-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Adresse mail</th>
            <th>Mot de passe prédéfini</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in filteredEmployees" :key="emp.id">
            <td>{{ emp.nom }}</td>
            <td>{{ emp.prenom }}</td>
            <td>{{ emp.email }}</td>
            <td>{{ emp.password }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import { ref, computed } from "vue";

const search = ref("");
const employees = ref([
  { id: 1, nom: "MUKOKA", prenom: "JUSTICE", email: "justice.musau@gmail.com", password: "Azerty@123" },
  { id: 2, nom: "MULUMBU", prenom: "ROSAINT", email: "rosaint@gmail.com", password: "Azerty@123" },
]);

const filteredEmployees = computed(() =>
  employees.value.filter((e) =>
    e.nom.toLowerCase().includes(search.value.toLowerCase()) ||
    e.prenom.toLowerCase().includes(search.value.toLowerCase())
  )
);
</script>

<style scoped>
.hero-section {
  background: url("@/assets/burger.jpg") center/cover no-repeat;
  color: black;
  padding: 80px 60px;
  border-radius: 0 0 40px 40px;
}

.accounts-section {
  background: #111;
  color: white;
  padding: 40px;
  border-radius: 16px;
  margin: 40px;
}

.search-bar {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.search-bar input {
  width: 50%;
  padding: 10px;
  border-radius: 8px;
  border: none;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
}

.styled-table th, .styled-table td {
  border-bottom: 1px solid #444;
  padding: 10px;
}
</style>
