<template>
 <div class="statistiques-view">   
  <div class="statistiques-container">
    <div class="statistiques-header">
      <h1 class="header-title">Statistiques</h1>
      <div class="date-filter">
        <select v-model="selectedPeriod" @change="loadStatistics" class="filter-select">
          <option value="today">Aujourd'hui</option>
          <option value="week">Cette semaine</option>
          <option value="month" selected>Ce mois</option>
          <option value="year">Cette année</option>
        </select>
      </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="stats-cards">
      <div class="stat-card revenue" @mouseenter="animateCard('revenue')" @mouseleave="resetCard('revenue')">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <h3>Chiffre d'affaires</h3>
          <p class="stat-number">{{ formatCurrency(stats.revenue.total) }}</p>
          <p class="stat-change" :class="getChangeClass(stats.revenue.change)">
            {{ stats.revenue.change > 0 ? '+' : '' }}{{ stats.revenue.change }}% vs période précédente
          </p>
        </div>
        <div class="hover-effect"></div>
      </div>

      <div class="stat-card orders" @mouseenter="animateCard('orders')" @mouseleave="resetCard('orders')">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <h3>Commandes</h3>
          <p class="stat-number">{{ stats.orders.total }}</p>
          <p class="stat-change" :class="getChangeClass(stats.orders.change)">
            {{ stats.orders.change > 0 ? '+' : '' }}{{ stats.orders.change }}%
          </p>
        </div>
        <div class="hover-effect"></div>
      </div>

      <div class="stat-card customers" @mouseenter="animateCard('customers')" @mouseleave="resetCard('customers')">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
          <h3>Nouveaux clients</h3>
          <p class="stat-number">{{ stats.customers.new }}</p>
          <p class="stat-subtext">Total: {{ stats.customers.total }}</p>
        </div>
        <div class="hover-effect"></div>
      </div>

      <div class="stat-card loyalty" @mouseenter="animateCard('loyalty')" @mouseleave="resetCard('loyalty')">
        <div class="stat-icon">🎯</div>
        <div class="stat-content">
          <h3>Fidélité</h3>
          <p class="stat-number">{{ stats.loyalty.activeUsers }}</p>
          <p class="stat-subtext">Points distribués: {{ stats.loyalty.pointsDistributed }}</p>
        </div>
        <div class="hover-effect"></div>
      </div>
    </div>

    <!-- Graphiques et sections détaillées -->
    <div class="charts-grid">
      <!-- Graphique des ventes -->
      <div class="chart-card" @mouseenter="animateChart(0)" @mouseleave="resetChart(0)">
        <h3>Évolution du chiffre d'affaires</h3>
        <div class="chart-container">
          <canvas ref="revenueChart"></canvas>
        </div>
        <div class="chart-hover-info">
          <p>Cliquez pour voir les détails</p>
        </div>
      </div>

      <!-- Graphique des commandes -->
      <div class="chart-card" @mouseenter="animateChart(1)" @mouseleave="resetChart(1)">
        <h3>Commandes par statut</h3>
        <div class="chart-container">
          <canvas ref="ordersChart"></canvas>
        </div>
        <div class="chart-hover-info">
          <p>Cliquez pour voir les détails</p>
        </div>
      </div>

      <!-- Statistiques de fidélité -->
      <div class="chart-card" @mouseenter="animateChart(2)" @mouseleave="resetChart(2)">
        <h3>Programme de fidélité</h3>
        <div class="loyalty-stats">
          <div class="loyalty-item" v-for="(item, index) in loyaltyItems" :key="index"
               @mouseenter="animateListItem(index, 'loyalty')" 
               @mouseleave="resetListItem(index, 'loyalty')">
            <span class="label">{{ item.label }}</span>
            <span class="value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <!-- Programme de parrainage -->
      <div class="chart-card" @mouseenter="animateChart(3)" @mouseleave="resetChart(3)">
        <h3>Programme de parrainage</h3>
        <div class="referral-stats">
          <div class="referral-item" v-for="(item, index) in referralItems" :key="index"
               @mouseenter="animateListItem(index, 'referral')" 
               @mouseleave="resetListItem(index, 'referral')">
            <span class="label">{{ item.label }}</span>
            <span class="value">{{ item.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tableau des produits populaires -->
    <div class="table-section" @mouseenter="animateTable" @mouseleave="resetTable">
      <h3>Produits les plus populaires</h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Produit</th>
              <th>Quantité vendue</th>
              <th>Revenu</th>
              <th>Tendance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(product, index) in stats.topProducts" :key="product.id"
                @mouseenter="animateTableRow(index)" 
                @mouseleave="resetTableRow(index)"
                :class="['table-row', { 'row-hover': hoveredRow === index }]">
              <td>
                <span class="product-name">{{ product.name }}</span>
              </td>
              <td>
                <span class="quantity-badge">{{ product.quantity }}</span>
              </td>
              <td>{{ formatCurrency(product.revenue) }}</td>
              <td>
                <span class="trend" :class="product.trend">
                  {{ product.trend === 'up' ? '📈' : product.trend === 'down' ? '📉' : '➡️' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
 </div> 
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import Chart from 'chart.js/auto'

// Données réactives
const selectedPeriod = ref('month')
const revenueChart = ref(null)
const ordersChart = ref(null)
const hoveredRow = ref(null)
const animatedCards = ref({
  revenue: false,
  orders: false,
  customers: false,
  loyalty: false
})
const animatedCharts = ref([false, false, false, false])
const animatedListItems = ref({
  loyalty: {},
  referral: {}
})

let revenueChartInstance = null
let ordersChartInstance = null

// Données mockées des statistiques
const stats = ref({
  revenue: {
    total: 12542.50,
    change: 12.5
  },
  orders: {
    total: 342,
    change: 8.2
  },
  customers: {
    new: 45,
    total: 1247
  },
  loyalty: {
    activeUsers: 892,
    pointsDistributed: 12560,
    pointsUsed: 8450,
    rewardsClaimed: 234,
    usageRate: 67.3
  },
  referral: {
    monthlyReferrals: 28,
    totalReferrals: 456,
    rewardsDistributed: 156,
    conversionRate: 34.2
  },
  topProducts: [
    { id: 1, name: "Pizza Margherita", quantity: 145, revenue: 2175.00, trend: "up" },
    { id: 2, name: "Pasta Carbonara", quantity: 98, revenue: 1568.00, trend: "up" },
    { id: 3, name: "Salade César", quantity: 76, revenue: 912.00, trend: "stable" },
    { id: 4, name: "Tiramisu", quantity: 67, revenue: 469.00, trend: "down" },
    { id: 5, name: "Lasagne", quantity: 54, revenue: 864.00, trend: "up" }
  ]
})

// Computed properties pour les listes
const loyaltyItems = computed(() => [
  { label: 'Membres actifs', value: stats.value.loyalty.activeUsers },
  { label: 'Points utilisés', value: stats.value.loyalty.pointsUsed },
  { label: 'Récompenses réclamées', value: stats.value.loyalty.rewardsClaimed },
  { label: 'Taux d\'utilisation', value: stats.value.loyalty.usageRate + '%' }
])

const referralItems = computed(() => [
  { label: 'Parrainages ce mois', value: stats.value.referral.monthlyReferrals },
  { label: 'Total parrainages', value: stats.value.referral.totalReferrals },
  { label: 'Récompenses distribuées', value: stats.value.referral.rewardsDistributed },
  { label: 'Taux de conversion', value: stats.value.referral.conversionRate + '%' }
])

// Méthodes d'animation
const animateCard = (cardType) => {
  animatedCards.value[cardType] = true
}

const resetCard = (cardType) => {
  animatedCards.value[cardType] = false
}

const animateChart = (index) => {
  animatedCharts.value[index] = true
}

const resetChart = (index) => {
  animatedCharts.value[index] = false
}

const animateListItem = (index, type) => {
  animatedListItems.value[type][index] = true
}

const resetListItem = (index, type) => {
  animatedListItems.value[type][index] = false
}

const animateTable = () => {
  // Animation globale du tableau
}

const resetTable = () => {
  hoveredRow.value = null
}

const animateTableRow = (index) => {
  hoveredRow.value = index
}

const resetTableRow = (index) => {
  if (hoveredRow.value === index) {
    hoveredRow.value = null
  }
}

// Méthodes existantes
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const getChangeClass = (change) => {
  return change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral'
}

const loadStatistics = async () => {
  console.log('Chargement des stats pour:', selectedPeriod.value)
}

const initCharts = () => {
  const revenueData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
    datasets: [{
      label: 'Chiffre d\'affaires',
      data: [8500, 9200, 10200, 11500, 12500, 13400],
      borderColor: '#4CAF50',
      backgroundColor: 'rgba(76, 175, 80, 0.1)',
      tension: 0.4,
      fill: true
    }]
  }

  const ordersData = {
    labels: ['En attente', 'Confirmées', 'En préparation', 'Livraison', 'Terminées'],
    datasets: [{
      data: [12, 45, 23, 18, 242],
      backgroundColor: [
        '#FF6384',
        '#36A2EB',
        '#FFCE56',
        '#4BC0C0',
        '#9966FF'
      ]
    }]
  }

  if (revenueChartInstance) revenueChartInstance.destroy()
  if (ordersChartInstance) ordersChartInstance.destroy()

  if (revenueChart.value) {
    revenueChartInstance = new Chart(revenueChart.value, {
      type: 'line',
      data: revenueData,
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        },
        animations: {
          tension: {
            duration: 1000,
            easing: 'linear'
          }
        }
      }
    })
  }

  if (ordersChart.value) {
    ordersChartInstance = new Chart(ordersChart.value, {
      type: 'doughnut',
      data: ordersData,
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        animations: {
          animateRotate: true,
          animateScale: true
        }
      }
    })
  }
}

// Cycle de vie
onMounted(() => {
  loadStatistics()
  setTimeout(initCharts, 100)
})

onUnmounted(() => {
  if (revenueChartInstance) revenueChartInstance.destroy()
  if (ordersChartInstance) ordersChartInstance.destroy()
})
</script>

<style scoped>
.statistiques-view {
  padding: 2rem;
  min-height: 100vh;
}

.statistiques-container {
  max-width: 1200px;
  margin: 0 auto;
}
.statistiques-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.statistiques-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header-title {
  color: #333;
  margin: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 2.5em;
  font-weight: 700;
  transition: all 0.3s ease;
}

.header-title:hover {
  transform: translateY(-2px);
  text-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.filter-select {
  padding: 10px 16px;
  border: 2px solid #e1e5e9;
  border-radius: 12px;
  background: var(--success-color);
  font-size: 1em;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  outline: none;
}

.filter-select:hover {
  border-color: var(--hover-color);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
  transform: translateY(-1px);
}

.filter-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 6px 20px rgba(118, 75, 162, 0.2);
  transform: translateY(-2px);
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 25px;
  margin-bottom: 40px;
}

.stat-card {
  background: var(--success-color);
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  gap: 20px;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
  border: 1px solid transparent;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  transition: left 0.6s ease;
}

.stat-card:hover::before {
  left: 100%;
}

.stat-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
  border-color: rgba(102, 126, 234, 0.1);
}

.stat-card.revenue:hover { border-left-color: #4CAF50; }
.stat-card.orders:hover { border-left-color: #2196F3; }
.stat-card.customers:hover { border-left-color: #FF9800; }
.stat-card.loyalty:hover { border-left-color: #9C27B0; }

.hover-effect {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.stat-card:hover .hover-effect {
  opacity: 1;
}

.stat-icon {
  font-size: 2.5em;
  transition: all 0.3s ease;
  filter: grayscale(0.3);
}

.stat-card:hover .stat-icon {
  transform: scale(1.2) rotate(5deg);
  filter: grayscale(0);
}

.stat-content h3 {
  margin: 0 0 8px 0;
  font-size: 0.95em;
  color: var(--fin-color);
  transition: color 0.3s ease;
}

.stat-card:hover .stat-content h3 {
  color: var(--fin-color);
}

.stat-number {
  font-size: 2em;
  font-weight: 800;
  margin: 0;
  color: var(--fin-color);
  background: linear-gradient(135deg, #333 0%, #666 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  transition: all 0.3s ease;
}

.stat-card:hover .stat-number {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--hover-color) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-change, .stat-subtext {
  margin: 5px 0 0 0;
  font-size: 0.85em;
  transition: all 0.3s ease;
}

.stat-change.positive { 
  color: #4CAF50;
  font-weight: 600;
}
.stat-change.negative { 
  color: #f44336;
  font-weight: 600;
}
.stat-change.neutral { color: var(--fin-color) }

.stat-subtext {
  color: var(--fin-color);
}

.stat-card:hover .stat-subtext {
  color: var(--fin-color);
}

/* Couleurs spécifiques pour les cartes */
.stat-card.revenue { border-left: 6px solid #4CAF50; }
.stat-card.orders { border-left: 6px solid #2196F3; }
.stat-card.customers { border-left: 6px solid #FF9800; }
.stat-card.loyalty { border-left: 6px solid #9C27B0; }

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 25px;
  margin-bottom: 40px;
}

.chart-card {
  background: var(--success-color);
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
  border: 2px solid transparent;
}

.chart-card:hover {
  transform: translateY(-6px) scale(1.01);
  box-shadow: 0 16px 40px rgba(0,0,0,0.12);
  border-color: rgba(102, 126, 234, 0.1);
}

.chart-card h3 {
  margin: 0 0 20px 0;
  color: var(--secondary-color);
  font-size: 1.2em;
  font-weight: 600;
  transition: color 0.3s ease;
}

.chart-card:hover h3 {
  color: var(--primary-color);
}

.chart-container {
  position: relative;
  height: 220px;
  transition: transform 0.3s ease;
}

.chart-card:hover .chart-container {
  transform: scale(1.02);
}

.chart-hover-info {
  position: absolute;
  bottom: 20px;
  left: 0;
  right: 0;
  text-align: center;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s ease;
}

.chart-card:hover .chart-hover-info {
  opacity: 1;
  transform: translateY(0);
}

.chart-hover-info p {
  margin: 0;
  color: var(--secondary-color);
  font-size: 0.9em;
  font-weight: 500;
}

.loyalty-stats, .referral-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.loyalty-item, .referral-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 16px;
  border-radius: 10px;
  background: #f8f9fa;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  border: 1px solid transparent;
}

.loyalty-item:hover, .referral-item:hover {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--hover-color) 100%);
  transform: translateX(8px) scale(1.02);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  border-color: rgba(255,255,255,0.2);
}

.loyalty-item:hover .label,
.referral-item:hover .label,
.loyalty-item:hover .value,
.referral-item:hover .value {
  color: var(--success-color);
}

.label {
  color: var(--fin-color);
  font-weight: 500;
  transition: color 0.3s ease;
}

.value {
  font-weight: 700;
  color: var(--fin-color);
  transition: color 0.3s ease;
}

.table-section {
  background: var(--success-color);
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.table-section:hover {
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
  border-color: rgba(102, 126, 234, 0.05);
}

.table-section h3 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 1.3em;
  font-weight: 600;
  transition: color 0.3s ease;
}

.table-section:hover h3 {
  color: var(--primary-color);
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
}

table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
}

th, td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s ease;
}

th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  position: relative;
  overflow: hidden;
}

th::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.6s ease;
}

table:hover th::after {
  left: 100%;
}

.table-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
}

.table-row::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
}

.table-row:hover::before {
  width: 4px;
}

.table-row:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  transform: translateX(4px);
}

.table-row.row-hover td {
  padding-left: 20px;
}

.product-name {
  font-weight: 500;
  transition: all 0.3s ease;
}

.table-row:hover .product-name {
  color: var(--primary-color);
  font-weight: 600;
}

.quantity-badge {
  background: #f8f9fa;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85em;
  font-weight: 600;
  color: #666;
  transition: all 0.3s ease;
}

.table-row:hover .quantity-badge {
  background: linear-gradient(135deg, var(--primary-color)0%, var(--primary-color) 100%);
  color: white;
  transform: scale(1.1);
}

.trend {
  font-size: 1.2em;
  transition: all 0.3s ease;
}

.table-row:hover .trend {
  transform: scale(1.3);
}

.trend.up { color: #4CAF50; }
.trend.down { color: #f44336; }
.trend.stable { color: #666; }

/* Animations pour les éléments en hover */
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

@keyframes glow {
  0%, 100% { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
  50% { box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2); }
}

.stat-card:hover {
  animation: float 2s ease-in-out infinite, glow 3s ease-in-out infinite;
}

/* Responsive */
@media (max-width: 768px) {
  .statistiques-header {
    flex-direction: column;
    gap: 20px;
    align-items: stretch;
  }
  
  .stats-cards {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .charts-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .header-title {
    font-size: 2em;
    text-align: center;
  }
}

/* Animation pour le chargement initial */
.stat-card, .chart-card, .table-section {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Délais d'animation pour les cartes */
.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.chart-card:nth-child(1) { animation-delay: 0.5s; }
.chart-card:nth-child(2) { animation-delay: 0.6s; }
.chart-card:nth-child(3) { animation-delay: 0.7s; }
.chart-card:nth-child(4) { animation-delay: 0.8s; }
.table-section { animation-delay: 0.9s; }
</style>