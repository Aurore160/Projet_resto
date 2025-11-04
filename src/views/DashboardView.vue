<template>
  <div class="dashboard-admin">
    <!-- En-tête de bienvenue -->
    <div class="dashboard-header">
      <h1></h1>
      <div class="header-actions">
        <button class="btn btn-primary">📊 Rapport du jour</button>
      </div>
    </div>

    <!-- Section Overall - Cartes horizontales -->
    <div class="overall-section">
      <h2 class="section-title">Aperçu Général</h2>
      <div class="overall-grid">
        <div class="overall-card">
          <div class="overall-icon">📦</div>
          <div class="overall-content">
            <h3>Employers</h3>
            <div class="overall-value">{{ stats.stockTotal }} unités</div>
            <div class="overall-trend positive">+8%</div>
          </div>
        </div>

        <div class="overall-card">
          <div class="overall-icon">🏭</div>
          <div class="overall-content">
            <h3>Production Total</h3>
            <div class="overall-value">{{ stats.productionTotal }} plats</div>
            <div class="overall-trend positive">+15%</div>
          </div>
        </div>

        <div class="overall-card">
          <div class="overall-icon">👥</div>
          <div class="overall-content">
            <h3>Utilisateurs Actifs</h3>
            <div class="overall-value">{{ stats.utilisateursActifs }}</div>
            <div class="overall-trend positive">+12%</div>
          </div>
        </div>

        <div class="overall-card">
          <div class="overall-icon">🛒</div>
          <div class="overall-content">
            <h3>Commandes du Jour</h3>
            <div class="overall-value">{{ stats.commandesJour }}</div>
            <div class="overall-trend positive">+20%</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Grid principal -->
    <div class="dashboard-grid">
      <!-- Colonne gauche -->
      <div class="left-column">
        <div class="chart-card">
          <div class="card-header"><h3>Valeurs d'Inventaire</h3></div>
          <div class="chart-container">
            <div class="stock-chart">
              <div class="stock-bar total-stock">
                <div class="bar-label">Stock Total</div>
                <div class="bar-container">
                  <div class="bar-fill" :style="{ width: stockData.total.percentage + '%' }">
                    <span class="bar-value">{{ formatCurrency(stockData.total.value) }}</span>
                  </div>
                </div>
              </div>
              <div class="stock-bar sold-stock">
                <div class="bar-label">Stock Vendue</div>
                <div class="bar-container">
                  <div class="bar-fill" :style="{ width: stockData.sold.percentage + '%' }">
                    <span class="bar-value">{{ formatCurrency(stockData.sold.value) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div style="margin-top:16px;">
              <PieChart :labels="statusLabels" :values="statusValues" title="Répartition des statuts" />
            </div>
          </div>
          <div class="chart-summary">
            <div class="summary-item"><span class="summary-label">Taux de vente</span><span class="summary-value">{{ stockData.sold.percentage }}%</span></div>
            <div class="summary-item"><span class="summary-label">Reste en stock</span><span class="summary-value">{{ formatCurrency(stockData.remaining) }}</span></div>
          </div>
        </div>

        <div class="top-stores-card">
          <div class="card-header"><h3>Top 10 Plats par Commandes</h3></div>
          <div class="stores-list">
            <div v-for="(plat, index) in topPlats" :key="plat.id" class="store-item">
              <div class="store-info"><span class="store-rank">#{{ index + 1 }}</span><span class="store-name">{{ plat.nom }}</span></div>
              <span class="store-sales">{{ plat.commandes }} FC</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Colonne droite -->
      <div class="right-column">
        <div class="chart-card">
          <div class="card-header"><h3>Dépenses vs Profits</h3></div>
          <div class="chart-container">
            <div class="bar-chart">
              <!-- Chart.js dynamic chart (BarChart component) -->
              <BarChart :labels="monthlyData.map(m => m.mois)" :datasets="barDatasets" />
            </div>
          </div>
        </div>

  <!-- Summary cards moved out of the chart card into their own card below -->
  <div class="chart-card summary-block">
          <div class="summary-cards">
            <div class="summary-card profit-card">
              <div class="summary-icon">💰</div>
              <div class="summary-content">
                <h4>Profit Net</h4>
                <div class="summary-value">{{ formatCurrency(netProfit) }}</div>
                <div class="summary-trend positive">+18%</div>
              </div>
            </div>

            <div class="summary-card expense-card">
              <div class="summary-icon">💸</div>
              <div class="summary-content">
                <h4>Dépenses Moy.</h4>
                <div class="summary-value">{{ formatCurrency(avgExpenses) }}</div>
                <div class="summary-trend negative">-5%</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section inférieure - Dernières activités -->
    <div class="bottom-section">
      <div class="recent-activities">
        <div class="card-header"><h3>Activités Récentes</h3></div>
        <div class="activities-list">
          <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div class="activity-icon">{{ activity.icon }}</div>
            <div class="activity-content"><p class="activity-text">{{ activity.description }}</p><span class="activity-time">{{ activity.time }}</span></div>
            <span class="activity-status" :class="activity.status">{{ activity.statusText }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PieChart from '../components/PieChart.vue'
import BarChart from '../components/BarChart.vue'

const adminName = ref('Admin')

const stats = ref({
  stockTotal: 1240,
  productionTotal: 5320,
  utilisateursActifs: 842,
  commandesJour: 128
})

const stockData = ref({
  total: { value: 124000, percentage: 100 },
  sold: { value: 92000, percentage: 74 },
  remaining: 32000
})
// Responsive histogram sizing
const svgWidth = 700
const svgHeight = 220
const topMargin = 10
const chartHeight = 160 // area used for bars
const paddingLeft = 20
const paddingRight = 20

const perMonthSlot = computed(() => {
  const n = Math.max(1, monthlyData.value.length)
  return (svgWidth - paddingLeft - paddingRight) / n
})

const perBarWidth = computed(() => {
  // two bars per month (expense + profit) with small gap
  const slot = perMonthSlot.value
  const w = Math.floor(slot * 0.38)
  return Math.max(8, Math.min(60, w))
})

const barGap = 6

const getBarX = (index) => {
  const slot = perMonthSlot.value
  const totalBarsWidth = perBarWidth.value * 2 + barGap
  const start = paddingLeft + index * slot + (slot - totalBarsWidth) / 2
  return start
}

const maxY = computed(() => Math.max(maxProfit.value, maxExpense.value) || 1)

const getBarHeight = (value) => {
  const v = Number(value || 0)
  return (v / maxY.value) * chartHeight
}

const getBarY = (value) => {
  const h = getBarHeight(value)
  return topMargin + (chartHeight - h)
}


const statusLabels = ref(['Disponible', 'En rupture', 'Bientôt épuisé'])
const statusValues = ref([74, 18, 8])

const topPlats = ref([
  { id: 1, nom: 'Poulet Braisé', commandes: 512 },
  { id: 2, nom: 'Frites', commandes: 489 },
  { id: 3, nom: 'Poisson Grillé', commandes: 412 },
  { id: 4, nom: 'Pâtes Carbonara', commandes: 399 },
  { id: 5, nom: 'Salade César', commandes: 344 },
  { id: 6, nom: 'Tiramisu', commandes: 276 },
  { id: 7, nom: 'Poulet DG', commandes: 213 },
  { id: 8, nom: 'Riz Sauce Arachide', commandes: 183 },
  { id: 9, nom: 'Saka Saka', commandes: 156 },
  { id: 10, nom: 'Beignets', commandes: 127 }
])

// Extend months to December with placeholder (0) values for future months
const monthlyData = ref([
  { mois: 'Jan', profits: 12000, expenses: 8000 },
  { mois: 'Fév', profits: 15000, expenses: 8500 },
  { mois: 'Mar', profits: 18000, expenses: 9000 },
  { mois: 'Avr', profits: 22000, expenses: 9500 },
  { mois: 'Mai', profits: 25000, expenses: 10000 },
  { mois: 'Jun', profits: 28000, expenses: 10500 },
  { mois: 'Jul', profits: 32000, expenses: 11000 },
  { mois: 'Aoû', profits: 0, expenses: 0 },
  { mois: 'Sep', profits: 0, expenses: 0 },
  { mois: 'Oct', profits: 0, expenses: 0 },
  { mois: 'Nov', profits: 0, expenses: 0 },
  { mois: 'Déc', profits: 0, expenses: 0 }
])

// prepare datasets for BarChart
const barDatasets = computed(() => {
  return [
    {
      label: 'Dépenses',
      data: monthlyData.value.map(m => m.expenses),
      backgroundColor: '#EF4444'
    },
    {
      label: 'Profits',
      data: monthlyData.value.map(m => m.profits),
      backgroundColor: '#10B981'
    }
  ]
})

const recentActivities = ref([
  { id: 1, icon: '📦', description: 'Nouvelle commande - Poulet Braisé x2', time: 'Il y a 5 min', status: 'completed', statusText: 'Livré' },
  { id: 2, icon: '👥', description: 'Nouvel utilisateur - Marie Dupont', time: 'Il y a 12 min', status: 'completed', statusText: 'Actif' },
  { id: 3, icon: '⚠️', description: 'Stock faible - Poisson Grillé', time: 'Il y a 25 min', status: 'pending', statusText: 'En attente' },
  { id: 4, icon: '💸', description: 'Paiement échoué - Commande #2456', time: 'Il y a 1h', status: 'failed', statusText: 'Échec' }
])

// Computed min/max pour le graphique (doivent être définis avant les paths)
const minProfit = computed(() => Math.min(...monthlyData.value.map(d => d.profits)))
const maxProfit = computed(() => Math.max(...monthlyData.value.map(d => d.profits)))
const minExpense = computed(() => Math.min(...monthlyData.value.map(d => d.expenses)))
const maxExpense = computed(() => Math.max(...monthlyData.value.map(d => d.expenses)))

// Computed properties pour le tracé des lignes (protéger contre division par zéro)
const profitsPath = computed(() => {
  const range = (maxProfit.value - minProfit.value) || 1
  const points = monthlyData.value.map((point, index) => {
    const x = (index / (monthlyData.value.length - 1)) * 500
    const y = 200 - ((point.profits - minProfit.value) / range) * 180
    return `${index === 0 ? 'M' : 'L'} ${x} ${y}`
  })
  return points.join(' ')
})

const expensesPath = computed(() => {
  const range = (maxExpense.value - minExpense.value) || 1
  const points = monthlyData.value.map((point, index) => {
    const x = (index / (monthlyData.value.length - 1)) * 500
    const y = 200 - ((point.expenses - minExpense.value) / range) * 180
    return `${index === 0 ? 'M' : 'L'} ${x} ${y}`
  })
  return points.join(' ')
})

const getXPosition = (index) => (index / (monthlyData.value.length - 1)) * 500
const getYPosition = (value, type) => {
  const min = type === 'profit' ? minProfit.value : minExpense.value
  const max = type === 'profit' ? maxProfit.value : maxExpense.value
  const range = (max - min) || 1
  return 200 - ((value - min) / range) * 180
}

const totalProfits = computed(() => monthlyData.value.reduce((sum, month) => sum + month.profits, 0))
const totalExpenses = computed(() => monthlyData.value.reduce((sum, month) => sum + month.expenses, 0))
const netProfit = computed(() => totalProfits.value - totalExpenses.value)
const avgExpenses = computed(() => totalExpenses.value / monthlyData.value.length)

const formatCurrency = (amount) => {
  const n = Number(amount || 0)
  // format with thousands separator and no decimals, append ' FC'
  return `${n.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FC`
}

onMounted(async () => {
  await chargerDonnees()
})

const chargerDonnees = async () => {
  // TODO: Intégration avec Supabase
}
</script>

<style scoped>
.dashboard-admin {
  padding: 2rem;
  /* background-color: #f8fafc; */
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  color: #f0f2f5;
  font-size: 2rem;
  font-weight: 600;
  margin: 0;
}

.btn-primary {
  background-color: #E4DBC6;
  color: #2d3748;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background-color: #d4c9ac;
  transform: translateY(-2px);
}

/* Section Overall */
.overall-section {
  margin-bottom: 2rem;
}

.section-title {
  color: #2d3748;
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.overall-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.overall-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.3s ease;
}

.overall-card:hover {
  transform: translateY(-5px);
}

.overall-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f7fafc;
  border-radius: 12px;
}

.overall-content h3 {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.overall-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.overall-trend {
  font-size: 0.8rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  display: inline-block;
}

.overall-trend.positive {
  background: #c6f6d5;
  color: #22543d;
}

.overall-trend.negative {
  background: #fed7d7;
  color: #742a2a;
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.left-column, .right-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Cartes générales */
.chart-card, .top-stores-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
}

.card-header {
  margin-bottom: 1.5rem;
}

.card-header h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

/* Diagramme Stock */
.stock-chart {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stock-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bar-label {
  width: 100px;
  color: #718096;
  font-weight: 500;
}

.bar-container {
  flex: 1;
  height: 30px;
  background: #f7fafc;
  border-radius: 15px;
  overflow: hidden;
  position: relative;
}

.bar-fill {
  height: 100%;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 1rem;
  transition: width 0.5s ease;
}

.total-stock .bar-fill {
  background: linear-gradient(90deg, #E4DBC6, #d4c9ac);
}

.sold-stock .bar-fill {
  background: linear-gradient(90deg, #10B981, #059669);
}

.bar-value {
  color: white;
  font-weight: 600;
  font-size: 0.8rem;
}

.chart-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  /* removed border-top to prevent overlap with PieChart legend */
  border-top: none;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  color: #718096;
  font-size: 0.9rem;
}

.summary-value {
  font-weight: 600;
  color: #2d3748;
}

/* Graphique Ligne */
.line-chart {
  position: relative;
  height: 200px;
  margin: 1rem 0;
}

.chart-grid {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.grid-line {
  /* Masque les lignes de grille si vous ne les souhaitez pas */
  display: none;
}

.chart-lines {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 30px;
}

.line {
  width: 100%;
  height: 100%;
}

.chart-labels {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  padding: 0 20px;
}

.chart-label {
  font-size: 0.8rem;
  color: #718096;
  font-weight: 500;
}

.chart-legend {
  display: flex;
  gap: 2rem;
  justify-content: center;
  margin-top: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #4a5568;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.profits-color {
  background: #10B981;
}

.expenses-color {
  background: #EF4444;
}

.legend-value {
  font-weight: 600;
  margin-left: 0.5rem;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.summary-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.summary-icon {
  font-size: 2rem;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.profit-card .summary-icon {
  background: #c6f6d5;
  color: #22543d;
}

.expense-card .summary-icon {
  background: #fed7d7;
  color: #742a2a;
}

.summary-content h4 {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  margin: 0 0 0.5rem 0;
}

.summary-value {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.summary-trend {
  font-size: 0.8rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  display: inline-block;
}

.summary-trend.positive {
  background: #c6f6d5;
  color: #22543d;
}

.summary-trend.negative {
  background: #fed7d7;
  color: #742a2a;
}

/* Top Stores */
.stores-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.store-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f7fafc;
}

.store-item:last-child {
  border-bottom: none;
}

.store-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.store-rank {
  background: #E4DBC6;
  color: #2d3748;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.store-name {
  color: #4a5568;
  font-weight: 500;
}

.store-sales {
  color: #2d3748;
  font-weight: 600;
}

/* Bottom Section */
.bottom-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
}

.activities-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
}

.activity-icon {
  font-size: 1.2rem;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-weight: 500;
}

.activity-time {
  font-size: 0.8rem;
  color: #718096;
}

.activity-status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.activity-status.completed {
  background: #c6f6d5;
  color: #22543d;
}

.activity-status.pending {
  background: #feebcb;
  color: #744210;
}

.activity-status.failed {
  background: #fed7d7;
  color: #742a2a;
}

/* Responsive */
@media (max-width: 1200px) {
  .overall-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-admin {
    padding: 1rem;
  }
  
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .overall-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
}

/* push the summary block slightly lower so it doesn't align with left column top cards */
.summary-block {
  margin-top: 20rem; /* user requested ~10x lower position */
}
</style>