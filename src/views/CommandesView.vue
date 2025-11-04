<template>
  <div class="dashboard-commandes">
    <!-- ==================== SECTION 1 : Statistiques globales ==================== -->
    <div class="stats-cards">
      <div class="card" v-for="card in stats" :key="card.titre">
        <div class="card-header">
          <span class="card-icon">{{ card.emoji }}</span>
          <h3>{{ card.titre }}</h3>
        </div>
        <div class="card-gauge">
          <Gauge :value="card.valeur" :max="totalCommands" :color="card.color" :size="140" />
        </div>
        <p class="card-value">{{ card.valeur }}</p>
        <small :class="card.tendance > 0 ? 'positive' : 'negative'">
          {{ card.tendance > 0 ? '+' : '' }}{{ card.tendance }}%
        </small>
      </div>
    </div>

    <!-- ==================== SECTION 2 : Graphique Camembert ==================== -->
    <div class="chart-section chart-pie">
      <h2>Répartition des Statuts de Commandes</h2>
      <div class="chart-pie-inner">
        <canvas id="statusChart"></canvas>
      </div>
      <div class="chart-pie-legend text-muted small mt-2">Survoler les segments pour voir les détails.</div>
    </div>

    <!-- ==================== SECTION 3 : Graphique Ligne Commandes ==================== -->
    <div class="chart-section">
      <h2>Évolution des Commandes</h2>
      <canvas id="ordersChart"></canvas>
    </div>

    <!-- ==================== SECTION 4 : Plats en Promotion ==================== -->
    <div class="promos-section">
      <h2>Plats en Promotion</h2>
      <div class="plats-grid">
        <div v-for="plat in plats" :key="plat.nom" class="plat-card">
          <img :src="plat.image" alt="Image du plat" />
          <div class="plat-info">
            <h3>{{ plat.nom }}</h3>
            <p>{{ plat.description }}</p>
            <p class="prix">{{ plat.prix }} FC</p>
            <span class="note">⭐ {{ plat.note }}/5</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== SECTION 5 : Flux des Commandes ==================== -->
    <div class="flux-section">
      <h2>Flux des Commandes</h2>
      <p class="small text-muted">Détails par type de commande (Livraison / Retrait / Sur place)</p>
      <canvas id="fluxChart"></canvas>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import Chart from "chart.js/auto";
import Gauge from '../components/Gauge.vue'

const stats = ref([
  { key: 'total', titre: "Total Commandes", valeur: 425, emoji: "📦", tendance: +5, color: '#3B82F6' },
  { key: 'pending', titre: "En attente", valeur: 25, emoji: "⏳", tendance: -3, color: '#FBBF24' },
  { key: 'resolved', titre: "Résolues", valeur: 380, emoji: "✅", tendance: +10, color: '#10B981' },
  { key: 'claims', titre: "Réclamations", valeur: 20, emoji: "⚠", tendance: +2, color: '#EF4444' },
]);

const totalCommands = computed(() => {
  const totalEntry = stats.value.find(c => c.key === 'total')
  if (totalEntry) return totalEntry.valeur
  return stats.value.reduce((s, c) => s + (c.valeur || 0), 0)
})

const plats = ref([
  { nom: "Pizza Margherita", description: "Tomate, mozzarella, basilic", prix: 9500, note: 4.5, image: "/src/images/80e59ddd335067ac9aad370dc04917b9.JPG" },
  { nom: "Burger Royal", description: "Boeuf, fromage, salade", prix: 11000, note: 4.7, image: "/src/images/breakfast.JPG" },
  { nom: "Poulet Grillé", description: "Mariné aux épices", prix: 12500, note: 4.8, image: "/src/images/plats.JPG" },
  { nom: "Pâtes Carbonara", description: "Crème, œuf, lardons", prix: 9000, note: 4.6, image: "/src/images/e6defb19e0d9a2947ce040c985ad60b3.JPG" },
]);

onMounted(() => {
  // === Graphique Camembert (petit, interactif) ===
  const ctxStatus = document.getElementById("statusChart");
  if (ctxStatus) {
    try {
      new Chart(ctxStatus, {
    type: "pie",
    data: {
      labels: ["Nouvelles", "En attente", "Résolues", "Réclamations"],
      datasets: [
        {
          data: [40, 25, 30, 15],
          backgroundColor: ["#3B82F6", "#FBBF24", "#10B981", "#EF4444"],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true, position: 'right', labels: { boxWidth: 12, padding: 8 } },
        tooltip: {
          callbacks: {
            label: function(context){
              const label = context.label || ''
              const value = context.raw || 0
              const sum = context.dataset.data.reduce((a,b)=>a+b,0)
              const pct = ((value / sum) * 100).toFixed(1)
              return `${label}: ${value} (${pct}%)`
            }
          }
        }
      }
    },
      })
    } catch (e) { console.error('statusChart init error', e) }
  } else { console.warn('statusChart canvas not found') }

  // === Graphique Ligne (Évolution des commandes) ===
  const ctxOrders = document.getElementById("ordersChart");
  if (ctxOrders) {
    try {
      new Chart(ctxOrders, {
    type: "line",
    data: {
      labels: ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
      datasets: [
        {
          label: "Commandes journalières",
          data: [120, 200, 150, 300, 280, 350, 400],
          borderColor: "#4CAF50",
          backgroundColor: "rgba(76, 175, 80, 0.2)",
          fill: true,
          tension: 0.4,
        },
      ],
    },
        options: { responsive: true, plugins: { legend: { display: false } } },
      })
    } catch (e) { console.error('ordersChart init error', e) }
  } else { console.warn('ordersChart canvas not found') }

  // === Graphique Barres (Flux des commandes) - breakdown par type pour être plus parlant ===
  const ctxFlux = document.getElementById("fluxChart");
  if (ctxFlux) {
    try {
      new Chart(ctxFlux, {
    type: "bar",
    data: {
      labels: ["Semaine 1", "Semaine 2", "Semaine 3", "Semaine 4"],
      datasets: [
        { label: 'Livraison', data: [300, 420, 480, 520], backgroundColor: '#3B82F6' },
        { label: 'Retrait', data: [120, 200, 220, 300], backgroundColor: '#10B981' },
        { label: 'Sur place', data: [80, 80, 100, 130], backgroundColor: '#FBBF24' }
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context){
              const label = context.dataset.label || ''
                  const value = (context.parsed?.y ?? context.parsed) || 0
                  return `${label}: ${value}`
            }
          }
        }
      },
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true }
      }
    },
      })
    } catch (e) { console.error('fluxChart init error', e) }
  } else { console.warn('fluxChart canvas not found') }
  // no additional charts for gauges here; gauges rendered as components in template
});
</script>

<style scoped>
.dashboard-commandes {
  font-family: 'Poppins', sans-serif;
  padding: 20px;
  /* background: #f9fafb; */
}

.card-gauge {
  display: flex;
  justify-content: center;
  align-items: center;
  height: auto; /* allow gauge to define its size */
  padding: 8px 0;
  margin: 10px 0;
}


/* Cartes statistiques */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}
.card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.card-value {
  font-size: 26px;
  font-weight: bold;
  margin-top: 10px;
}
.positive { color: #10B981; }
.negative { color: #EF4444; }

/* Graphiques */
.chart-section {
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}
.chart-section h2 {
  margin-bottom: 20px;
  font-size: 20px;
}

.chart-pie .chart-pie-inner{ width: 75%; max-width: 420px; margin: 0 auto; height: 320px }
.chart-pie .chart-pie-inner canvas{ width: 100% !important; height: 100% !important }
.chart-pie .chart-pie-legend{ text-align:center }

/* Plats */
.promos-section h2 {
  margin-bottom: 20px;
}
.plats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
}
.plat-card {
  background: white;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  transition: transform 0.3s;
}
.plat-card:hover {
  transform: translateY(-5px);
}
.plat-card img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 10px;
}
.plat-info { padding: 10px; }
.prix { color: #4CAF50; font-weight: bold; }
.note { font-size: 14px; color: #555; }

/* Flux */
.flux-section {
  margin-top: 30px;
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.flux-section canvas{ width: 100% !important; height: 320px !important }
</style>