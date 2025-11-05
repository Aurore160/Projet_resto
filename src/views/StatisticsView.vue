<template>
  <div class="statistics-wrap">

    <HeaderSpecial />

   
    <section class="intro">
      <h1>Statistiques et gestion de la fidélité</h1>
      <p>Analysez les performances, les points de fidélité et le parrainage.</p>
    </section>

    <section class="fidelite-section">
      <h2>Fidélité & Parrainage</h2>
      <p>Suivi des utilisateurs parrainés et des points de fidélité gagnés.</p>

      <div class="filters">
        <input
          type="text"
          v-model="searchReferral"
          placeholder="Rechercher un utilisateur..."
        />
        <div class="filters-inline">
          <div class="filter-box">Tout : {{ referrals.length }}</div>
          <div class="filter-box">
            Parrainage : {{ referrals.filter(r => r.parrainId).length }}
          </div>
          <div class="filter-box">
            Non-parrainé :
            {{ Math.max(0, totalUsers - referrals.filter(r => r.parrainId).length) }}
          </div>
        </div>
      </div>

      <table class="referral-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Points</th>
            <th>Code unique</th>
            <th>Parrain</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in filteredReferrals" :key="r.date">
            <td>{{ r.parraineNom }}</td>
            <td>{{ userPoints[r.parrainId] || 0 }}</td>
            <td>{{ r.parrainId }}</td>
            <td>{{ r.parrainNom }}</td>
          </tr>
          <tr v-if="filteredReferrals.length === 0">
            <td colspan="4" class="empty">Aucun enregistrement trouvé.</td>
          </tr>
        </tbody>
      </table>
    </section>

   
    <section class="sales-stats-section">
      <h2>Statistiques de ventes</h2>
      <p>Suivez vos ventes quotidiennes et les montants totaux générés.</p>

      <div class="filters-sales">
        <select>
          <option>Jour</option>
          <option v-for="j in 31" :key="j">{{ j }}</option>
        </select>

        <select>
          <option>Mois</option>
          <option v-for="m in mois" :key="m">{{ m }}</option>
        </select>

        <select>
          <option>Année</option>
          <option v-for="a in annees" :key="a">{{ a }}</option>
        </select>

        <input type="text" placeholder="Rechercher un plat..." />
      </div>

      <div class="totaux-ventes">
        <p><strong>Sub total :</strong> {{ subtotal.toFixed(2) }} {{ store.currencySymbol }}</p>
        <p><strong>Tax :</strong> {{ tax.toFixed(2) }} {{ store.currencySymbol }}</p>
        <p><strong>Total :</strong> {{ total.toFixed(2) }} {{ store.currencySymbol }}</p>
      </div>

      <table class="sales-table">
        <thead>
          <tr>
            <th>Plat</th>
            <th>Prix</th>
            <th>Qté</th>
            <th>Montant total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sales" :key="s.name">
            <td>{{ s.name }}</td>
            <td>{{ s.price }} {{ store.currencySymbol }}</td>
            <td>{{ s.qty }}</td>
            <td>{{ s.total.toFixed(2) }} {{ store.currencySymbol }}</td>
          </tr>
          <tr v-if="sales.length === 0">
            <td colspan="4" class="empty">Aucune vente enregistrée.</td>
          </tr>
        </tbody>
      </table>
    </section>
    <FooterSpecified />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useUserStore } from "@/stores/userStore"
import HeaderSpecial from "@/component/HeaderSpecial.vue"
import FooterSpecified from "@/component/FooterSpecified.vue"

const store = useUserStore()


store.loadReferralData()
store.loadProductsFromLocal()
store.loadPromotionsFromLocal()

const referrals = computed(() => store.referrals || [])
const userPoints = computed(() => store.userPoints || {})
const searchReferral = ref("")
const totalUsers = computed(() => store.users ? store.users.length : 0)

const filteredReferrals = computed(() => {
  if (!searchReferral.value) return referrals.value
  return referrals.value.filter(r =>
    r.parraineNom?.toLowerCase().includes(searchReferral.value.toLowerCase()) ||
    r.parrainNom?.toLowerCase().includes(searchReferral.value.toLowerCase())
  )
})


const mois = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
]
const annees = [2020, 2021, 2022, 2023, 2024, 2025]

const sales = computed(() => {
  const products = store.products || []
  return products.map(p => {
    const qty = Math.floor(Math.random() * 10) + 1
    return {
      name: p.name,
      price: p.price,
      qty,
      total: p.price * qty
    }
  })
})

const subtotal = computed(() => sales.value.reduce((sum, s) => sum + s.total, 0))
const tax = computed(() => subtotal.value * 0.1)
const total = computed(() => subtotal.value + tax.value)

onMounted(() => {
  window.addEventListener("storage", () => {
    store.loadReferralData()
  })
})
</script>

<style scoped>
.statistics-wrap {
  background: #d6c7a6;
  color: #000;
  min-height: 100vh;
}
.site-header {
  background: #cfbd97;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
}
.nav-links {
  display: flex;
  gap: 15px;
}
.nav-links a {
  color: #000;
  font-weight: 500;
}
.intro {
  padding: 20px 40px;
}
h1 {
  color: #000;
}
.fidelite-section,
.sales-stats-section {
  background: #111;
  color: #fff;
  padding: 20px;
  margin: 25px 40px;
  border-radius: 10px;
}
.filters {
  margin-bottom: 15px;
}
.filters input {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: none;
  margin-bottom: 10px;
}
.filters-inline {
  display: flex;
  gap: 10px;
}
.filter-box {
  background: #cfbd97;
  color: #000;
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: bold;
}
.referral-table, .sales-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
.referral-table th, .sales-table th,
.referral-table td, .sales-table td {
  border-bottom: 1px solid rgba(255,255,255,0.2);
  padding: 10px;
  text-align: left;
}
.referral-table th, .sales-table th {
  color: #cfbd97;
  font-weight: 600;
}
.totaux-ventes {
  margin-bottom: 15px;
  color: #cfbd97;
}
.site-footer {
  background: #cfbd97;
  text-align: center;
  padding: 15px;
  margin-top: 40px;
}
</style>
