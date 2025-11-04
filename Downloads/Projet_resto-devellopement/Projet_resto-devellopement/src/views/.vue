<template>
  <div class="paiement-page">
    <!-- Header -->
    <div class="paiement-header">
      <button class="btn-back" @click="$router.back()">
        <i class="fas fa-arrow-left"></i> Retour
      </button>
      <h1>Finalisation de votre commande</h1>
    </div>

    <div class="paiement-container">
      <!-- Colonne gauche : Récapitulatif détaillé -->
      <div class="recap-column">
        <div class="recap-card">
          <h2>Récapitulatif de commande</h2>
          
          <!-- Liste des articles -->
          <div class="articles-list">
            <div v-for="item in cartItems" :key="item.id" class="article-item">
              <div class="article-image">
                <img :src="item.image" :alt="item.name">
              </div>
              <div class="article-details">
                <h4>{{ item.name }}</h4>
                <p class="article-description">{{ item.description }}</p>
                <div class="article-quantity">Quantité: {{ item.quantity }}</div>
              </div>
              <div class="article-price">
                {{ formatPrice(item.price * item.quantity) }}
              </div>
            </div>
          </div>

          <!-- Détails des prix -->
          <div class="price-breakdown">
            <div class="price-row">
              <span>Sous-total</span>
              <span>{{ formatPrice(subTotal) }}</span>
            </div>
            <div class="price-row">
              <span>Frais de service</span>
              <span>{{ formatPrice(serviceFee) }}</span>
            </div>
            <div class="price-row">
              <span>TVA (18%)</span>
              <span>{{ formatPrice(tva) }}</span>
            </div>
            <div class="price-row total">
              <span>Total à payer</span>
              <span>{{ formatPrice(total) }}</span>
            </div>
          </div>

          <!-- Informations du restaurant -->
          <div class="restaurant-info">
            <h4>Restaurant Miam Miam</h4>
            <p>123 Avenue de la Gastronomie, Kinshasa</p>
            <p>📞 +243 81 234 5678</p>
            <p>🕒 Ouvert de 08h00 à 23h00</p>
          </div>
        </div>
      </div>

      <!-- Colonne droite : Informations client et paiement -->
      <div class="info-column">
        <!-- Informations client -->
        <div class="info-card">
          <h3>Informations personnelles</h3>
          <form @submit.prevent="processPayment">
            <div class="form-group">
              <label for="name">Nom complet *</label>
              <input 
                type="text" 
                id="name" 
                v-model="clientInfo.name" 
                required 
                placeholder="Votre nom complet"
              >
            </div>

            <div class="form-group">
              <label for="phone">Téléphone *</label>
              <input 
                type="tel" 
                id="phone" 
                v-model="clientInfo.phone" 
                required 
                placeholder="+243 XX XXX XXXX"
              >
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input 
                type="email" 
                id="email" 
                v-model="clientInfo.email" 
                placeholder="votre@email.com"
              >
            </div>

            <div class="form-group">
              <label for="address">Adresse de livraison *</label>
              <textarea 
                id="address" 
                v-model="clientInfo.address" 
                required 
                placeholder="Votre adresse complète"
                rows="3"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="notes">Notes pour la commande</label>
              <textarea 
                id="notes" 
                v-model="clientInfo.notes" 
                placeholder="Instructions spéciales, allergies..."
                rows="2"
              ></textarea>
            </div>
          </form>
        </div>

        <!-- Méthode de paiement -->
        <div class="payment-card">
          <h3>Méthode de paiement</h3>
          <div class="payment-methods">
            <label class="payment-option">
              <input 
                type="radio" 
                name="payment" 
                value="cash" 
                v-model="paymentMethod"
              >
              <div class="payment-content">
                <span class="payment-icon">💵</span>
                <span>Paiement à la livraison</span>
              </div>
            </label>

            <label class="payment-option">
              <input 
                type="radio" 
                name="payment" 
                value="mobile" 
                v-model="paymentMethod"
              >
              <div class="payment-content">
                <span class="payment-icon">📱</span>
                <span>Paiement mobile (Orange Money, M-Pesa)</span>
              </div>
            </label>

            <label class="payment-option">
              <input 
                type="radio" 
                name="payment" 
                value="card" 
                v-model="paymentMethod"
              >
              <div class="payment-content">
                <span class="payment-icon">💳</span>
                <span>Carte bancaire</span>
              </div>
            </label>
          </div>

          <!-- Détails paiement mobile -->
          <div v-if="paymentMethod === 'mobile'" class="mobile-payment-details">
            <div class="form-group">
              <label for="mobileNumber">Numéro mobile *</label>
              <input 
                type="tel" 
                id="mobileNumber" 
                v-model="mobilePayment.number" 
                required 
                placeholder="+243 XX XXX XXXX"
              >
            </div>
            <div class="form-group">
              <label for="mobileProvider">Opérateur *</label>
              <select id="mobileProvider" v-model="mobilePayment.provider" required>
                <option value="">Choisissez votre opérateur</option>
                <option value="orange">Orange Money</option>
                <option value="mpesa">M-Pesa</option>
                <option value="airtel">Airtel Money</option>
              </select>
            </div>
          </div>

          <!-- Bouton de confirmation -->
          <button 
            class="btn-confirm-payment" 
            @click="processPayment"
            :disabled="!canProceed"
          >
            <span v-if="processing">Traitement en cours...</span>
            <span v-else>
              Confirmer le paiement - {{ formatPrice(total) }}
            </span>
          </button>

          <p class="security-note">
            🔒 Vos informations sont sécurisées et protégées
          </p>
        </div>
      </div>
    </div>

    <!-- Modal de confirmation -->
    <div v-if="showConfirmation" class="confirmation-modal">
      <div class="modal-content">
        <div class="modal-icon">✅</div>
        <h2>Commande confirmée !</h2>
        <p class="order-number">N° de commande: {{ orderNumber }}</p>
        <p>Votre commande a été prise en compte et sera préparée rapidement.</p>
        <div class="estimated-time">
          <strong>Temps estimé: 25-35 minutes</strong>
        </div>
        <button class="btn-close-modal" @click="closeModal">
          Retour à l'accueil
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Props pour recevoir les articles du panier
const props = defineProps({
  cartItems: {
    type: Array,
    default: () => []
  }
})

// État local
const clientInfo = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
  notes: ''
})

const paymentMethod = ref('cash')
const mobilePayment = ref({
  number: '',
  provider: ''
})

const processing = ref(false)
const showConfirmation = ref(false)
const orderNumber = ref('')

// Computed properties
const subTotal = computed(() => {
  return props.cartItems.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const serviceFee = computed(() => {
  return subTotal.value * 0.05 // 5% de frais de service
})

const tva = computed(() => {
  return (subTotal.value + serviceFee.value) * 0.18 // 18% de TVA
})

const total = computed(() => {
  return subTotal.value + serviceFee.value + tva.value
})

const canProceed = computed(() => {
  return clientInfo.value.name && 
         clientInfo.value.phone && 
         clientInfo.value.address &&
         (paymentMethod.value !== 'mobile' || 
          (mobilePayment.value.number && mobilePayment.value.provider))
})

// Méthodes
const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'CDF'
  }).format(price)
}

const processPayment = async () => {
  if (!canProceed.value) return
  
  processing.value = true
  
  // Simulation de traitement
  await new Promise(resolve => setTimeout(resolve, 2000))
  
  // Générer un numéro de commande
  orderNumber.value = 'CMD-' + Date.now().toString().slice(-6)
  showConfirmation.value = true
  processing.value = false
  
  // Ici, vous enverriez les données à votre backend
  console.log('Commande traitée:', {
    client: clientInfo.value,
    payment: paymentMethod.value,
    mobile: mobilePayment.value,
    items: props.cartItems,
    total: total.value
  })
}

const closeModal = () => {
  showConfirmation.value = false
  router.push('/')
}

// Initialisation
onMounted(() => {
  if (props.cartItems.length === 0) {
    router.push('/menu')
  }
})
</script>

<style scoped>
.paiement-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.paiement-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  color: white;
}

.btn-back {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 25px;
  cursor: pointer;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.3);
}

.paiement-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.recap-card, .info-card, .payment-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  margin-bottom: 1.5rem;
}

.recap-card h2, .info-card h3, .payment-card h3 {
  color: #333;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 0.5rem;
}

/* Articles list */
.articles-list {
  max-height: 400px;
  overflow-y: auto;
  margin-bottom: 1.5rem;
}

.article-item {
  display: flex;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.article-image {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  overflow: hidden;
  margin-right: 1rem;
  flex-shrink: 0;
}

.article-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-details {
  flex: 1;
}

.article-details h4 {
  margin: 0 0 0.3rem 0;
  color: #333;
}

.article-description {
  font-size: 0.8rem;
  color: #666;
  margin: 0 0 0.5rem 0;
}

.article-quantity {
  font-size: 0.8rem;
  color: #888;
}

.article-price {
  font-weight: bold;
  color: #e74c3c;
  font-size: 1.1rem;
}

/* Price breakdown */
.price-breakdown {
  border-top: 2px solid #f0f0f0;
  padding-top: 1rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.8rem;
  color: #666;
}

.price-row.total {
  font-weight: bold;
  font-size: 1.2rem;
  color: #333;
  border-top: 1px solid #ddd;
  padding-top: 0.8rem;
}

/* Restaurant info */
.restaurant-info {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 10px;
  margin-top: 1.5rem;
}

.restaurant-info h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.restaurant-info p {
  margin: 0.3rem 0;
  font-size: 0.9rem;
  color: #666;
}

/* Form styles */
.form-group {
  margin-bottom: 1.2rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

input, textarea, select {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #667eea;
}

/* Payment methods */
.payment-methods {
  margin-bottom: 1.5rem;
}

.payment-option {
  display: flex;
  align-items: center;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  margin-bottom: 0.8rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-option:hover {
  border-color: #667eea;
  background: #f8f9ff;
}

.payment-option input {
  width: auto;
  margin-right: 1rem;
}

.payment-content {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.payment-icon {
  font-size: 1.2rem;
}

.mobile-payment-details {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

/* Confirm button */
.btn-confirm-payment {
  width: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1.2rem;
  border-radius: 15px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-confirm-payment:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-confirm-payment:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
}

.security-note {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: #666;
}

/* Confirmation modal */
.confirmation-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 3rem;
  border-radius: 20px;
  text-align: center;
  max-width: 500px;
  width: 90%;
}

.modal-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.order-number {
  background: #f8f9fa;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-weight: bold;
  color: #333;
  display: inline-block;
  margin: 1rem 0;
}

.estimated-time {
  background: #e8f5e8;
  color: #2ecc71;
  padding: 0.8rem;
  border-radius: 10px;
  margin: 1.5rem 0;
}

.btn-close-modal {
  background: #667eea;
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 10px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-close-modal:hover {
  background: #5a6fd8;
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 968px) {
  .paiement-container {
    grid-template-columns: 1fr;
  }
  
  .paiement-page {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .recap-card, .info-card, .payment-card {
    padding: 1.5rem;
  }
  
  .article-item {
    flex-direction: column;
    text-align: center;
  }
  
  .article-image {
    margin-right: 0;
    margin-bottom: 1rem;
  }
}
</style>