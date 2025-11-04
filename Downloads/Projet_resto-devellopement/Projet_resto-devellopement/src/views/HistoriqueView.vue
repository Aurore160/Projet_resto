<template>
  <div class="payment-history-container">
    <!-- Espace réservé pour la navigation bar -->
    <div class="nav-bar-space"></div>
    
    <div class="payment-history">
      <h2>Historique des Paiements</h2>
      
      <!-- Filtres de date fixes -->
      <div class="filters">
        <div class="date-filter-buttons">
          <button 
            v-for="period in datePeriods" 
            :key="period.value"
            @click="setDatePeriod(period.value)"
            :class="{ active: selectedPeriod === period.value }"
            class="period-btn"
          >
            {{ period.label }}
          </button>
        </div>
        
        <!-- Affichage de la période sélectionnée -->
        <div v-if="selectedPeriod !== 'all'" class="selected-period">
          Période : {{ getPeriodLabel(selectedPeriod) }}
        </div>
      </div>

      <!-- Liste des factures -->
      <div class="invoices-list">
        <div 
          v-for="invoice in filteredInvoices" 
          :key="invoice.id" 
          class="invoice-card"
        >
          <div class="invoice-header">
            <h3>Facture #{{ invoice.id }}</h3>
            <span class="date">{{ formatDate(invoice.date) }}</span>
          </div>
          
          <div class="meals-list">
            <div 
              v-for="(meal, index) in invoice.meals" 
              :key="index" 
              class="meal-item"
            >
              <span class="meal-name">{{ meal.name }}</span>
              <span class="meal-quantity">x{{ meal.quantity }}</span>
              <span class="meal-price">{{ meal.price }}€</span>
              <span class="meal-subtotal">({{ meal.total }}€)</span>
            </div>
          </div>
          
          <div class="invoice-total">
            Total : <strong>{{ invoice.total }}€</strong>
          </div>
        </div>
      </div>

      <!-- Message si aucune facture -->
      <div v-if="filteredInvoices.length === 0" class="no-data">
        Aucune facture trouvée pour cette période.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Données réactives - à remplacer par vos données réelles
const invoices = ref([
  // Exemple de structure de données
  {
    id: 1,
    date: '2024-01-15T12:30:00',
    meals: [
      { name: 'Pizza Margherita', price: 12, quantity: 2, total: 24 },
      { name: 'Boisson', price: 3, quantity: 2, total: 6 }
    ],
    total: 30
  }
])

const selectedPeriod = ref('all')

// Périodes de date fixes
const datePeriods = ref([
  { label: 'Toutes', value: 'all' },
  { label: "Aujourd'hui", value: 'today' },
  { label: 'Hier', value: 'yesterday' },
  { label: 'Cette semaine', value: 'this_week' },
  { label: 'Semaine dernière', value: 'last_week' },
  { label: 'Ce mois', value: 'this_month' },
  { label: 'Mois dernier', value: 'last_month' },
  { label: 'Ces 7 derniers jours', value: 'last_7_days' },
  { label: 'Ces 30 derniers jours', value: 'last_30_days' }
])

// Calcul des dates pour chaque période
const getDateRange = (period) => {
  const now = new Date()
  const start = new Date()
  const end = new Date()

  switch (period) {
    case 'today':
      start.setHours(0, 0, 0, 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'yesterday':
      start.setDate(now.getDate() - 1)
      start.setHours(0, 0, 0, 0)
      end.setDate(now.getDate() - 1)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'this_week':
      start.setDate(now.getDate() - now.getDay())
      start.setHours(0, 0, 0, 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'last_week':
      start.setDate(now.getDate() - now.getDay() - 7)
      start.setHours(0, 0, 0, 0)
      end.setDate(now.getDate() - now.getDay() - 1)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'this_month':
      start.setDate(1)
      start.setHours(0, 0, 0, 0)
      end.setMonth(now.getMonth() + 1, 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'last_month':
      start.setMonth(now.getMonth() - 1, 1)
      start.setHours(0, 0, 0, 0)
      end.setMonth(now.getMonth(), 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'last_7_days':
      start.setDate(now.getDate() - 7)
      start.setHours(0, 0, 0, 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    case 'last_30_days':
      start.setDate(now.getDate() - 30)
      start.setHours(0, 0, 0, 0)
      end.setHours(23, 59, 59, 999)
      return { start, end }

    default:
      return null
  }
}

// Filtrage des factures
const filteredInvoices = computed(() => {
  let filtered = invoices.value

  if (selectedPeriod.value !== 'all') {
    const dateRange = getDateRange(selectedPeriod.value)
    if (dateRange) {
      filtered = filtered.filter(invoice => {
        const invoiceDate = new Date(invoice.date)
        return invoiceDate >= dateRange.start && invoiceDate <= dateRange.end
      })
    }
  }

  // Tri par date décroissante
  return filtered.sort((a, b) => new Date(b.date) - new Date(a.date))
})

// Définition de la période
const setDatePeriod = (period) => {
  selectedPeriod.value = period
}

// Formatage de la date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Libellé de la période sélectionnée
const getPeriodLabel = (period) => {
  const found = datePeriods.value.find(p => p.value === period)
  return found ? found.label : ''
}

// Exemple de méthode pour ajouter une facture (à utiliser depuis votre code)
const addInvoice = (invoiceData) => {
  invoices.value.push(invoiceData)
}

// Exemple de méthode pour charger des factures depuis une API
const loadInvoices = async (apiUrl) => {
  try {
    const response = await fetch(apiUrl)
    invoices.value = await response.json()
  } catch (error) {
    console.error('Erreur lors du chargement des factures:', error)
  }
}
</script>

<style scoped>
.payment-history-container {
  min-height: 100vh;
  background-color: var(--secondary-color);
}

.nav-bar-space {
  height: 50px; /* Ajustez cette valeur selon la hauteur de votre navigation bar */
}

.payment-history {
  color: var(--success-color);
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}
.payment-history h2{
  font-weight: bold;
}

.filters {
  margin-bottom: 30px;
  padding: 20px;
  background-color: var(--secondary-color);
  border-radius: 10px;
  border: var(--primary-color);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.date-filter-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 15px;
}

.period-btn {
  padding: 10px 16px;
  border: var(--primary-color);
  background-color: var(--secondary-color);
  color: var(--primary-color);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.period-btn:hover {
  background-color: var(--hover-color);
  color: var(--secondary-color);
  transform: translateY(-2px);
}

.period-btn.active {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.selected-period {
  font-weight: bold;
  color: var(--success-color);
  font-size: 0.9em;
}

.invoice-card {
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  background-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
}

.invoice-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--secondary-color);
}

.invoice-header h3 {
  margin: 0;
  color: var(--secondary-color);
}

.date {
  color: var(--fin-color);
  font-weight: 400;
}

.meal-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: var(--secondary-color);
}

.meal-name {
  flex-grow: 1;
  font-weight: 500;
  color: var(--secondary-color);
}

.meal-quantity, .meal-price, .meal-subtotal {
  margin-left: 15px;
  color: var(--secondary-color);
}

.meal-subtotal {
  font-weight: bold;
  color: #28a745;
}

.invoice-total {
  text-align: right;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 2px solid var(--secondary-color);
  font-size: 1.2em;
  font-weight: bold;
  color: #28a745;
}

.no-data {
  text-align: center;
  padding: 40px;
  color: #6c757d;
  font-style: italic;
  background-color: var(--primary-color);
  border-radius: 10px;
  border: 2px dashed #dee2e6;
}

@media (max-width: 768px) {
  .nav-bar-space {
    height: 60px; /* Réduction pour mobile si nécessaire */
  }
  
  .payment-history {
    padding: 15px;
  }
  
  .date-filter-buttons {
    justify-content: center;
  }
  
  .period-btn {
    flex: 1;
    min-width: 120px;
    text-align: center;
  }
  
  .invoice-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .meal-item {
    flex-wrap: wrap;
  }
}
</style>