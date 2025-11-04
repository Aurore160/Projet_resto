<template>
  <div class="menu-view">
    <div class="view-header">
      <h2>Gestion du Menu et Promotions</h2>
      <p>Créez et gérez votre menu ainsi que vos promotions</p>
    </div>

    <!-- Navigation entre les sections -->
    <div class="section-tabs">
      <button 
        @click="activeSection = 'menu'"
        :class="['tab-button', { active: activeSection === 'menu' }]"
      >
        Gestion du Menu
      </button>
      <button 
        @click="activeSection = 'categories'"
        :class="['tab-button', { active: activeSection === 'categories' }]"
      >
        Catégories
      </button>
      <button 
        @click="activeSection = 'promotions'"
        :class="['tab-button', { active: activeSection === 'promotions' }]"
      >
        Gestion des Promotions
      </button>
    </div>

    <!-- Section Gestion du Menu -->
    <div v-if="activeSection === 'menu'" class="section-content">
      <div class="section-header">
        <h3>Gestion des Éléments du Menu</h3>
        <button @click="showAddItemModal = true" class="btn-primary">
          Ajouter un Plat
        </button>
      </div>

      <!-- Filtres et recherche -->
      <div class="filters">
        <div class="search-box">
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Rechercher un plat..."
            class="search-input"
          >
        </div>
        <select v-model="categoryFilter" class="filter-select">
          <option value="">Toutes les catégories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        <select v-model="availabilityFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="available">Disponible</option>
          <option value="unavailable">Indisponible</option>
        </select>
      </div>

      <!-- Liste des éléments du menu -->
      <div class="menu-items-grid">
        <div 
          v-for="item in filteredMenuItems" 
          :key="item.id"
          class="menu-item-card"
        >
          <div class="item-image">
            <img :src="item.image" :alt="item.name" v-if="item.image">
            <div v-else class="image-placeholder">Image</div>
          </div>
          <div class="item-details">
            <div class="item-header">
              <h4>{{ item.name }}</h4>
              <span :class="['availability-badge', item.available ? 'available' : 'unavailable']">
                {{ item.available ? 'Disponible' : 'Indisponible' }}
              </span>
            </div>
            <p class="item-description">{{ item.description }}</p>
            <div class="item-meta">
              <div class="price-section">
                <span class="price">{{ formatPrice(item.price) }}</span>
                <span v-if="item.isDailySpecial" class="daily-special">Plat du jour</span>
              </div>
              <span class="category">{{ getCategoryName(item.categoryId) }}</span>
            </div>
            <div class="item-additional-info">
              <span class="prep-time">Préparation: {{ item.preparationTime }} min</span>
              <span v-if="item.ingredients" class="ingredients-count">
                {{ item.ingredients.length }} ingrédients
              </span>
            </div>
            <div class="item-actions">
              <button @click="editItem(item)" class="btn-edit">Modifier</button>
              <button @click="toggleAvailability(item.id)" class="btn-status">
                {{ item.available ? 'Désactiver' : 'Activer' }}
              </button>
              <button @click="deleteItem(item.id)" class="btn-delete">Supprimer</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Message si aucun élément -->
      <div v-if="filteredMenuItems.length === 0" class="empty-state">
        <p>Aucun plat trouvé dans le menu</p>
      </div>
    </div>

    <!-- Section Catégories -->
    <div v-if="activeSection === 'categories'" class="section-content">
      <div class="section-header">
        <h3>Gestion des Catégories</h3>
        <button @click="showAddCategoryModal = true" class="btn-primary">
          Ajouter une Catégorie
        </button>
      </div>

      <div class="categories-list">
        <div 
          v-for="category in categories" 
          :key="category.id"
          class="category-card"
        >
          <div class="category-info">
            <h4>{{ category.name }}</h4>
            <p class="category-description">{{ category.description }}</p>
            <span class="display-order">Ordre: {{ category.displayOrder }}</span>
          </div>
          <div class="category-actions">
            <button @click="editCategory(category)" class="btn-edit">Modifier</button>
            <button @click="deleteCategory(category.id)" class="btn-delete">Supprimer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Section Gestion des Promotions -->
    <div v-if="activeSection === 'promotions'" class="section-content">
      <div class="section-header">
        <h3>Gestion des Promotions</h3>
        <div class="promotion-actions-header">
          <button @click="showCreatePromotionModal = true" class="btn-primary">
            Créer Promotion
          </button>
          <button @click="showPublishPromotionModal = true" class="btn-secondary">
            Publier Promotion
          </button>
        </div>
      </div>

      <!-- Liste des promotions -->
      <div class="promotions-grid">
        <div 
          v-for="promotion in promotions" 
          :key="promotion.id"
          class="promotion-card"
        >
          <div class="promotion-image">
            <img :src="promotion.image" :alt="promotion.title" v-if="promotion.image">
            <div v-else class="image-placeholder">Image promo</div>
          </div>
          <div class="promotion-details">
            <div class="promotion-header">
              <h4>{{ promotion.title }}</h4>
              <span :class="['status', promotion.status]">
                {{ getPromotionStatusText(promotion.status) }}
              </span>
            </div>
            <p class="promotion-type">{{ getPromotionTypeText(promotion.type) }}</p>
            <p class="promotion-description">{{ promotion.description }}</p>
            
            <div class="promotion-meta">
              <div class="promo-code">
                <strong>Code: </strong>{{ promotion.promoCode }}
              </div>
              <div class="promo-dates">
                Du {{ formatDate(promotion.startDate) }} au {{ formatDate(promotion.endDate) }}
              </div>
            </div>

            <div class="promotion-stats">
              <span>Utilisation: {{ promotion.currentUsage }}/{{ promotion.maxUsage }}</span>
              <span v-if="promotion.minCartValue">Panier min: {{ formatPrice(promotion.minCartValue) }}</span>
            </div>

            <div class="promotion-actions">
              <button @click="editPromotion(promotion)" class="btn-edit">Modifier</button>
              <button @click="togglePromotionStatus(promotion.id)" class="btn-status">
                {{ promotion.status === 'active' ? 'Désactiver' : 'Activer' }}
              </button>
              <button @click="deletePromotion(promotion.id)" class="btn-delete">Supprimer</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des promotions publiées -->
      <div v-if="publishedPromotions.length > 0" class="published-promotions">
        <h4>Promotions Publiées</h4>
        <div class="published-promotions-grid">
          <div 
            v-for="publishedPromo in publishedPromotions" 
            :key="publishedPromo.id"
            class="published-promo-card"
          >
            <div class="published-promo-header">
              <h5>{{ publishedPromo.promotionTitle }}</h5>
              <span class="promo-price">{{ formatPrice(publishedPromo.promotionalPrice) }}</span>
            </div>
            <p class="published-promo-item">{{ publishedPromo.menuItemName }}</p>
            <div class="published-promo-dates">
              Du {{ formatDate(publishedPromo.startDate) }} au {{ formatDate(publishedPromo.endDate) }}
            </div>
            <div class="published-promo-actions">
              <button @click="unpublishPromotion(publishedPromo.id)" class="btn-delete">Retirer</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Message si aucune promotion -->
      <div v-if="promotions.length === 0 && publishedPromotions.length === 0" class="empty-state">
        <p>Aucune promotion créée pour le moment</p>
      </div>
    </div>

    <!-- Modal pour ajouter/modifier un plat -->
    <div v-if="showAddItemModal" class="modal-overlay">
      <div class="modal large-modal">
        <div class="modal-header">
          <h3>{{ editingItem ? 'Modifier' : 'Ajouter' }} un Plat</h3>
          <button @click="closeModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveItem">
            <div class="form-row">
              <div class="form-group">
                <label>Catégorie *</label>
                <select v-model="itemForm.categoryId" required>
                  <option value="">Sélectionner une catégorie</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Nom du plat *</label>
                <input v-model="itemForm.name" type="text" required>
              </div>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea v-model="itemForm.description" rows="3" placeholder="Description du plat..."></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Prix (FC) *</label>
                <input v-model="itemForm.price" type="number" step="0.01" min="0" required>
              </div>
              <div class="form-group">
                <label>Temps de préparation (min)</label>
                <input v-model="itemForm.preparationTime" type="number" min="0">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="itemForm.available">
                  Disponible
                </label>
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="itemForm.isDailySpecial">
                  Plat du jour
                </label>
              </div>
            </div>

            <div class="form-group">
              <label>Ingrédients (séparés par des virgules)</label>
              <textarea v-model="itemForm.ingredientsText" rows="2" placeholder="Tomate, fromage, basilic..."></textarea>
            </div>

            <div class="form-group">
              <label>Image du plat</label>
              <input type="file" @change="handleImageUpload" accept="image/*">
              <div v-if="itemForm.image" class="image-preview">
                <img :src="itemForm.image" alt="Preview">
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">
                {{ editingItem ? 'Modifier' : 'Ajouter' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal pour créer une promotion -->
    <div v-if="showCreatePromotionModal" class="modal-overlay">
      <div class="modal large-modal">
        <div class="modal-header">
          <h3>{{ editingPromotion ? 'Modifier' : 'Créer' }} une Promotion</h3>
          <button @click="closePromotionModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePromotion">
            <div class="form-row">
              <div class="form-group">
                <label>Titre de la promotion *</label>
                <input v-model="promotionForm.title" type="text" required>
              </div>
              <div class="form-group">
                <label>Type de promotion *</label>
                <select v-model="promotionForm.type" required>
                  <option value="">Sélectionner un type</option>
                  <option v-for="type in promotionTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Description *</label>
              <textarea v-model="promotionForm.description" rows="3" required></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Date de début *</label>
                <input v-model="promotionForm.startDate" type="date" required>
              </div>
              <div class="form-group">
                <label>Date de fin *</label>
                <input v-model="promotionForm.endDate" type="date" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Utilisation maximum</label>
                <input v-model="promotionForm.maxUsage" type="number" min="1">
              </div>
              <div class="form-group">
                <label>Valeur minimale du panier (FC)</label>
                <input v-model="promotionForm.minCartValue" type="number" step="0.01" min="0">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Code promotionnel *</label>
                <input v-model="promotionForm.promoCode" type="text" required>
              </div>
              <div class="form-group">
                <label>Conditions (points requis)</label>
                <input v-model="promotionForm.requiredPoints" type="number" min="0">
              </div>
            </div>

            <div class="form-group">
              <label>Détails supplémentaires</label>
              <textarea v-model="promotionForm.details" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Image de la promotion</label>
              <input type="file" @change="handlePromotionImageUpload" accept="image/*">
            </div>

            <div class="modal-actions">
              <button type="button" @click="closePromotionModal" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">
                {{ editingPromotion ? 'Modifier' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal pour publier une promotion -->
    <div v-if="showPublishPromotionModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>Publier une Promotion</h3>
          <button @click="closePublishModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="publishPromotion">
            <div class="form-group">
              <label>Sélectionner une promotion *</label>
              <select v-model="publishForm.promotionId" required>
                <option value="">Choisir une promotion</option>
                <option 
                  v-for="promo in unpublishedPromotions" 
                  :key="promo.id" 
                  :value="promo.id"
                >
                  {{ promo.title }} ({{ promo.promoCode }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Sélectionner un plat *</label>
              <select v-model="publishForm.menuItemId" required>
                <option value="">Choisir un plat</option>
                <option 
                  v-for="item in availableMenuItems" 
                  :key="item.id" 
                  :value="item.id"
                >
                  {{ item.name }} - {{ formatPrice(item.price) }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Prix promotionnel (FC) *</label>
              <input v-model="publishForm.promotionalPrice" type="number" step="0.01" min="0" required>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closePublishModal" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">Publier</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal pour gérer les catégories -->
    <div v-if="showAddCategoryModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editingCategory ? 'Modifier' : 'Ajouter' }} une Catégorie</h3>
          <button @click="closeCategoryModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCategory">
            <div class="form-group">
              <label>Nom de la catégorie *</label>
              <input v-model="categoryForm.name" type="text" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea v-model="categoryForm.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Ordre d'affichage</label>
              <input v-model="categoryForm.displayOrder" type="number" min="1">
            </div>
            <div class="modal-actions">
              <button type="button" @click="closeCategoryModal" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">
                {{ editingCategory ? 'Modifier' : 'Ajouter' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'

// États pour la navigation
const activeSection = ref('menu')

// Données des catégories
const categories = ref([
  { id: 1, name: 'Entrées', description: 'Plats pour commencer le repas', displayOrder: 1 },
  { id: 2, name: 'Plats Principaux', description: 'Plats principaux', displayOrder: 2 },
  { id: 3, name: 'Desserts', description: 'Douceurs pour finir le repas', displayOrder: 3 },
  { id: 4, name: 'Boissons', description: 'Boissons fraîches et chaudes', displayOrder: 4 },
  { id: 5, name: 'Pizzas', description: 'Pizzas variées', displayOrder: 5 }
])

// Données du menu
const menuItems = ref([
  {
    id: 1,
    name: 'Pizza Margherita',
    description: 'Tomate, mozzarella, basilic frais',
    price: 15000,
    categoryId: 5,
    available: true,
    isDailySpecial: false,
    preparationTime: 20,
    ingredients: ['Tomate', 'Mozzarella', 'Basilic', 'Huile d\'olive'],
    image: ''
  },
  {
    id: 2,
    name: 'Burger Classique',
    description: 'Steak haché, salade, tomate, oignon',
    price: 12000,
    categoryId: 2,
    available: true,
    isDailySpecial: true,
    preparationTime: 15,
    ingredients: ['Steak haché', 'Pain burger', 'Salade', 'Tomate', 'Oignon'],
    image: ''
  },
  {
    id: 3,
    name: 'Tiramisu',
    description: 'Dessert italien au café et mascarpone',
    price: 8000,
    categoryId: 3,
    available: true,
    isDailySpecial: false,
    preparationTime: 0,
    ingredients: ['Mascarpone', 'Café', 'Biscuits', 'Cacao'],
    image: ''
  }
])

// Données des promotions
const promotions = ref([
  {
    id: 1,
    title: 'Offre Spéciale Été',
    description: 'Profitez de nos rafraîchissements avec une réduction spéciale',
    type: 'percentage',
    startDate: '2024-06-01',
    endDate: '2024-08-31',
    maxUsage: 100,
    currentUsage: 25,
    minCartValue: 20000,
    promoCode: 'ETE2024',
    requiredPoints: 0,
    details: 'Valable sur toutes les boissons',
    status: 'active',
    image: ''
  }
])

// Promotions publiées
const publishedPromotions = ref([
  {
    id: 1,
    promotionId: 1,
    promotionTitle: 'Offre Spéciale Été',
    menuItemId: 3,
    menuItemName: 'Tiramisu',
    promotionalPrice: 6000,
    startDate: '2024-06-01',
    endDate: '2024-08-31'
  }
])

// Types de promotions
const promotionTypes = ref([
  { value: 'percentage', label: 'Pourcentage de réduction' },
  { value: 'fixed', label: 'Montant fixe' },
  { value: 'buy_one_get_one', label: 'Achetez un, obtenez-en un gratuit' },
  { value: 'free_delivery', label: 'Livraison gratuite' },
  { value: 'gift', label: 'Cadeau offert' }
])

// États pour les filtres
const searchQuery = ref('')
const categoryFilter = ref('')
const availabilityFilter = ref('')

// États pour les modals
const showAddItemModal = ref(false)
const showCreatePromotionModal = ref(false)
const showPublishPromotionModal = ref(false)
const showAddCategoryModal = ref(false)
const editingItem = ref(null)
const editingPromotion = ref(null)
const editingCategory = ref(null)

// Formulaires
const itemForm = reactive({
  name: '',
  description: '',
  price: '',
  categoryId: '',
  available: true,
  isDailySpecial: false,
  preparationTime: '',
  ingredientsText: '',
  image: ''
})

const promotionForm = reactive({
  title: '',
  description: '',
  type: '',
  startDate: '',
  endDate: '',
  maxUsage: '',
  minCartValue: '',
  promoCode: '',
  requiredPoints: '',
  details: '',
  image: '',
  status: 'draft'
})

const publishForm = reactive({
  promotionId: '',
  menuItemId: '',
  promotionalPrice: ''
})

const categoryForm = reactive({
  name: '',
  description: '',
  displayOrder: ''
})

// Computed properties
const filteredMenuItems = computed(() => {
  return menuItems.value.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         item.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesCategory = !categoryFilter.value || item.categoryId.toString() === categoryFilter.value
    const matchesAvailability = !availabilityFilter.value || 
                              (availabilityFilter.value === 'available' && item.available) ||
                              (availabilityFilter.value === 'unavailable' && !item.available)
    return matchesSearch && matchesCategory && matchesAvailability
  })
})

const unpublishedPromotions = computed(() => {
  return promotions.value.filter(promo => !publishedPromotions.value.find(p => p.promotionId === promo.id))
})

const availableMenuItems = computed(() => {
  return menuItems.value.filter(item => item.available)
})

// Méthodes utilitaires
const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price) + ' FC'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const getCategoryName = (categoryId) => {
  const category = categories.value.find(c => c.id === categoryId)
  return category ? category.name : 'Non catégorisé'
}

const getPromotionTypeText = (type) => {
  const typeObj = promotionTypes.value.find(t => t.value === type)
  return typeObj ? typeObj.label : type
}

const getPromotionStatusText = (status) => {
  const statusMap = {
    draft: 'Brouillon',
    active: 'Active',
    inactive: 'Inactive',
    expired: 'Expirée'
  }
  return statusMap[status] || status
}

// Méthodes pour la gestion du menu
const editItem = (item) => {
  editingItem.value = item
  Object.assign(itemForm, {
    ...item,
    ingredientsText: item.ingredients ? item.ingredients.join(', ') : ''
  })
  showAddItemModal.value = true
}

const deleteItem = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce plat ?')) {
    menuItems.value = menuItems.value.filter(item => item.id !== id)
    // Supprimer aussi les promotions publiées associées
    publishedPromotions.value = publishedPromotions.value.filter(promo => promo.menuItemId !== id)
  }
}

const toggleAvailability = (id) => {
  const item = menuItems.value.find(item => item.id === id)
  if (item) {
    item.available = !item.available
  }
}

const saveItem = () => {
  const itemData = {
    ...itemForm,
    ingredients: itemForm.ingredientsText ? itemForm.ingredientsText.split(',').map(i => i.trim()).filter(i => i) : [],
    price: parseFloat(itemForm.price),
    preparationTime: parseInt(itemForm.preparationTime) || 0
  }

  if (editingItem.value) {
    // Modification
    const index = menuItems.value.findIndex(item => item.id === editingItem.value.id)
    menuItems.value[index] = { ...itemData, id: editingItem.value.id }
  } else {
    // Ajout
    const newItem = {
      ...itemData,
      id: Math.max(...menuItems.value.map(i => i.id)) + 1
    }
    menuItems.value.push(newItem)
  }
  closeModal()
}

// Méthodes pour la gestion des promotions
const editPromotion = (promotion) => {
  editingPromotion.value = promotion
  Object.assign(promotionForm, promotion)
  showCreatePromotionModal.value = true
}

const deletePromotion = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette promotion ?')) {
    promotions.value = promotions.value.filter(promo => promo.id !== id)
    publishedPromotions.value = publishedPromotions.value.filter(promo => promo.promotionId !== id)
  }
}

const togglePromotionStatus = (id) => {
  const promotion = promotions.value.find(promo => promo.id === id)
  if (promotion) {
    promotion.status = promotion.status === 'active' ? 'inactive' : 'active'
  }
}

const savePromotion = () => {
  const promotionData = {
    ...promotionForm,
    maxUsage: parseInt(promotionForm.maxUsage) || 0,
    minCartValue: parseFloat(promotionForm.minCartValue) || 0,
    requiredPoints: parseInt(promotionForm.requiredPoints) || 0,
    currentUsage: 0
  }

  if (editingPromotion.value) {
    // Modification
    const index = promotions.value.findIndex(promo => promo.id === editingPromotion.value.id)
    promotions.value[index] = { ...promotionData, id: editingPromotion.value.id }
  } else {
    // Ajout
    const newPromotion = {
      ...promotionData,
      id: Math.max(...promotions.value.map(p => p.id)) + 1,
      status: 'draft'
    }
    promotions.value.push(newPromotion)
  }
  closePromotionModal()
}

const publishPromotion = () => {
  const promotion = promotions.value.find(p => p.id === parseInt(publishForm.promotionId))
  const menuItem = menuItems.value.find(m => m.id === parseInt(publishForm.menuItemId))

  if (promotion && menuItem) {
    const newPublishedPromo = {
      id: Math.max(...publishedPromotions.value.map(p => p.id)) + 1,
      promotionId: parseInt(publishForm.promotionId),
      promotionTitle: promotion.title,
      menuItemId: parseInt(publishForm.menuItemId),
      menuItemName: menuItem.name,
      promotionalPrice: parseFloat(publishForm.promotionalPrice),
      startDate: promotion.startDate,
      endDate: promotion.endDate
    }
    publishedPromotions.value.push(newPublishedPromo)
    promotion.status = 'active'
    closePublishModal()
  }
}

const unpublishPromotion = (id) => {
  if (confirm('Êtes-vous sûr de vouloir retirer cette promotion ?')) {
    const publishedPromo = publishedPromotions.value.find(p => p.id === id)
    if (publishedPromo) {
      const promotion = promotions.value.find(p => p.id === publishedPromo.promotionId)
      if (promotion) {
        promotion.status = 'draft'
      }
    }
    publishedPromotions.value = publishedPromotions.value.filter(p => p.id !== id)
  }
}

// Méthodes pour la gestion des catégories
const editCategory = (category) => {
  editingCategory.value = category
  Object.assign(categoryForm, category)
  showAddCategoryModal.value = true
}

const deleteCategory = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
    // Vérifier si des plats utilisent cette catégorie
    const itemsInCategory = menuItems.value.filter(item => item.categoryId === id)
    if (itemsInCategory.length > 0) {
      alert('Impossible de supprimer cette catégorie car des plats y sont associés.')
      return
    }
    categories.value = categories.value.filter(cat => cat.id !== id)
  }
}

const saveCategory = () => {
  const categoryData = {
    ...categoryForm,
    displayOrder: parseInt(categoryForm.displayOrder) || 1
  }

  if (editingCategory.value) {
    // Modification
    const index = categories.value.findIndex(cat => cat.id === editingCategory.value.id)
    categories.value[index] = { ...categoryData, id: editingCategory.value.id }
  } else {
    // Ajout
    const newCategory = {
      ...categoryData,
      id: Math.max(...categories.value.map(c => c.id)) + 1
    }
    categories.value.push(newCategory)
  }
  closeCategoryModal()
}

// Méthodes pour les uploads d'images
const handleImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    itemForm.image = URL.createObjectURL(file)
  }
}

const handlePromotionImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    promotionForm.image = URL.createObjectURL(file)
  }
}

// Méthodes de fermeture des modals
const closeModal = () => {
  showAddItemModal.value = false
  editingItem.value = null
  Object.keys(itemForm).forEach(key => {
    if (key !== 'available') itemForm[key] = ''
  })
  itemForm.available = true
}

const closePromotionModal = () => {
  showCreatePromotionModal.value = false
  editingPromotion.value = null
  Object.keys(promotionForm).forEach(key => promotionForm[key] = '')
}

const closePublishModal = () => {
  showPublishPromotionModal.value = false
  Object.keys(publishForm).forEach(key => publishForm[key] = '')
}

const closeCategoryModal = () => {
  showAddCategoryModal.value = false
  editingCategory.value = null
  Object.keys(categoryForm).forEach(key => categoryForm[key] = '')
}


</script>

<style scoped>
.menu-view {
  padding: 0;
  background-color: #f8f9fa;
  min-height: 100%;
}

.view-header {
  background: white;
  padding: 2rem;
  margin-bottom: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-header h2 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.view-header p {
  margin: 0;
  color: #6c757d;
}

.section-tabs {
  display: flex;
  background: white;
  border-radius: 8px;
  padding: 0.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  gap: 0.5rem;
}

.tab-button {
  flex: 1;
  padding: 1rem;
  border: none;
  background: none;
  cursor: pointer;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.tab-button.active {
  background: #a89f91;
  color: white;
}

.section-content {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
}

.promotion-actions-header {
  display: flex;
  gap: 1rem;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.search-input, .filter-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
}

.search-input {
  flex: 1;
  min-width: 200px;
}

.filter-select {
  min-width: 150px;
}

/* Styles pour les cartes de menu */
.menu-items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.menu-item-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.item-image {
  height: 200px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  color: #6c757d;
  font-size: 0.9rem;
}

.item-details {
  padding: 1.5rem;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.item-header h4 {
  margin: 0;
  color: #2c3e50;
  flex: 1;
}

.availability-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.5rem;
}

.availability-badge.available {
  background: #d4edda;
  color: #155724;
}

.availability-badge.unavailable {
  background: #f8d7da;
  color: #721c24;
}

.item-description {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.item-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.price-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.price {
  font-weight: bold;
  color: #a89f91;
  font-size: 1.1rem;
}

.daily-special {
  background: #fff3cd;
  color: #856404;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
}

.category {
  background: #e9ecef;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.8rem;
  color: #6c757d;
}

.item-additional-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.item-actions {
  display: flex;
  gap: 0.5rem;
}

/* Styles pour les catégories */
.categories-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.category-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: #f8f9fa;
}

.category-info h4 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.category-description {
  color: #6c757d;
  margin: 0 0 0.5rem 0;
}

.display-order {
  font-size: 0.8rem;
  color: #a89f91;
  font-weight: 600;
}

.category-actions {
  display: flex;
  gap: 0.5rem;
}

/* Styles pour les promotions */
.promotions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.promotion-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.promotion-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.promotion-image {
  height: 150px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.promotion-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.promotion-details {
  padding: 1.5rem;
}

.promotion-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.promotion-header h4 {
  margin: 0;
  color: #2c3e50;
  flex: 1;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.5rem;
}

.status.draft {
  background: #e2e3e5;
  color: #383d41;
}

.status.active {
  background: #d4edda;
  color: #155724;
}

.status.inactive {
  background: #f8d7da;
  color: #721c24;
}

.status.expired {
  background: #fff3cd;
  color: #856404;
}

.promotion-type {
  color: #a89f91;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
}

.promotion-description {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.promotion-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.8rem;
}

.promo-code {
  font-weight: 600;
  color: #2c3e50;
}

.promo-dates {
  color: #6c757d;
}

.promotion-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.promotion-actions {
  display: flex;
  gap: 0.5rem;
}

/* Promotions publiées */
.published-promotions {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #e9ecef;
}

.published-promotions h4 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
}

.published-promotions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.published-promo-card {
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: #f8f9fa;
}

.published-promo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.published-promo-header h5 {
  margin: 0;
  color: #2c3e50;
  flex: 1;
}

.promo-price {
  font-weight: bold;
  color: #a89f91;
  font-size: 1.1rem;
}

.published-promo-item {
  color: #6c757d;
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
}

.published-promo-dates {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.published-promo-actions {
  text-align: right;
}

/* Boutons */
.btn-primary, .btn-secondary, .btn-edit, .btn-delete, .btn-status {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #a89f91;
  color: white;
}

.btn-primary:hover {
  background: #8a8174;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-edit {
  background: #17a2b8;
  color: white;
}

.btn-edit:hover {
  background: #138496;
}

.btn-delete {
  background: #dc3545;
  color: white;
}

.btn-delete:hover {
  background: #c82333;
}

.btn-status {
  background: #28a745;
  color: white;
}

.btn-status:hover {
  background: #218838;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6c757d;
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal.large-modal {
  max-width: 700px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
  box-sizing: border-box;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: normal;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
}

.image-preview {
  margin-top: 0.5rem;
}

.image-preview img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 6px;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .section-tabs {
    flex-direction: column;
  }
  
  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .promotion-actions-header {
    flex-direction: column;
  }
  
  .filters {
    flex-direction: column;
  }
  
  .search-input, .filter-select {
    width: 100%;
  }
  
  .menu-items-grid, .promotions-grid, .published-promotions-grid {
    grid-template-columns: 1fr;
  }
  
  .category-card {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .category-actions {
    justify-content: flex-end;
  }
  
  .form-row {
    flex-direction: column;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }

  /* AJOUTEZ ces styles à la section Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999; /* Augmentez le z-index */
}

.modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  z-index: 10000; /* Ajoutez ceci */
  position: relative; /* Ajoutez ceci */
}

.modal.large-modal {
  max-width: 700px;
}
}
</style>