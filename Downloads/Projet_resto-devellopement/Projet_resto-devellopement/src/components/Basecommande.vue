<template>
  <div class="orders-management">
    <!-- Espace réservé pour la navigation bar -->
    <div class="nav-bar-space"></div>
    
    <div class="container">
      <!-- Filtres -->
      <div class="filters-section">
        <div class="row g-4">
          <!-- Filtre Statut -->
          <div class="col-md-6 col-lg-3">
            <div class="filter-group">
              <label class="filter-label">Statut</label>
              <div class="status-filters">
                <label class="status-checkbox">
                  <input 
                    type="checkbox" 
                    v-model="filters.status.enCours"
                    @change="applyFilters"
                  >
                  <span class="checkmark"></span>
                  <span class="status-text">En cours</span>
                  <span class="status-badge en-cours"></span>
                </label>
                <label class="status-checkbox">
                  <input 
                    type="checkbox" 
                    v-model="filters.status.livre"
                    @change="applyFilters"
                  >
                  <span class="checkmark"></span>
                  <span class="status-text">Livré</span>
                  <span class="status-badge livre"></span>
                </label>
              </div>
            </div>
          </div>

          <!-- Filtre Date -->
          <div class="col-md-6 col-lg-3">
            <div class="filter-group">
              <label class="filter-label">Date</label>
              <select 
                class="form-select date-filter"
                v-model="filters.date"
                @change="applyFilters"
              >
                <option value="all">Toutes les dates</option>
                <option value="today">Aujourd'hui</option>
                <option value="yesterday">Hier</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
              </select>
            </div>
          </div>

          <!-- Recherche Auteur -->
          <div class="col-md-6 col-lg-3">
            <div class="filter-group">
              <label class="filter-label">Recherche par Auteur</label>
              <div class="search-input-wrapper">
                <input 
                  type="text" 
                  class="form-control search-input"
                  placeholder="Nom de l'auteur..."
                  v-model="filters.searchAuthor"
                  @input="applyFilters"
                >
                <i class="fas fa-search search-icon"></i>
              </div>
            </div>
          </div>

          <!-- Recherche Plat -->
          <div class="col-md-6 col-lg-3">
            <div class="filter-group">
              <label class="filter-label">Recherche par Plat</label>
              <div class="search-input-wrapper">
                <input 
                  type="text" 
                  class="form-control search-input"
                  placeholder="Nom du plat..."
                  v-model="filters.searchDish"
                  @input="applyFilters"
                >
                <i class="fas fa-search search-icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tableau des commandes -->
      <div class="orders-table-section">
        <div class="table-responsive">
          <table class="orders-table">
            <thead>
              <tr>
                <th class="column-plat">
                  <span>Plat</span>
                </th>
                <th class="column-prix">
                  <span>Prix unitaire</span>
                </th>
                <th class="column-quantite">
                  <span>Qte</span>
                </th>
                <th class="column-auteur">
                  <span>Auteur</span>
                </th>
                <th class="column-statut">
                  <span>Statut</span>
                </th>
                <th class="column-montant">
                  <span>Montant</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="order in filteredOrders" 
                :key="order.id"
                class="order-row"
              >
                <td class="cell-plat">
                  <div class="dish-info">
                    <img 
                      :src="order.dishImage" 
                      :alt="order.dishName"
                      class="dish-image"
                    >
                    <div class="dish-details">
                      <span class="dish-name">{{ order.dishName }}</span>
                      <span class="dish-description">{{ order.dishDescription }}</span>
                    </div>
                  </div>
                </td>
                <td class="cell-prix">
                  <span class="price">{{ order.unitPrice }} FC</span>
                </td>
                <td class="cell-quantite">
                  <span class="quantity">{{ order.quantity }}</span>
                </td>
                <td class="cell-auteur">
                  <div class="author-info">
                    <img 
                      :src="order.authorAvatar" 
                      :alt="order.authorName"
                      class="author-avatar"
                    >
                    <span class="author-name">{{ order.authorName }}</span>
                  </div>
                </td>
                <td class="cell-statut">
                  <!-- SLOT pour le statut - personnalisable -->
                  <slot name="status-cell" :order="order" :updateStatus="updateOrderStatus">
                    <!-- Contenu par défaut - statut en lecture seule -->
                    <span class="status-badge" :class="getStatusClass(order.status)">
                      {{ getStatusText(order.status) }}
                    </span>
                  </slot>
                </td>
                <td class="cell-montant">
                  <span class="amount">{{ calculateAmount(order) }} FC</span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Message si aucune commande -->
          <div v-if="filteredOrders.length === 0" class="no-orders">
            <div class="no-orders-icon">📋</div>
            <h3>Aucune commande trouvée</h3>
            <p>Ajustez vos filtres pour voir plus de résultats</p>
          </div>
        </div>

        <!-- Résumé -->
        <div class="orders-summary" v-if="filteredOrders.length > 0">
          <div class="summary-item">
            <span class="summary-label">Total commandes:</span>
            <span class="summary-value">{{ filteredOrders.length }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Montant total:</span>
            <span class="summary-value">{{ calculateTotalAmount() }} FC</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">En cours:</span>
            <span class="summary-value">{{ getOrdersByStatus('en_cours').length }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Livrés:</span>
            <span class="summary-value">{{ getOrdersByStatus('livre').length }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props pour la configuration
const props = defineProps({
  orders: {
    type: Array,
    default: () => []
  }
})

// Émettre les événements
const emit = defineEmits(['status-updated'])

// État des filtres
const filters = ref({
  status: {
    enCours: true,
    livre: true
  },
  date: 'all',
  searchAuthor: '',
  searchDish: ''
})

// Appliquer les filtres
const applyFilters = () => {
  // Les computed properties se mettront à jour automatiquement
}

// Commandes filtrées
const filteredOrders = computed(() => {
  return props.orders.filter(order => {
    // Filtre par statut
    const statusMatch = 
      (filters.value.status.enCours && order.status === 'en_cours') ||
      (filters.value.status.livre && order.status === 'livre') ||
      (!filters.value.status.enCours && !filters.value.status.livre && order.status !== 'en_cours' && order.status !== 'livre')

    if (!statusMatch) return false

    // Filtre par date
    if (filters.value.date !== 'all') {
      const orderDate = new Date(order.date)
      const today = new Date()
      
      switch (filters.value.date) {
        case 'today':
          if (orderDate.toDateString() !== today.toDateString()) return false
          break
        case 'yesterday':
          const yesterday = new Date(today)
          yesterday.setDate(yesterday.getDate() - 1)
          if (orderDate.toDateString() !== yesterday.toDateString()) return false
          break
        case 'week':
          const weekAgo = new Date(today)
          weekAgo.setDate(weekAgo.getDate() - 7)
          if (orderDate < weekAgo) return false
          break
        case 'month':
          const monthAgo = new Date(today)
          monthAgo.setMonth(monthAgo.getMonth() - 1)
          if (orderDate < monthAgo) return false
          break
      }
    }

    // Filtre par auteur
    if (filters.value.searchAuthor && 
        !order.authorName.toLowerCase().includes(filters.value.searchAuthor.toLowerCase())) {
      return false
    }

    // Filtre par plat
    if (filters.value.searchDish && 
        !order.dishName.toLowerCase().includes(filters.value.searchDish.toLowerCase())) {
      return false
    }

    return true
  })
})

// Calculer le montant d'une commande
const calculateAmount = (order) => {
  const price = parseFloat(order.unitPrice.replace(',', ''))
  return (price * order.quantity).toLocaleString('fr-FR')
}

// Calculer le montant total
const calculateTotalAmount = () => {
  const total = filteredOrders.value.reduce((sum, order) => {
    const price = parseFloat(order.unitPrice.replace(',', ''))
    return sum + (price * order.quantity)
  }, 0)
  return total.toLocaleString('fr-FR')
}

// Obtenir les commandes par statut
const getOrdersByStatus = (status) => {
  return filteredOrders.value.filter(order => order.status === status)
}

// Mettre à jour le statut d'une commande
const updateOrderStatus = (orderId, newStatus) => {
  emit('status-updated', { orderId, newStatus })
}

// Obtenir la classe CSS pour le statut
const getStatusClass = (status) => {
  return `status-${status}`
}

// Obtenir le texte du statut
const getStatusText = (status) => {
  const statusMap = {
    'en_cours': 'En cours',
    'livre': 'Livré',
    'annule': 'Annulé'
  }
  return statusMap[status] || status
}
</script>

<style scoped>
.orders-management {
  min-height: 100vh;
  background: var(--primary-color);
  position: relative;
}

/* Espace réservé pour la navigation bar */
.nav-bar-space {
  height: 90px; /* Ajustez cette valeur selon la hauteur de votre navbar */
  width: 100%;
}

.container {
  padding-top: 80;
}

/* Section des filtres */
.filters-section {
  background: var(--primary-color);
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.filter-group {
  margin-bottom: 0;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: var(--secondary-color);
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}
.form-select{
  background-color: var(--accent-color);
}

/* Filtres de statut */
.status-filters {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.status-checkbox {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background-color 0.4s;
  position: relative;
}

.status-checkbox:hover {
  background: var(--secondary-color);
  color: var(--primary-color) ;
}

.status-checkbox input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.checkmark {
  width: 18px;
  height: 18px;
  border: 2px solid #ddd;
  border-radius: 4px;
  margin-right: 0.75rem;
  position: relative;
  transition: all 0.2s;
}

.status-checkbox input:checked ~ .checkmark {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 5px;
  top: 2px;
  width: 4px;
  height: 8px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.status-checkbox input:checked ~ .checkmark:after {
  display: block;
}

.status-text {
  flex: 1;
  font-weight: 500;
}

.status-badge {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-badge.en-cours {
  background: var(--success-color);
}

.status-badge.livre {
  background: var(--secondary-color);
}

/* Champs de recherche */
.search-input-wrapper {
  position: relative;
}

.search-input {
  padding-left: 2.5rem;
  background-color: var(--accent-color);
  color: var(--fin-color);
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #7f8c8d;
}

/* Tableau */
.orders-table-section {
  background: var(--secondary-color);
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow: hidden;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
}

.orders-table th {
  background: var(--secondary-color);
  padding: 1rem;
  font-weight: 600;
  color: var(--primary-color);
  border-bottom: 2px solid var(--accent-color-);
  text-align: left;
}

.orders-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--accent-color);
  vertical-align: middle;
}

.order-row:hover {
  background: #f8f9fa;
}

/* Colonnes spécifiques */
.cell-plat {
  width: 25%;
}

.cell-prix {
  width: 15%;
}

.cell-quantite {
  width: 10%;
  text-align: center;
}

.cell-auteur {
  width: 20%;
}

.cell-statut {
  width: 15%;
}

.cell-montant {
  width: 15%;
  text-align: right;
  font-weight: 600;
}

/* Informations plat */
.dish-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.dish-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
}

.dish-details {
  display: flex;
  flex-direction: column;
}

.dish-name {
  font-weight: 600;
  color: #2c3e50;
}

.dish-description {
  font-size: 0.8rem;
  color: #7f8c8d;
  margin-top: 0.25rem;
}

/* Informations auteur */
.author-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.author-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
}

.author-name {
  font-weight: 500;
}

/* Badge de statut (lecture seule) */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-badge:before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.status-en_cours {
  background: #fff3cd;
  color: #856404;
}

.status-en_cours:before {
  background: #f39c12;
}

.status-livre {
  background: #d1edff;
  color: #0c5460;
}

.status-livre:before {
  background: #27ae60;
}

.status-annule {
  background: #f8d7da;
  color: #721c24;
}

.status-annule:before {
  background: #e74c3c;
}

/* Sélecteur de statut (modification) */
.status-select {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  width: 100%;
}

.status-select.status-en_cours {
  background: #fff3cd;
  color: #856404;
}

.status-select.status-livre {
  background: #d1edff;
  color: #0c5460;
}

.status-select.status-annule {
  background: #f8d7da;
  color: #721c24;
}

/* Prix et montants */
.price, .quantity, .amount {
  font-weight: 500;
}

.amount {
  color: var(--accent-color);
  font-size: 1.1rem;
}

/* Aucune commande */
.no-orders {
  text-align: center;
  padding: 3rem 2rem;
  color: #7f8c8d;
}

.no-orders-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-orders h3 {
  margin-bottom: 0.5rem;
  color: #2c3e50;
}

/* Résumé */
.orders-summary {
  display: flex;
  justify-content: space-between;
  padding: 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  flex-wrap: wrap;
  gap: 1rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.summary-label {
  font-weight: 500;
  color: #7f8c8d;
}

.summary-value {
  font-weight: 600;
  color: #2c3e50;
}

/* Responsive */
@media (max-width: 768px) {
  .nav-bar-space {
    height: 60px; /* Réduction pour mobile si nécessaire */
  }
  
  .filters-section .row {
    gap: 1rem;
  }
  
  .orders-table {
    font-size: 0.9rem;
  }
  
  .dish-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .author-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .orders-summary {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .orders-table th,
  .orders-table td {
    padding: 0.75rem 0.5rem;
  }
}

@media (max-width: 576px) {
  .status-filters {
    flex-direction: row;
    flex-wrap: wrap;
  }
}
</style>