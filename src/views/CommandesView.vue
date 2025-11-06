<template>
  <div class="commandes-view">
    <!-- Header -->
    <div class="view-header">
      <h2>Tableau de Bord des Commandes</h2>
      <p>Surveillez et géz l'ensemble des commandes de votre restaurant</p>
    </div>

    <!-- ==================== SECTION 1 : Statistiques globales ==================== -->
    <div class="stats-section">
      <h3>Aperçu des Commandes</h3>
      <div class="stats-cards">
        <div class="stat-card" v-for="stat in stats" :key="stat.titre">
          
          <div class="stat-content">
            <h4>{{ stat.titre }}</h4>
            <div class="stat-value">{{ stat.valeur }}</div>
            <div :class="['stat-trend', stat.tendance > 0 ? 'positive' : 'negative']">
              {{ stat.tendance > 0 ? '↗' : '↘' }} {{ Math.abs(stat.tendance) }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== SECTION 2 : Graphiques principaux ==================== -->
    <div class="charts-grid">
      <!-- Graphique Camembert -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Répartition des Statuts</h3>
          <span class="chart-subtitle">Distribution des commandes par statut</span>
        </div>
        <div class="chart-container">
          <canvas id="statusChart"></canvas>
        </div>
      </div>

      <!-- Graphique Ligne -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Évolution Hebdomadaire</h3>
          <span class="chart-subtitle">Tendance des commandes sur 7 jours</span>
        </div>
        <div class="chart-container">
          <canvas id="ordersChart"></canvas>
        </div>
      </div>
    </div>

    <!-- ==================== SECTION 3 : Plats en Promotion ==================== -->
    <div class="promotions-section">
      <div class="section-header">
        <h3>Plats en Promotion</h3>
        <button @click="navigateToPromotions" class="btn-primary">
          Gérer les Promotions
        </button>
      </div>
      
      <div v-if="promotedItems.length > 0" class="promotions-grid">
        <div v-for="item in promotedItems" :key="item.id" class="promo-card">
          <div class="promo-image">
            <img :src="item.image || '/src/images/placeholder-food.jpg'" :alt="item.name" />
            <div class="promo-badge">PROMO</div>
          </div>
          <div class="promo-content">
            <h4>{{ item.name }}</h4>
            <p class="promo-description">{{ item.description }}</p>
            <div class="price-section">
              <span class="original-price">{{ formatPrice(item.originalPrice) }}</span>
              <span class="promo-price">{{ formatPrice(item.promotionalPrice) }}</span>
            </div>
            <div class="promo-dates">
              Jusqu'au {{ formatDate(item.endDate) }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state">
        
        <h4>Aucune promotion active</h4>
        <p>Créez des promotions pour booster vos ventes</p>
        <button @click="navigateToPromotions" class="btn-primary">
          Créer une Promotion
        </button>
      </div>
    </div>

    <!-- ==================== SECTION 4 : Analyse des Flux ==================== -->
    <div class="analysis-section">
      <div class="section-header">
        <h3>Analyse des Canaux de Commande</h3>
        <span class="section-subtitle">Répartition par type de service</span>
      </div>
      
      <div class="analysis-grid">
        <div class="analysis-card large">
          <div class="chart-container">
            <canvas id="fluxChart"></canvas>
          </div>
        </div>
        
        <div class="analysis-card">
          <div class="metric-card">
            
            <div class="metric-content">
              <h4>Livraison</h4>
              <div class="metric-value">65%</div>
              <div class="metric-trend positive">+12%</div>
            </div>
          </div>
        </div>
        
        <div class="analysis-card">
          <div class="metric-card">
            
            <div class="metric-content">
              <h4>Retrait</h4>
              <div class="metric-value">25%</div>
              <div class="metric-trend positive">+5%</div>
            </div>
          </div>
        </div>
        
        <div class="analysis-card">
          <div class="metric-card">
            
            <div class="metric-content">
              <h4>Sur place</h4>
              <div class="metric-value">10%</div>
              <div class="metric-trend negative">-3%</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== SECTION 5 : Actions Rapides ==================== -->
    <div class="quick-actions">
      <h3>Actions Rapides</h3>
      <div class="actions-grid">
        <button @click="navigateToMenu" class="action-card">
          
          <div class="action-content">
            <h4>Modifier le Menu</h4>
            <p>Ajouter ou modifier des plats</p>
          </div>
        </button>
        
        <button @click="navigateToPromotions" class="action-card">
          
          <div class="action-content">
            <h4>Gérer les Promotions</h4>
            <p>Créer des offres spéciales</p>
          </div>
        </button>
        
        <button @click="viewReports" class="action-card">
         
          <div class="action-content">
            <h4>Rapports Détaillés</h4>
            <p>Analyses approfondies</p>
          </div>
        </button>
        
        <button @click="manageAPI" class="action-card">
          <div class="action-icon">🔌</div>
          <div class="action-content">
            <h4>Intégrations API</h4>
            <p>Configurer les services externes</p>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import Chart from "chart.js/auto";

const router = useRouter();

// Données des statistiques
const stats = ref([
  { titre: "Total Commandes", valeur: 425, emoji: "📦", tendance: +5 },
  { titre: "En Attente", valeur: 25, emoji: "⏳", tendance: -3 },
  { titre: "Traitées", valeur: 380, emoji: "✅", tendance: +10 },
  { titre: "Réclamations", valeur: 20, emoji: "⚠️", tendance: +2 },
]);

// Données des plats en promotion (connectées aux promotions réelles)
const promotedItems = ref([
  {
    id: 1,
    name: "Pizza Margherita",
    description: "Tomate, mozzarella, basilic frais - Promotion spéciale",
    originalPrice: 15000,
    promotionalPrice: 12000,
    image: "/src/images/80e59ddd335067ac9aad370dc04917b9.JPG",
    endDate: "2024-12-31"
  },
  {
    id: 2,
    name: "Burger Royal",
    description: "Bœuf, fromage, salade - Offre limitée",
    originalPrice: 13000,
    promotionalPrice: 11000,
    image: "/src/images/breakfast.JPG",
    endDate: "2024-12-25"
  },
  {
    id: 3,
    name: "Poulet Grillé",
    description: "Mariné aux épices - Menu du jour",
    originalPrice: 14000,
    promotionalPrice: 12500,
    image: "/src/images/plats.JPG",
    endDate: "2024-12-20"
  }
]);

// Méthodes de navigation
const navigateToPromotions = () => {
  router.push('/admin/menu?section=promotions');
};

const navigateToMenu = () => {
  router.push('/admin/menu');
};

const viewReports = () => {
  // À implémenter avec l'API
  console.log("Voir les rapports détaillés");
};

const manageAPI = () => {
  // À implémenter avec l'API
  console.log("Gérer les intégrations API");
};

// Méthodes utilitaires
const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price) + ' FC';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR');
};

// Initialisation des graphiques
onMounted(() => {
  // Graphique Camembert des statuts
  const ctxStatus = document.getElementById("statusChart");
  if (ctxStatus) {
    new Chart(ctxStatus, {
      type: "doughnut",
      data: {
        labels: ["Nouvelles", "En attente", "Traitées", "Réclamations"],
        datasets: [{
          data: [120, 25, 250, 20],
          backgroundColor: ["#a89f91", "#e9b949", "#10b981", "#ef4444"],
          borderWidth: 2,
          borderColor: "#ffffff"
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
            }
          }
        }
      }
    });
  }

  // Graphique d'évolution des commandes
  const ctxOrders = document.getElementById("ordersChart");
  if (ctxOrders) {
    new Chart(ctxOrders, {
      type: "line",
      data: {
        labels: ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
        datasets: [{
          label: "Commandes",
          data: [45, 62, 58, 73, 81, 95, 110],
          borderColor: "#a89f91",
          backgroundColor: "rgba(168, 159, 145, 0.1)",
          fill: true,
          tension: 0.4,
          borderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(0,0,0,0.1)"
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }

  // Graphique des flux de commandes
  const ctxFlux = document.getElementById("fluxChart");
  if (ctxFlux) {
    new Chart(ctxFlux, {
      type: "bar",
      data: {
        labels: ["Sem 1", "Sem 2", "Sem 3", "Sem 4"],
        datasets: [
          {
            label: 'Livraison',
            data: [180, 220, 260, 300],
            backgroundColor: '#a89f91',
            borderRadius: 6
          },
          {
            label: 'Retrait',
            data: [80, 95, 110, 125],
            backgroundColor: '#8a8174',
            borderRadius: 6
          },
          {
            label: 'Sur place',
            data: [40, 45, 50, 55],
            backgroundColor: '#6c757d',
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          }
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(0,0,0,0.1)"
            }
          }
        }
      }
    });
  }
});
</script>

<style scoped>
.commandes-view {
  padding: 0;
  background-color: #f8f9fa;
  min-height: 100vh;
}

.view-header {
  background: white;
  padding: 2rem;
  margin-bottom: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-header h2 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.8rem;
}

.view-header p {
  margin: 0;
  color: #6c757d;
  font-size: 1.1rem;
}

/* Section des statistiques */
.stats-section {
  margin-bottom: 2rem;
}

.stats-section h3 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-size: 1.3rem;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.1rem;
}

.stat-card {
  background: white;
  padding: 1.1rem;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.stat-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  border-radius: 12px;
}

.stat-content h4 {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
  font-size: 0.9rem;
  font-weight: 600;
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.stat-trend {
  font-size: 0.85rem;
  font-weight: 600;
}

.stat-trend.positive {
  color: #10b981;
}

.stat-trend.negative {
  color: #ef4444;
}

/* Grille des graphiques */
.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-header {
  margin-bottom: 1.5rem;
}

.chart-header h3 {
  margin: 0 0 0.25rem 0;
  color: #2c3e50;
  font-size: 1.2rem;
}

.chart-subtitle {
  color: #6c757d;
  font-size: 0.9rem;
}

.chart-container {
  height: 300px;
  position: relative;
}

/* Section des promotions */
.promotions-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.3rem;
}

.section-subtitle {
  color: #6c757d;
  font-size: 0.9rem;
}

.promotions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.promo-card {
  border: 1px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.promo-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.promo-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.promo-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.promo-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: #ef4444;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.promo-content {
  padding: 1.25rem;
}

.promo-content h4 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.promo-description {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.price-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.original-price {
  color: #6c757d;
  text-decoration: line-through;
  font-size: 0.9rem;
}

.promo-price {
  color: #ef4444;
  font-weight: bold;
  font-size: 1.1rem;
}

.promo-dates {
  color: #6c757d;
  font-size: 0.8rem;
}

/* Section d'analyse */
.analysis-section {
  margin-bottom: 2rem;
}

.analysis-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1.5rem;
}

.analysis-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.analysis-card.large {
  grid-column: span 1;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.metric-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  border-radius: 12px;
}

.metric-content h4 {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
  font-size: 0.9rem;
  font-weight: 600;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

/* Actions rapides */
.quick-actions {
  margin-bottom: 2rem;
}

.quick-actions h3 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-size: 1.3rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: none;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  background: #f8f9fa;
}

.action-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  border-radius: 12px;
}

.action-content h4 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.1rem;
}

.action-content p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
}

/* États vides */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #6c757d;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-state h4 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
}

/* Boutons */
.btn-primary {
  background: #a89f91;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-primary:hover {
  background: #8a8174;
}

/* Responsive */
@media (max-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  
  .analysis-grid {
    grid-template-columns: 1fr;
  }
  
  .analysis-card.large {
    grid-column: span 1;
  }
}

@media (max-width: 768px) {
  .stats-cards {
    grid-template-columns: 1fr;
  }
  
  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .promotions-grid {
    grid-template-columns: 1fr;
  }
}
</style>