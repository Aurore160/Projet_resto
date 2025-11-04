<template>
  <div class="admin-dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h3>Admin Dashboard</h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li>
            <button 
              @click="activeSection = 'add-meal'"
              :class="{ active: activeSection === 'add-meal' }"
            >
              <span class="icon">🍽️</span>
              Ajouter Repas
            </button>
          </li>
          <li>
            <button 
              @click="activeSection = 'list-meals'"
              :class="{ active: activeSection === 'list-meals' }"
            >
              <span class="icon">📋</span>
              Liste Repas
            </button>
          </li>
          <li>
            <button 
              @click="activeSection = 'add-promotion'"
              :class="{ active: activeSection === 'add-promotion' }"
            >
              <span class="icon">🏷️</span>
              Ajouter Promotion
            </button>
          </li>
          <li>
            <button 
              @click="activeSection = 'list-promotions'"
              :class="{ active: activeSection === 'list-promotions' }"
            >
              <span class="icon">📊</span>
              Liste Promotion
            </button>
          </li>
          <li>
            <button 
              @click="activeSection = 'add-event'"
              :class="{ active: activeSection === 'add-event' }"
            >
              <span class="icon">🎉</span>
              Ajouter Événement
            </button>
          </li>
          <li>
            <button 
              @click="activeSection = 'list-events'"
              :class="{ active: activeSection === 'list-events' }"
            >
              <span class="icon">📅</span>
              Liste Événement
            </button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Contenu Principal -->
    <div class="main-content">
      <!-- Ajouter Repas -->
      <div v-if="activeSection === 'add-meal'" class="section">
        <h2>Ajouter un Nouveau Repas</h2>
        <form @submit.prevent="addMeal" class="form">
          <div class="form-group">
            <label>Photo du repas</label>
            <div class="image-upload">
              <input 
                type="file" 
                @change="handleMealImageUpload"
                accept="image/*"
                ref="mealImageInput"
              >
              <div class="image-preview" v-if="newMeal.image">
                <img :src="newMeal.image" alt="Preview">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Nom du repas</label>
            <input 
              type="text" 
              v-model="newMeal.name"
              placeholder="Ex: Pizza Margherita"
              required
            >
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea 
              v-model="newMeal.description"
              placeholder="Description du repas..."
              rows="4"
              required
            ></textarea>
          </div>

          <div class="form-group">
            <label>Prix (FC)</label>
            <input 
              type="number" 
              v-model="newMeal.price"
              placeholder="5000"
              min="0"
              required
            >
          </div>

          <button type="submit" class="submit-btn">Ajouter le Repas</button>
        </form>
      </div>

      <!-- Liste Repas - Version Tableau -->
      <div v-if="activeSection === 'list-meals'" class="section">
        <div class="section-header">
          <h2>Liste des Repas</h2>
          <div class="search-box">
            <input 
              type="text" 
              v-model="mealSearch"
              placeholder="Rechercher un repas..."
            >
            <span class="search-icon">🔍</span>
          </div>
        </div>

        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="image-col">Image</th>
                <th class="name-col">Nom</th>
                <th class="description-col">Description</th>
                <th class="price-col">Prix</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="meal in filteredMeals" 
                :key="meal.id"
                class="table-row"
              >
                <td class="image-cell">
                  <img :src="meal.image" :alt="meal.name" class="table-image">
                </td>
                <td class="name-cell">
                  <strong>{{ meal.name }}</strong>
                </td>
                <td class="description-cell">
                  {{ meal.description }}
                </td>
                <td class="price-cell">
                  {{ meal.price }} FC
                </td>
                <td class="actions-cell">
                  <button @click="deleteMeal(meal.id)" class="delete-btn">
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredMeals.length === 0" class="no-items">
          <p>Aucun repas trouvé</p>
        </div>
      </div>

      <!-- Ajouter Promotion -->
      <div v-if="activeSection === 'add-promotion'" class="section">
        <h2>Ajouter une Promotion</h2>
        <form @submit.prevent="addPromotion" class="form">
          <div class="form-group">
            <label>Photo de la promotion</label>
            <div class="image-upload">
              <input 
                type="file" 
                @change="handlePromotionImageUpload"
                accept="image/*"
                ref="promotionImageInput"
              >
              <div class="image-preview" v-if="newPromotion.image">
                <img :src="newPromotion.image" alt="Preview">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Repas</label>
            <select v-model="newPromotion.mealId" @change="updatePromotionDetails" required>
              <option value="">Sélectionner un repas</option>
              <option 
                v-for="meal in meals" 
                :key="meal.id" 
                :value="meal.id"
              >
                {{ meal.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea 
              v-model="newPromotion.description"
              placeholder="Description de la promotion..."
              rows="4"
              required
            ></textarea>
          </div>

          <div class="form-group">
            <label>Prix promotionnel (FC)</label>
            <input 
              type="number" 
              v-model="newPromotion.price"
              placeholder="4000"
              min="0"
              required
            >
          </div>

          <div class="price-comparison" v-if="selectedMealForPromotion">
            <p>Prix original: <span class="original-price">{{ selectedMealForPromotion.price }} FC</span></p>
            <p>Économie: <span class="saving">{{ calculateSaving() }} FC</span></p>
          </div>

          <button type="submit" class="submit-btn">Ajouter la Promotion</button>
        </form>
      </div>

      <!-- Liste Promotions - Version Tableau -->
      <div v-if="activeSection === 'list-promotions'" class="section">
        <div class="section-header">
          <h2>Liste des Promotions</h2>
          <div class="search-box">
            <input 
              type="text" 
              v-model="promotionSearch"
              placeholder="Rechercher une promotion..."
            >
            <span class="search-icon">🔍</span>
          </div>
        </div>

        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="image-col">Image</th>
                <th class="name-col">Repas</th>
                <th class="description-col">Description</th>
                <th class="price-col">Prix Original</th>
                <th class="price-col">Prix Promo</th>
                <th class="saving-col">Économie</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="promotion in filteredPromotions" 
                :key="promotion.id"
                class="table-row promotion-row"
              >
                <td class="image-cell">
                  <img :src="promotion.image" :alt="promotion.mealName" class="table-image">
                </td>
                <td class="name-cell">
                  <strong>{{ promotion.mealName }}</strong>
                  <span class="promotion-badge">PROMO</span>
                </td>
                <td class="description-cell">
                  {{ promotion.description }}
                </td>
                <td class="price-cell original-price">
                  {{ promotion.originalPrice }} FC
                </td>
                <td class="price-cell promotion-price">
                  {{ promotion.price }} FC
                </td>
                <td class="saving-cell">
                  <span class="saving-amount">
                    {{ promotion.originalPrice - promotion.price }} FC
                  </span>
                </td>
                <td class="actions-cell">
                  <button @click="deletePromotion(promotion.id)" class="delete-btn">
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredPromotions.length === 0" class="no-items">
          <p>Aucune promotion trouvée</p>
        </div>
      </div>

      <!-- Ajouter Événement -->
      <div v-if="activeSection === 'add-event'" class="section">
        <h2>Ajouter un Événement</h2>
        <form @submit.prevent="addEvent" class="form">
          <div class="form-group">
            <label>Photo de l'événement</label>
            <div class="image-upload">
              <input 
                type="file" 
                @change="handleEventImageUpload"
                accept="image/*"
                ref="eventImageInput"
              >
              <div class="image-preview" v-if="newEvent.image">
                <img :src="newEvent.image" alt="Preview">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Nom de l'événement</label>
            <input 
              type="text" 
              v-model="newEvent.name"
              placeholder="Ex: Soirée Spéciale"
              required
            >
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea 
              v-model="newEvent.description"
              placeholder="Description de l'événement..."
              rows="4"
              required
            ></textarea>
          </div>

          <button type="submit" class="submit-btn">Ajouter l'Événement</button>
        </form>
      </div>

      <!-- Liste Événements - Version Tableau -->
      <div v-if="activeSection === 'list-events'" class="section">
        <div class="section-header">
          <h2>Liste des Événements</h2>
          <div class="search-box">
            <input 
              type="text" 
              v-model="eventSearch"
              placeholder="Rechercher un événement..."
            >
            <span class="search-icon">🔍</span>
          </div>
        </div>

        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="image-col">Image</th>
                <th class="name-col">Nom</th>
                <th class="description-col">Description</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="event in filteredEvents" 
                :key="event.id"
                class="table-row event-row"
              >
                <td class="image-cell">
                  <img :src="event.image" :alt="event.name" class="table-image">
                </td>
                <td class="name-cell">
                  <strong>{{ event.name }}</strong>
                  <span class="event-badge">ÉVÉNEMENT</span>
                </td>
                <td class="description-cell">
                  {{ event.description }}
                </td>
                <td class="actions-cell">
                  <button @click="deleteEvent(event.id)" class="delete-btn">
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredEvents.length === 0" class="no-items">
          <p>Aucun événement trouvé</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// État pour la section active
const activeSection = ref('add-meal')

// Recherches
const mealSearch = ref('')
const promotionSearch = ref('')
const eventSearch = ref('')

// Données des repas
const meals = ref([
  {
    id: 1,
    name: "Pizza Margherita",
    description: "Pizza classique avec mozzarella et basilic",
    price: 12000,
    image: "https://images.unsplash.com/photo-1604068549290-dea0e4a305ca?w=300&h=200&fit=crop"
  },
  {
    id: 2,
    name: "Burger Maison",
    description: "Burger avec viande hachée et légumes frais",
    price: 8000,
    image: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&h=200&fit=crop"
  }
])

// Données des promotions
const promotions = ref([
  {
    id: 1,
    mealId: 1,
    mealName: "Pizza Margherita",
    description: "Promotion spéciale sur la pizza classique",
    originalPrice: 12000,
    price: 9000,
    image: "https://images.unsplash.com/photo-1604068549290-dea0e4a305ca?w=300&h=200&fit=crop"
  }
])

// Données des événements
const events = ref([
  {
    id: 1,
    name: "Soirée Italienne",
    description: "Découvrez nos spécialités italiennes avec musique live",
    image: "https://images.unsplash.com/photo-1533777324565-a040eb52facd?w=300&h=200&fit=crop"
  }
])

// Nouveaux éléments
const newMeal = ref({
  name: '',
  description: '',
  price: 0,
  image: ''
})

const newPromotion = ref({
  mealId: '',
  description: '',
  price: 0,
  image: ''
})

const newEvent = ref({
  name: '',
  description: '',
  image: ''
})

// Références pour les inputs files
const mealImageInput = ref(null)
const promotionImageInput = ref(null)
const eventImageInput = ref(null)

// Computed properties pour les recherches
const filteredMeals = computed(() => {
  if (!mealSearch.value) return meals.value
  return meals.value.filter(meal => 
    meal.name.toLowerCase().includes(mealSearch.value.toLowerCase()) ||
    meal.description.toLowerCase().includes(mealSearch.value.toLowerCase())
  )
})

const filteredPromotions = computed(() => {
  if (!promotionSearch.value) return promotions.value
  return promotions.value.filter(promotion => 
    promotion.mealName.toLowerCase().includes(promotionSearch.value.toLowerCase()) ||
    promotion.description.toLowerCase().includes(promotionSearch.value.toLowerCase())
  )
})

const filteredEvents = computed(() => {
  if (!eventSearch.value) return events.value
  return events.value.filter(event => 
    event.name.toLowerCase().includes(eventSearch.value.toLowerCase()) ||
    event.description.toLowerCase().includes(eventSearch.value.toLowerCase())
  )
})

// Meal sélectionné pour la promotion
const selectedMealForPromotion = computed(() => {
  return meals.value.find(meal => meal.id === parseInt(newPromotion.value.mealId))
})

// Méthodes
const handleMealImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    newMeal.value.image = URL.createObjectURL(file)
  }
}

const handlePromotionImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    newPromotion.value.image = URL.createObjectURL(file)
  }
}

const handleEventImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    newEvent.value.image = URL.createObjectURL(file)
  }
}

const addMeal = () => {
  const meal = {
    id: Date.now(),
    ...newMeal.value
  }
  meals.value.push(meal)
  resetMealForm()
  alert('Repas ajouté avec succès!')
}

const addPromotion = () => {
  const selectedMeal = selectedMealForPromotion.value
  const promotion = {
    id: Date.now(),
    mealId: newPromotion.value.mealId,
    mealName: selectedMeal.name,
    originalPrice: selectedMeal.price,
    description: newPromotion.value.description,
    price: newPromotion.value.price,
    image: newPromotion.value.image || selectedMeal.image
  }
  promotions.value.push(promotion)
  resetPromotionForm()
  alert('Promotion ajoutée avec succès!')
}

const addEvent = () => {
  const event = {
    id: Date.now(),
    ...newEvent.value
  }
  events.value.push(event)
  resetEventForm()
  alert('Événement ajouté avec succès!')
}

const deleteMeal = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce repas?')) {
    meals.value = meals.value.filter(meal => meal.id !== id)
    promotions.value = promotions.value.filter(promo => promo.mealId !== id)
  }
}

const deletePromotion = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette promotion?')) {
    promotions.value = promotions.value.filter(promo => promo.id !== id)
  }
}

const deleteEvent = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet événement?')) {
    events.value = events.value.filter(event => event.id !== id)
  }
}

const updatePromotionDetails = () => {
  if (selectedMealForPromotion.value) {
    newPromotion.value.description = `Promotion spéciale sur ${selectedMealForPromotion.value.name}`
    newPromotion.value.image = selectedMealForPromotion.value.image
  }
}

const calculateSaving = () => {
  if (selectedMealForPromotion.value) {
    return selectedMealForPromotion.value.price - newPromotion.value.price
  }
  return 0
}

const resetMealForm = () => {
  newMeal.value = {
    name: '',
    description: '',
    price: 0,
    image: ''
  }
  if (mealImageInput.value) {
    mealImageInput.value.value = ''
  }
}

const resetPromotionForm = () => {
  newPromotion.value = {
    mealId: '',
    description: '',
    price: 0,
    image: ''
  }
  if (promotiorImageInput.value) {
    promotionImageInput.value.value = ''
  }
}

const resetEventForm = () => {
  newEvent.value = {
    name: '',
    description: '',
    image: ''
  }
  if (eventImageInput.value) {
    eventImageInput.value.value = ''
  }
}

onMounted(() => {
  // Charger les données existantes si nécessaire
})
</script>

<style scoped>
.admin-dashboard {
  display: flex;
  min-height: 120vh;
  background: var(--primary-color);
}

/* Sidebar avec padding supérieur */
.sidebar {
  width: 280px;
  background: var(--secondary-color);
  color: var(--primary-color);
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  overflow-y: auto;
  padding-top: 80px; /* Nouveau padding supérieur pour la navbar */
}

.sidebar-header {
  padding: 0 1.5rem 2rem 1.5rem; /* Ajustement du padding */
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;
  background: var(--secondary-color);
  z-index: 100;
  height: 80px; /* Hauteur fixe pour la navbar */
  display: flex;
  align-items: center;
}

.sidebar-header h3 {
  margin: 0;
  color: white;
}

.sidebar-nav {
  margin-top: 0; /* Supprimer la marge supérieure puisque le header est fixe */
}

.sidebar-nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-nav li {
  margin: 0;
}

.sidebar-nav button {
  width: 100%;
  background: none;
  border: none;
  color: var(--primary-color);
  padding: 1rem 1.5rem;
  text-align: left;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1rem;
}

.sidebar-nav button:hover {
  background: var(--hover-color);
  color: var(--secondary-color);
}

.sidebar-nav button.active {
  background: var(--primary-color);
  color: var(--secondary-color);
}

.icon {
  font-size: 1.2rem;
}

/* Main Content */
.main-content {
  flex: 1;
  margin-left: 280px;
  padding: 2rem;
}

.section {
  background: var(--primary-color);
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section h2 {
  margin-bottom: 2rem;
  color: var(--secondary-color);
  border-bottom: 2px solid var(--secondary-color);
  padding-bottom: 0.5rem;
}

/* Forms */
.form {
  max-width: 600px;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--secondary-color);
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--primary-color);
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
  
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--secondary-color);
}

.image-upload {
  margin-top: 0.5rem;
}

.image-upload input {
  margin-bottom: 1rem;
}

.image-preview {
  width: 200px;
  height: 150px;
  border: 2px dashed #ddd;
  border-radius: 5px;
  overflow: hidden;
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.submit-btn {
  background: var(--hover-color);
  color: var(--secondary-color);
  border: none;
  padding: 1rem 2rem;
  border-radius: 5px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s ease;
}

.submit-btn:hover {
  background: var(--secondary-color);
  color: var(--primary-color);
}

/* Section Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.search-box {
  position: relative;
  min-width: 300px;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 25px;
}

.search-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #7f8c8d;
}

/* Styles pour les tableaux */
.table-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.data-table th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #e0e0e0;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #e0e0e0;
  vertical-align: middle;
}

.table-row:hover {
  background: #f8f9fa;
}

.promotion-row {
  border-left: 4px solid #e74c3c;
}

.event-row {
  border-left: 4px solid #3498db;
}

/* Colonnes spécifiques */
.image-col {
  width: 80px;
}

.name-col {
  width: 200px;
}

.description-col {
  min-width: 300px;
}

.price-col {
  width: 120px;
}

.saving-col {
  width: 100px;
}

.actions-col {
  width: 120px;
}

/* Cellules spécifiques */
.image-cell {
  text-align: center;
}

.table-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
}

.name-cell {
  position: relative;
}

.name-cell strong {
  display: block;
  margin-bottom: 0.25rem;
}

.promotion-badge,
.event-badge {
  background: #e74c3c;
  color: white;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: bold;
}

.event-badge {
  background: #3498db;
}

.description-cell {
  color: #666;
  line-height: 1.4;
}

.price-cell {
  font-weight: 500;
}

.original-price {
  text-decoration: line-through;
  color: #7f8c8d;
}

.promotion-price {
  color: #e74c3c;
  font-weight: bold;
  font-size: 1.1rem;
}

.saving-cell .saving-amount {
  color: #27ae60;
  font-weight: bold;
}

.actions-cell {
  text-align: center;
}

.delete-btn {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s ease;
  font-size: 0.9rem;
}

.delete-btn:hover {
  background: #c0392b;
}

.no-items {
  text-align: center;
  padding: 3rem;
  color: #7f8c8d;
  background: #f8f9fa;
  border-radius: 8px;
  margin-top: 1rem;
}

.price-comparison {
  margin-top: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 5px;
}

.price-comparison p {
  margin: 0.25rem 0;
}

.original-price {
  text-decoration: line-through;
  color: #7f8c8d;
}

.saving {
  color: #27ae60;
  font-weight: bold;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 100%;
    height: auto;
    position: relative;
    padding-top: 0;
  }

  .sidebar-header {
    position: relative;
    width: 100%;
    height: auto;
  }

  .main-content {
    margin-left: 0;
  }

  .section-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    min-width: auto;
  }

  .table-container {
    font-size: 0.9rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }

  .image-col {
    width: 60px;
  }

  .table-image {
    width: 50px;
    height: 50px;
  }
}

@media (max-width: 480px) {
  .main-content {
    padding: 1rem;
  }

  .section {
    padding: 1rem;
  }

  .data-table {
    font-size: 0.8rem;
  }

  .delete-btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
}
</style>