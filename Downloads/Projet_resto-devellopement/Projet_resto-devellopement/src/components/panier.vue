<template>
  <!-- Bouton panier fixe en bas -->
  <button class="cart-toggle-btn" @click="toggleCart">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-count" v-if="cartTotalItems > 0">{{ cartTotalItems }}</span>
  </button>

  <!-- Panier latéral -->
  <div class="cart-sidebar" :class="{ 'cart-open': isCartOpen }">
    <div class="cart-header">
      <h3>PANIER</h3>
      <button class="close-cart" @click="closeCart">×</button>
    </div>
    
    <div class="cart-content">
      <!-- Liste des articles -->
      <div class="cart-items" v-if="cartItems.length > 0">
        <div v-for="item in cartItems" :key="item.id" class="cart-item">
          <div class="item-info">
            <h4 class="item-name">{{ item.name }}</h4>
            <p class="item-description">{{ item.description }}</p>
            <div class="item-quantity-controls">
              <button class="qty-btn" @click="decreaseQuantity(item.id)">-</button>
              <span class="item-quantity">{{ item.quantity }}x</span>
              <button class="qty-btn" @click="increaseQuantity(item.id)">+</button>
            </div>
          </div>
          <div class="item-price">{{ formatPrice(item.price * item.quantity) }}</div>
          <button class="remove-item" @click="removeFromCart(item.id)">×</button>
        </div>
      </div>
      
      <!-- Panier vide -->
      <div v-else class="empty-cart">
        <i class="fas fa-shopping-cart"></i>
        <p>Votre panier est vide</p>
      </div>

      <!-- Résumé du panier -->
      <div class="cart-summary" v-if="cartItems.length > 0">
        <div class="summary-row">
          <span>Sous-total</span>
          <span>{{ formatPrice(subTotal) }}</span>
        </div>
        <div class="summary-row">
          <span>Taxes</span>
          <span>{{ formatPrice(tax) }}</span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span>{{ formatPrice(total) }}</span>
        </div>
        
        <!-- Boutons SE et PAYER -->
        <div class="cart-actions">
          <button class="btn-payer" @click="proceedToPayment">PAYER</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Overlay pour fermer le panier -->
  <div class="cart-overlay" :class="{ 'overlay-visible': isCartOpen }" @click="closeCart"></div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props pour recevoir les articles du panier et les fonctions
const props = defineProps({
  cartItems: {
    type: Array,
    default: () => []
  }
})

// Émettre les événements pour les actions sur le panier
const emit = defineEmits([
  'increase-quantity',
  'decrease-quantity', 
  'remove-from-cart',
  'proceed-to-payment'
])

// État local pour l'ouverture/fermeture du panier
const isCartOpen = ref(false)

// Fonctions du panier
const toggleCart = () => {
  isCartOpen.value = !isCartOpen.value
}

const closeCart = () => {
  isCartOpen.value = false
}

const increaseQuantity = (itemId) => {
  emit('increase-quantity', itemId)
}

const decreaseQuantity = (itemId) => {
  emit('decrease-quantity', itemId)
}

const removeFromCart = (itemId) => {
  emit('remove-from-cart', itemId)
}

const formatPrice = (price) => {
  return `$${price.toFixed(2)}`
}

const proceedToPayment = () => {
  emit('proceed-to-payment')
}

// Computed properties pour le panier
const cartTotalItems = computed(() => {
  return props.cartItems.reduce((total, item) => total + item.quantity, 0)
})

const subTotal = computed(() => {
  return props.cartItems.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const tax = computed(() => {
  return subTotal.value * 0.1 // 10% de taxe
})

const total = computed(() => {
  return subTotal.value + tax.value
})
</script>

<style scoped>
/* Styles pour le panier */
.cart-toggle-btn {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: var(--primary-color);
  color: var(--secondary-color);
  border: none;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  font-size: 1.5rem;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.cart-toggle-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
}

.cart-count {
  position: absolute;
  top: -5px;
  right: -5px;
  background: var(--secondary-color);
  color: var(--primary-color);
  border-radius: 50%;
  width: 25px;
  height: 25px;
  font-size: 0.8rem;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 380px;
  height: 100vh;
  background: var(--primary-color);
  box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
  z-index: 1001;
  transition: right 0.3s ease;
  display: flex;
  flex-direction: column;
}

.cart-open {
  right: 0;
}

.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: var(--primary-color);
  background: var(--primary-color);
  color: var(--secondary-color);
  margin-top: 4em;
}

.cart-header h3 {
  margin: 0;
  font-weight: 700;
}

.close-cart {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: var(--secondary-color);
}

.cart-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.cart-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem 0;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
}

.item-info {
  flex: 1;
}

.item-name {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: var(--secondary-color);
}

.item-description {
  font-size: 0.8rem;
  color: var(--fin-color);
  margin: 0 0 0.5rem 0;
}

.item-quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn {
  background: var(--accent-color);
  color: var(--secondary-color);
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-quantity {
  font-weight: bold;
  min-width: 30px;
  text-align: center;
}

.item-price {
  font-weight: 700;
  color: var(--secondary-color);
  margin-left: 1rem;
}

.remove-item {
  background: none;
  border: none;
  color: var(--secondary-color);
  font-size: 1.2rem;
  cursor: pointer;
  margin-left: 0.5rem;
}

.empty-cart {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--fin-color);
}

.empty-cart i {
  font-size: 5rem;
  margin-bottom: 1rem;
  margin-top: 2em;
  opacity: 1.5;
}

.cart-summary {
  padding: 1.5rem;
  border-top: 1px solid #eee;
  background: var(--primary-color);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.summary-row.total {
  font-weight: 700;
  font-size: 1.1rem;
  border-top: 1px solid #ddd;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
}

.cart-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn-payer {
  flex: 2;
  background: var(--secondary-color);
  color: var(--primary-color);
  border: none;
  padding: 0.8rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 700;
  font-size: 1.1rem;
}

.cart-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.overlay-visible {
  opacity: 1;
  visibility: visible;
}

@media (max-width: 768px) {
  .cart-sidebar {
    width: 100%;
    right: -100%;
  }
  
  .cart-toggle-btn {
    bottom: 15px;
    right: 15px;
    width: 55px;
    height: 55px;
    font-size: 1.3rem;
  }
}
</style>