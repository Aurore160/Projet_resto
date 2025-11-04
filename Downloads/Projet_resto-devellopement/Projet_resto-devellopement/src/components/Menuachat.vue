<template>
  <!-- Menu avec bouton d'ajout activé -->
  <Menu
    :show-add-button="true" 
    @add-to-cart="addToCart"
  />
  
  <!-- Panier séparé -->
  <panier 
    :cart-items="cartItems"
    @increase-quantity="increaseQuantity"
    @decrease-quantity="decreaseQuantity"
    @remove-from-cart="removeFromCart"
    @proceed-to-payment="proceedToPayment"
  />
</template>

<script setup>
import { ref } from 'vue'
import Menu from './Menu.vue'
import panier from './panier.vue'


// État du panier géré dans MenuAchat
const cartItems = ref([])

// Fonctions pour gérer le panier
const addToCart = (dish) => {
  const existingItem = cartItems.value.find(item => item.id === dish.id)
  
  if (existingItem) {
    existingItem.quantity += 1
  } else {
    cartItems.value.push({
      id: dish.id,
      name: dish.name,
      description: dish.description,
      price: parseFloat(dish.price.replace(',', '')),
      quantity: 1,
      image: dish.image
    })
  }
}

const removeFromCart = (itemId) => {
  cartItems.value = cartItems.value.filter(item => item.id !== itemId)
}

const increaseQuantity = (itemId) => {
  const item = cartItems.value.find(item => item.id === itemId)
  if (item) {
    item.quantity += 1
  }
}

const decreaseQuantity = (itemId) => {
  const item = cartItems.value.find(item => item.id === itemId)
  if (item && item.quantity > 1) {
    item.quantity -= 1
  } else {
    removeFromCart(itemId)
  }
}

const proceedToPayment = () => {
  alert('Redirection vers le paiement...')
  // Ici vous pouvez intégrer votre logique de paiement
}
</script>