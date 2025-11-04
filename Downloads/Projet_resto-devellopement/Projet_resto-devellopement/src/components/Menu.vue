<template>
  <!-- Catégories avec navigation horizontale très courbée -->
  <section class="categories-section">
    <div class="container">
      <h2 class="section-title">Catégories & Plats</h2>
      
      <div class="categories-scroll-wrapper">
        <div class="categories-scroll-container">
          <div 
            v-for="category in categories" 
            :key="category.id" 
            class="category-item"
            :class="{ 
              active: selectedCategoryId === category.id,
              'left-edge': isLeftEdge(category.id),
              'right-edge': isRightEdge(category.id)
            }"
            @click="selectCategory(category.id)"
          >
            <div class="category-image-wrapper">
              <div class="category-image">
                <img :src="category.image" :alt="category.name" class="rounded-circle">
                <div class="category-glow"></div>
              </div>
            </div>
            <h5 class="category-name">{{ category.name }}</h5>
          </div>
        </div>
        
        <!-- Boutons de navigation intégrés -->
        <button class="scroll-btn scroll-left" @click="scrollCategories(-1)">
          <span class="btn-arrow">‹</span>
          <div class="btn-glow"></div>
        </button>
        <button class="scroll-btn scroll-right" @click="scrollCategories(1)">
          <span class="btn-arrow">›</span>
          <div class="btn-glow"></div>
        </button>
      </div>
    </div>
  </section>

  <!-- Plats filtrés par catégorie avec layout adaptatif -->
  <section class="dishes-section">
    <div class="container">
      <div class="dishes-content-wrapper" :class="{ 'empty-state': filteredDishes.length === 0 }">
        <h3 class="dishes-title">Plats - {{ selectedCategoryName }}</h3>
        
        <!-- Conteneur adaptatif -->
        <div class="dishes-adaptive-container" v-if="filteredDishes.length > 0">
          <div class="dishes-grid-rows">
            <div 
              v-for="dish in filteredDishes" 
              :key="dish.id" 
              class="dish-card-wrapper"
            >
              <div class="dish-card">
                <div class="dish-image-container">
                  <img :src="dish.image" :alt="dish.name" class="dish-image">
                  <div class="dish-overlay"></div>
                </div>
                <div class="dish-content-inner">
                  <h5 class="dish-name">{{ dish.name }}</h5>
                  <p class="dish-description">{{ dish.description }}</p>
                  <div class="dish-footer">
                    <span class="dish-price">{{ dish.price }} FC</span>
                    <!-- Slot pour le bouton d'action personnalisable -->
                    <slot name="dish-action" :dish="dish">
                      <!-- Contenu par défaut si aucun slot n'est fourni -->
                      <button v-if="showAddButton" class="btn-add" @click="$emit('add-to-cart', dish)">
                        <span class="btn-add-icon">+</span>
                        <div class="btn-add-glow"></div>
                      </button>
                    </slot>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message si aucun plat - positionné au centre du conteneur adaptatif -->
        <div v-if="filteredDishes.length === 0" class="no-dishes">
          <div class="no-dishes-icon">🍽️</div>
          <p>Aucun plat disponible pour cette catégorie</p>
          <button class="btn-retour" @click="selectCategory(1)">
            Voir toutes les catégories
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Définir les émets
defineEmits(['add-to-cart'])

// Props pour la configuration
const props = defineProps({
  showAddButton: {
    type: Boolean,
    default: false
  },
  categories: {
    type: Array,
    default: () => [
      { id: 1, name: 'Desserts', image: '/src/images/desserts.jpg' },
      { id: 2, name: 'Breakfast', image: '/src/images/breakfast.jpg' },
      { id: 3, name: 'Take Away', image: '/src/images/takeaway.jpg' },
      { id: 4, name: 'Salades', image: '/src/images/salades.jpg' },
      { id: 5, name: 'Drinks', image: '/src/images/drinks.jpg' },
      { id: 6, name: 'Plats Principaux', image: '/src/images/plats.jpg' },
      { id: 7, name: 'Grillades', image: '/src/images/poulet-braise.jpg' },
      { id: 8, name: 'Pâtes', image: '/src/images/pates-carbonara.jpg' },
      { id: 9, name: 'Sushis', image: '/src/images/sushis.jpg' },
      { id: 10, name: 'Pizzas', image: '/src/images/pizzas.jpg' }
    ]
  },
  dishes: {
    type: Array,
    default: () => [
      {
        id: 1, name: 'Poulet Braisé', price: '6,500', categoryId: 7,
        description: 'Poulet braisé accompagné d\'alloco et riz', image: '/src/images/poulet-braise.jpg'
      },
      {
        id: 2, name: 'Poisson Grillé', price: '7,500', categoryId: 7,
        description: 'Poisson frais grillé avec plantain et légumes', image: '/src/images/poisson-grille.jpg'
      },
      {
        id: 3, name: 'Salade César', price: '4,500', categoryId: 4,
        description: 'Salade fraîche avec poulet grillé et sauce césar', image: '/src/images/salade-cesar.jpg'
      }
      // ... autres plats par défaut
    ]
  }
})

// État pour la catégorie sélectionnée
const selectedCategoryId = ref(1)

// Computed properties
const selectedCategoryName = computed(() => {
  const category = props.categories.find(cat => cat.id === selectedCategoryId.value)
  return category ? category.name : 'Tous les plats'
})

const filteredDishes = computed(() => {
  return props.dishes.filter(dish => dish.categoryId === selectedCategoryId.value)
})

// Fonctions
const selectCategory = (categoryId) => {
  selectedCategoryId.value = categoryId
}

const scrollCategories = (direction) => {
  const scrollContainer = document.querySelector('.categories-scroll-container')
  if (scrollContainer) {
    const scrollAmount = 300
    scrollContainer.scrollLeft += direction * scrollAmount
  }
}

const isLeftEdge = (categoryId) => {
  return props.categories[0].id === categoryId
}

const isRightEdge = (categoryId) => {
  return props.categories[props.categories.length - 1].id === categoryId
}

// Initialisation
onMounted(() => {
  if (props.categories.length > 0) {
    selectedCategoryId.value = props.categories[0].id
  }
})
</script>

<style scoped>
/* Copiez ici TOUT votre CSS existant pour les sections categories-section et dishes-section */
/* Section Catégories - Design courbé avancé */
.categories-section {
  padding: 7rem 0 1rem 0;
  background-color: var(--primary-color);
  position: relative;
  overflow: hidden;
}

.section-title {
  text-align: center;
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 1rem;
  color: var(--secondary-color);
  text-transform: uppercase;
  letter-spacing: 4px;
  position: relative;
}

/* Wrapper principal avec effet de courbure prononcé */
.categories-scroll-wrapper {
  position: relative;
  max-width: 75%;
  margin: 0 auto;
  padding: 0px 60px;
  border-radius: 40px;
  padding-top: 10px;
  padding-bottom: 10px;
}

/* Container de scroll avec effet de courbure intense */
.categories-scroll-container {
  display: flex;
  gap: 0.8rem;
  overflow-x: auto;
  scroll-behavior: smooth;
  padding: 2rem 1rem;
  scrollbar-width: none;
  -ms-overflow-style: none;
  
  /* Effet de courbure PRONONCÉ avec gradient complexe */
  mask-image: 
    radial-gradient(
      circle at 20% 50%,
      black 30%,
      transparent 60%
    ),
    radial-gradient(
      circle at 80% 50%,
      black 30%,
      transparent 60%
    ),
    linear-gradient(
      90deg,
      transparent 0%,
      black 15%,
      black 85%,
      transparent 100%
    );
  -webkit-mask-image: 
    radial-gradient(
      circle at 20% 50%,
      black 30%,
      transparent 60%
    ),
    radial-gradient(
      circle at 80% 50%,
      black 30%,
      transparent 60%
    ),
    linear-gradient(
      90deg,
      transparent 0%,
      black 15%,
      black 85%,
      transparent 100%
    );
  
  mask-composite: intersect;
  -webkit-mask-composite: source-in;
}

.categories-scroll-container::-webkit-scrollbar {
  display: none;
}

/* Items de catégorie avec design courbé */
.category-item {
  flex: 0 0 auto;
  text-align: center;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  padding: 1rem 0.8rem;
  border-radius: 25px;
  min-width: 110px;
  position: relative;
  background-color: var(--primary-color);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Effets de bordure courbée pour les éléments extrêmes */
.category-item.left-edge {
  margin-left: 5px;
}

.category-item.right-edge {
  margin-right: 5px;
}

.category-item::before {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: var(--hover-color);
  border-radius: 27px;
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: -1;
}

.category-item:hover {
  transform: translateY(-8px) scale(1.05);
  background: var(--accent-color);
  border-color: rgba(255, 255, 255, 0.2);
  box-shadow: 
    0 10px 25px rgba(0, 0, 0, 0.3),
    0 0 20px var(--glow-color);
}

.category-item.active {
  transform: translateY(-5px) scale(1.08);
  border-color: transparent;
  box-shadow: 
    0 15px 30px rgba(255, 107, 53, 0.4),
    0 0 30px var(--glow-color);
}

.category-item.active::before {
  opacity: 1;
}

/* Container d'image avec effet de lumière */
.category-image-wrapper {
  position: relative;
  margin-bottom: 0.8rem;
  padding: 5px;
}

.category-image {
  position: relative;
  display: inline-block;
}

.category-image img {
  width: 75px;
  height: 75px;
  object-fit: cover;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  z-index: 2;
}

.category-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 85px;
  height: 85px;
  background: var(--accent-color);
  border-radius: 50%;
  opacity: 0;
  filter: blur(15px);
  transition: opacity 0.4s ease;
  z-index: 1;
}

.category-item:hover .category-image img {
  transform: scale(1.15);
  border-color: var(--accent-color);
}

.category-item.active .category-image img {
  transform: scale(1.2);
  border-color: var(--secondary-color);
} 

.category-item.active .category-glow {
  opacity: 0.6;
}

/* Nom de catégorie */
.category-name {
  font-weight: 700;
  color: var(--secondary-color);
  margin: 0;
  font-size: 0.85rem;
  transition: all 0.3s ease;
}

.category-item.active .category-name {
  color: var(--secondary-color);
  font-weight: 800;
}

/* Boutons de scroll INTÉGRÉS et RAPPROCHÉS */
.scroll-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: var(--primary-color);
  color: var(--secondary-color);
  border: none;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  font-size: 2rem;
  font-weight: bold;
  cursor: pointer;
  z-index: 20;
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  border: var(--primary-color);
}

.scroll-btn:hover {
  transform: translateY(-50%) scale(1.15);
  box-shadow: 
    0 8px 25px rgba(255, 107, 53, 0.4),
    0 0 20px var(--glow-color);
}

.scroll-btn:active {
  transform: translateY(-50%) scale(1.3);
}

.scroll-left {
  left: 5px;
}

.scroll-right {
  right: 5px;
}

.btn-arrow {
  position: relative;
  z-index: 2;
  text-shadow: var(--primary-color);
}

.scroll-btn:hover .btn-glow {
  opacity: 0;
}

/* ===== NOUVELLE SECTION PLATS AVEC LAYOUT ADAPTATIF ===== */

.dishes-section {
  padding: 2rem 0;
  background: var(--secondary-color);
  min-height: 200px; /*Hauteur minimale réduite */
}

.dishes-content-wrapper {
  position: relative;
  max-width: 1200px;
  margin: 0 auto;
  transition: all 0.3s ease;
}

/* État quand il n'y a pas de plats */
.dishes-content-wrapper.empty-state {
  min-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dishes-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-top: 0.2em;
  margin-bottom: 2rem;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 2px;
  position: relative;
}

.dishes-title::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 2px;
  background: var(--accent-color);
  border-radius: 1px;
}

/* Conteneur adaptatif qui s'ajuste selon le nombre de plats */
.dishes-adaptive-container {
  width: 100%;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  min-height: 200px;
}

/* Grid par LIGNES - remplissage horizontal puis passage à la ligne suivante */
.dishes-grid-rows {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.2rem;
  width: 100%;
  /* Pas de hauteur fixe - s'adapte au contenu */
}

/* Chaque carte de plat */
.dish-card-wrapper {
  min-width: 0;
}

.dish-card {
  background: var(--secondary-color);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  border: var(--secondary-color);
  height: 260px;
  position: relative;
  display: flex;
  flex-direction: column;
}

.dish-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, transparent, var(--accent-color), transparent);
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 16px;
  z-index: 1;
}

.dish-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: var(--hover-color);
  border-color: var(--hover-color);
}

.dish-card:hover::before {
  opacity: 0.1;
}

.dish-image-container {
  position: relative;
  overflow: hidden;
  height: 140px;
  flex-shrink: 0;
  margin-bottom: 0.1em;
}

.dish-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.4s ease;
}

.dish-card:hover .dish-image {
  transform: scale(1.08);
}

.dish-content-inner {
  padding: 1rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 2;
}

.dish-name {
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 0.4rem;
  font-size: 0.95rem;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  line-height: 1.2;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
   overflow: hidden;
}

.dish-description {
  color: var(--fin-color);
  font-weight: 300;
  font-size: 0.75rem;
  margin-bottom: 0.8rem;
  line-height: 1.3;
  flex-grow: 1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.dish-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.dish-price {
  font-weight: 700;
  color: var(--accent-color);
  font-size: 1rem;
  text-shadow: var(--primary-color);
}

.btn-add {
  background: var(--secondary-color);
  color: var(--accent-color);
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  box-shadow: var(--secondary-color);
  flex-shrink: 0;
}

.btn-add:hover {
  transform: scale(1.15) rotate(90deg);
  box-shadow: var(--accent-color);
}

.btn-add:active {
  transform: scale(0.9);
}

.btn-add-icon {
  position: relative;
  z-index: 2;
}

.btn-add-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  background: var(--accent-color);
  border-radius: 50%;
  opacity: 0;
  filter: blur(8px);
  transition: opacity 0.3s ease;
}

.btn-add:hover .btn-add-glow {
  opacity: 0.6;
}

/* Message aucun plat - CENTRÉ dans le conteneur adaptatif */
.no-dishes {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--fin-color);
  width: 100%;
}

.no-dishes-icon {
  font-size: 4rem;
  margin-bottom: 0.1rem;
  opacity: 1.5;
}

.no-dishes p {
  font-size: 1.2rem;
  font-style: italic;
  margin-bottom: 1.5rem;
}

.btn-retour {
  background: var(--primary-color);
  color: var(--secondary-color);
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 25px;
  font-weight: 300;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--primary-color);
}

.btn-retour:hover {
  transform: translateY(-2px);
  box-shadow: var(--primary-color);
}

/* Responsive */
@media (max-width: 1200px) {
  .dishes-grid-rows {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}

@media (max-width: 768px) {
  .categories-scroll-wrapper {
    padding: 0 50px;
  }
  
  .scroll-btn {
    width: 45px;
    height: 45px;
    font-size: 1.6rem;
  }
  
  .scroll-left {
    left: 2px;
  }
  
  .scroll-right {
    right: 2px;
  }
  
  .category-image img {
    width: 65px;
    height: 65px;
  }
  
  .category-item {
    min-width: 95px;
    padding: 0.8rem 0.6rem;
  }

  .dishes-grid-rows {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
  }

  .dish-card {
    height: 240px;
  }

  .dish-image-container {
    height: 120px;
  }

  .dishes-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .categories-scroll-wrapper {
    padding: 0 40px;
  }
  
  .scroll-btn {
    width: 40px;
    height: 40px;
    font-size: 1.4rem;
  }
  
  .category-image img {
    width: 55px;
    height: 55px;
  }
  
  .category-item {
    min-width: 85px;
    padding: 0.6rem 0.4rem;
  }
  
  .category-name {
    font-size: 0.75rem;
  }

  .dishes-grid-rows {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.8rem;
  }

  .dish-card {
    height: 220px;
  }

  .dish-image-container {
    height: 110px;
  }

  .dish-content-inner {
    padding: 0.8rem;
  }

  .dish-name {
    font-size: 0.9rem;
  }

  .dish-description {
    font-size: 0.7rem;
  }

  .no-dishes {
    padding: 2rem 1rem;
  }

  .no-dishes-icon {
    font-size: 3rem;
  }

  .no-dishes p {
    font-size: 1rem;
  }
}

/* Animation d'apparition des plats */
.dish-card-wrapper {
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Délai d'animation pour chaque carte */
.dish-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.dish-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
.dish-card-wrapper:nth-child(3) { animation-delay: 0.3s; }
.dish-card-wrapper:nth-child(4) { animation-delay: 0.4s; }
.dish-card-wrapper:nth-child(5) { animation-delay: 0.5s; }
.dish-card-wrapper:nth-child(6) { animation-delay: 0.6s; }
.dish-card-wrapper:nth-child(7) { animation-delay: 0.7s; }
.dish-card-wrapper:nth-child(8) { animation-delay: 0.8s; }
.dish-card-wrapper:nth-child(9) { animation-delay: 0.9s; }
.dish-card-wrapper:nth-child(10) { animation-delay: 1s; }
</style>