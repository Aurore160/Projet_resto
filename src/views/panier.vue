<template>
  <div class="container my-5">
    <!-- Header avec progression -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="checkout-progress">
          <div class="progress-step active">
            <div class="step-number">1</div>
            <span>Panier</span>
          </div>
          <div class="progress-step" :class="{ active: currentStep >= 2 }">
            <div class="step-number">2</div>
            <span>Livraison</span>
          </div>
          <div class="progress-step" :class="{ active: currentStep >= 3 }">
            <div class="step-number">3</div>
            <span>Paiement</span>
          </div>
          <div class="progress-step" :class="{ active: currentStep >= 4 }">
            <div class="step-number">4</div>
            <span>Confirmation</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Panier -->
      <div class="col-lg-8" v-if="currentStep === 1">
        <div class="cart-header d-flex justify-content-between align-items-center mb-4">
          <h3 class="mb-0">🛒 Mon Panier</h3>
          <span class="badge bg-primary">{{ cart.length }} article(s)</span>
        </div>

        <div v-if="cart.length === 0" class="empty-cart text-center py-5">
          <div class="empty-icon mb-3">🛒</div>
          <h4 class="text-muted">Votre panier est vide</h4>
          <p class="text-muted">Découvrez nos délicieuses spécialités !</p>
          <button class="btn btn-primary">Voir le menu</button>
        </div>

        <div class="cart-items">
          <div
            v-for="(item, index) in cart"
            :key="item.id || index"
            class="cart-item card mb-3 border-0 shadow-sm"
          >
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-2">
                  <img :src="item.image" class="cart-item-img rounded" :alt="item.name" />
                </div>
                <div class="col-6">
                  <h6 class="mb-1">{{ item.name }}</h6>
                  <p class="text-muted small mb-2">{{ item.description }}</p>
                  <div class="item-actions">
                    <button 
                      class="btn btn-sm btn-outline-primary me-2"
                      @click="editItem(index)"
                    >
                      ✏️ Modifier
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-danger"
                      @click="removeItem(index)"
                    >
                      🗑️ Supprimer
                    </button>
                  </div>
                </div>
                <div class="col-2">
                  <div class="quantity-controls">
                    <button 
                      class="btn btn-sm btn-outline-secondary"
                      @click="decreaseQty(index)"
                      :disabled="item.qty <= 1"
                    >
                      -
                    </button>
                    <span class="mx-2 fw-bold">{{ item.qty }}</span>
                    <button 
                      class="btn btn-sm btn-outline-secondary"
                      @click="increaseQty(index)"
                    >
                      +
                    </button>
                  </div>
                </div>
                <div class="col-2 text-end">
                  <div class="fw-bold text-primary">{{ formatPrice(item.price * item.qty) }}</div>
                  <small class="text-muted">{{ formatPrice(item.price) }}/unité</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Options de livraison -->
        <div class="card border-0 shadow-sm mt-4">
          <div class="card-body">
            <h5 class="card-title">🚚 Options de livraison</h5>
            <div class="delivery-options">
              <div 
                class="delivery-option"
                :class="{ active: deliveryType === 'pickup' }"
                @click="deliveryType = 'pickup'"
              >
                <div class="option-content">
                  <div class="option-icon">🏪</div>
                  <div class="option-details">
                    <h6>Retrait sur place</h6>
                    <p class="mb-0">Venez récupérer votre commande au restaurant</p>
                  </div>
                  <div class="option-price">Gratuit</div>
                </div>
              </div>
              
              <div 
                class="delivery-option"
                :class="{ active: deliveryType === 'delivery' }"
                @click="deliveryType = 'delivery'"
              >
                <div class="option-content">
                  <div class="option-icon">🚗</div>
                  <div class="option-details">
                    <h6>Livraison à domicile</h6>
                    <p class="mb-0">Livré directement chez vous</p>
                  </div>
                  <div class="option-price">+5 000 FC</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <button class="btn btn-outline-secondary">
            ← Continuer mes achats
          </button>
          <button class="btn btn-primary" @click="goToStep(2)" :disabled="cart.length === 0">
            Continuer vers la livraison →
          </button>
        </div>
      </div>

      <!-- Étape Livraison -->
      <div class="col-lg-8" v-if="currentStep === 2">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="mb-4">📍 Informations de livraison</h4>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Prénom *</label>
                <input type="text" class="form-control" v-model="deliveryInfo.firstName" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Nom *</label>
                <input type="text" class="form-control" v-model="deliveryInfo.lastName" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Adresse *</label>
              <input type="text" class="form-control" v-model="deliveryInfo.address" required>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Code postal *</label>
                <input type="text" class="form-control" v-model="deliveryInfo.zipCode" required>
              </div>
              <div class="col-md-8 mb-3">
                <label class="form-label">Ville *</label>
                <input type="text" class="form-control" v-model="deliveryInfo.city" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Téléphone *</label>
              <input type="tel" class="form-control" v-model="deliveryInfo.phone" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Instructions de livraison (optionnel)</label>
              <textarea class="form-control" rows="3" v-model="deliveryInfo.instructions"></textarea>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <button class="btn btn-outline-secondary" @click="goToStep(1)">
                ← Retour au panier
              </button>
              <button class="btn btn-primary" @click="validateDelivery">
                Continuer vers le paiement →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Étape Paiement -->
      <div class="col-lg-8" v-if="currentStep === 3">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="mb-4">💳 Paiement</h4>
            
            <div class="payment-methods">
              <div 
                v-for="method in paymentMethods"
                :key="method.id"
                class="payment-method"
                :class="{ active: paymentMethod === method.id }"
                @click="paymentMethod = method.id"
              >
                <div class="method-content">
                  <div class="method-icon">{{ method.icon }}</div>
                  <div class="method-details">
                    <h6>{{ method.name }}</h6>
                    <p class="mb-0">{{ method.description }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Informations de carte (conditionnel) -->
            <div v-if="paymentMethod === 'card'" class="card-info mt-4 p-4 border rounded">
              <div class="row">
                <div class="col-12 mb-3">
                  <label class="form-label">Numéro de carte</label>
                  <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Date d'expiration</label>
                  <input type="text" class="form-control" placeholder="MM/AA">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">CVV</label>
                  <input type="text" class="form-control" placeholder="123">
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <button class="btn btn-outline-secondary" @click="goToStep(2)">
                ← Retour à la livraison
              </button>
              <button class="btn btn-success" @click="processPayment">
                Payer {{ formatPrice(total) }} →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Étape Confirmation -->
      <div class="col-lg-8" v-if="currentStep === 4">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="success-icon mb-4">🎉</div>
            <h3 class="text-success mb-3">Commande Confirmée !</h3>
            <p class="text-muted mb-4">Votre commande a été traitée avec succès</p>
            
            <div class="order-summary border rounded p-4 mb-4">
              <h5 class="mb-3">Récapitulatif de commande</h5>
              <div class="text-start">
                <div class="d-flex justify-content-between mb-2">
                  <span>Numéro de commande:</span>
                  <strong>#{{ orderNumber }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Total:</span>
                  <strong>{{ formatPrice(total) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Mode de livraison:</span>
                  <strong>{{ deliveryType === 'delivery' ? 'Livraison' : 'Retrait' }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span>Heure estimée:</span>
                  <strong>{{ estimatedTime }}</strong>
                </div>
              </div>
            </div>

            <button class="btn btn-primary" @click="resetCheckout">
              Faire une nouvelle commande
            </button>
          </div>
        </div>
      </div>

      <!-- Récapitulatif latéral -->
      <div class="col-lg-4">
        <div class="sticky-summary">
          <div class="card border-0 shadow-lg">
            <div class="card-body">
              <h5 class="card-title">Récapitulatif</h5>
              
              <div class="order-items mb-3">
                <div 
                  v-for="item in cart" 
                  :key="item.id"
                  class="order-item d-flex justify-content-between align-items-center mb-2"
                >
                  <div class="item-info">
                    <span class="item-name">{{ item.name }}</span>
                    <small class="text-muted d-block">x{{ item.qty }}</small>
                  </div>
                  <span class="item-price">{{ formatPrice(item.price * item.qty) }}</span>
                </div>
              </div>

              <hr>

              <div class="price-breakdown">
                <div class="d-flex justify-content-between mb-2">
                  <span>Sous-total:</span>
                  <span>{{ formatPrice(subtotal) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Livraison:</span>
                  <span>{{ formatPrice(deliveryCost) }}</span>
                </div>
                <div v-if="deliveryType === 'delivery'" class="d-flex justify-content-between mb-2">
                  <span>Frais de service:</span>
                  <span>{{ formatPrice(serviceFee) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                  <span>Total:</span>
                  <span class="text-primary">{{ formatPrice(total) }}</span>
                </div>
              </div>

              <!-- Facture en temps réel -->
              <div class="invoice-preview mt-4 p-3 bg-light rounded">
                <h6 class="mb-3">📋 Facture</h6>
                <div class="invoice-line" v-for="item in cart" :key="item.id">
                  {{ item.qty }}x {{ item.name }} - {{ formatPrice(item.price * item.qty) }}
                </div>
                <div class="invoice-line" v-if="deliveryCost > 0">
                  Livraison - {{ formatPrice(deliveryCost) }}
                </div>
                <div class="invoice-line" v-if="serviceFee > 0">
                  Frais de service - {{ formatPrice(serviceFee) }}
                </div>
                <hr class="my-2">
                <div class="invoice-total fw-bold">
                  TOTAL - {{ formatPrice(total) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

// États de l'application
const currentStep = ref(1);
const cart = ref([]);
const deliveryType = ref("pickup");
const paymentMethod = ref("card");
const orderNumber = ref("");
const estimatedTime = ref("");

// Informations de livraison
const deliveryInfo = ref({
  firstName: "",
  lastName: "",
  address: "",
  zipCode: "",
  city: "",
  phone: "",
  instructions: ""
});

// Méthodes de paiement disponibles
const paymentMethods = ref([
  { id: "card", name: "Carte bancaire", icon: "💳", description: "Paiement sécurisé" },
  { id: "orange", name: "Orange Money", icon: "🎯", description: "Paiement mobile" },
  { id: "airtel", name: "Airtel Money", icon: "📱", description: "Paiement mobile" },
  { id: "m-pesa", name: "M-Pesa", icon: "💰", description: "Paiement mobile" }
]);

// Calculs
const subtotal = computed(() => 
  cart.value.reduce((total, item) => total + (item.price * item.qty), 0)
);

const deliveryCost = computed(() => 
  deliveryType.value === "delivery" ? 5000 : 0  // 5 000 FC pour la livraison
);

const serviceFee = computed(() => 
  deliveryType.value === "delivery" ? 2000 : 0  // 2 000 FC frais de service
);

const total = computed(() => 
  subtotal.value + deliveryCost.value + serviceFee.value
);

// Fonctions utilitaires - DEVISE EN FRANCS CONGOLAIS
const formatPrice = (val) => {
  // Formatage pour les Francs Congolais
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(val) + ' FC';
};

// Gestion du panier
const removeItem = (index) => {
  cart.value.splice(index, 1);
};

const increaseQty = (index) => {
  cart.value[index].qty++;
};

const decreaseQty = (index) => {
  if (cart.value[index].qty > 1) {
    cart.value[index].qty--;
  }
};

const editItem = (index) => {
  // Ici vous pouvez implémenter une modal d'édition
  alert(`Modification de ${cart.value[index].name}`);
};

// Navigation entre les étapes
const goToStep = (step) => {
  currentStep.value = step;
};

const validateDelivery = () => {
  // Validation basique
  const required = ['firstName', 'lastName', 'address', 'zipCode', 'city', 'phone'];
  const isValid = required.every(field => deliveryInfo.value[field].trim() !== '');
  
  if (isValid) {
    goToStep(3);
  } else {
    alert("Veuillez remplir tous les champs obligatoires");
  }
};

const processPayment = () => {
  // Génération du numéro de commande
  orderNumber.value = 'CMD-' + Date.now().toString().slice(-6);
  
  // Estimation du temps
  const baseTime = deliveryType.value === 'delivery' ? 45 : 25;
  estimatedTime.value = `${baseTime}-${baseTime + 15} min`;
  
  goToStep(4);
};

const resetCheckout = () => {
  currentStep.value = 1;
  cart.value = [...initialCart];
  deliveryInfo.value = {
    firstName: "",
    lastName: "",
    address: "",
    zipCode: "",
    city: "",
    phone: "",
    instructions: ""
  };
};

// Données initiales - PRIX EN FRANCS CONGOLAIS
const initialCart = [
  {
    id: 1,
    name: "Pizza Margherita",
    description: "Tomate, mozzarella, basilic frais",
    price: 15000,  // 15 000 FC
    qty: 1,
    image: "/src/images/80e59ddd335067ac9aad370dc04917b9.JPG",
  },
  {
    id: 2,
    name: "Burger maison",
    description: "Boeuf, cheddar, sauce spéciale",
    price: 12000,  // 12 000 FC
    qty: 1,
    image: "/src/images/baufort.JPG",
  },
  {
    id: 3,
    name: "Poulet Braisé",
    description: "Poulet mariné avec épices locales",
    price: 18000,  // 18 000 FC
    qty: 1,
    image: "/src/images/poulet-braise.jpg",
  },
  {
    id: 4,
    name: "Frites Maison",
    description: "Frites croustillantes avec sel",
    price: 5000,   // 5 000 FC
    qty: 1,
    image: "/src/images/frites.jpg",
  }
];

// Initialisation
onMounted(() => {
  cart.value = [...initialCart];
});
</script>

<style scoped>
.checkout-progress {
  display: flex;
  justify-content: space-between;
  max-width: 600px;
  margin: 0 auto;
  position: relative;
}

.checkout-progress::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 0;
  right: 0;
  height: 2px;
  background: #e9ecef;
  z-index: 1;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-bottom: 8px;
  border: 3px solid white;
}

.progress-step.active .step-number {
  background: #0d6efd;
  color: white;
}

.progress-step span {
  font-size: 0.9rem;
  font-weight: 500;
}

.cart-item-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
}

.quantity-controls {
  display: flex;
  align-items: center;
  justify-content: center;
}

.quantity-controls .btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delivery-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.delivery-option {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.delivery-option:hover {
  border-color: #0d6efd;
}

.delivery-option.active {
  border-color: #0d6efd;
  background: #f8f9ff;
}

.option-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.option-icon {
  font-size: 24px;
}

.option-details h6 {
  margin-bottom: 4px;
}

.option-price {
  font-weight: bold;
  color: #0d6efd;
  margin-left: auto;
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-method {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-method:hover {
  border-color: #0d6efd;
}

.payment-method.active {
  border-color: #0d6efd;
  background: #f8f9ff;
}

.method-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.method-icon {
  font-size: 24px;
}

.sticky-summary {
  position: sticky;
  top: 20px;
}

.order-item {
  padding: 8px 0;
  border-bottom: 1px solid #f8f9fa;
}

.item-name {
  font-weight: 500;
}

.item-price {
  font-weight: 600;
}

.invoice-preview {
  font-size: 0.9rem;
}

.invoice-line {
  margin-bottom: 4px;
}

.invoice-total {
  color: #0d6efd;
}

.success-icon {
  font-size: 64px;
}

.empty-cart {
  background: #f8f9fa;
  border-radius: 12px;
}

.empty-icon {
  font-size: 64px;
  opacity: 0.5;
}

.cart-item {
  transition: transform 0.2s ease;
}

.cart-item:hover {
  transform: translateY(-2px);
}

.item-actions {
  margin-top: 8px;
}

.item-actions .btn {
  font-size: 0.8rem;
  padding: 4px 8px;
}

/* Style spécifique pour l'affichage des prix en FC */
.price-display {
  font-family: 'Courier New', monospace;
  font-weight: bold;
}

@media (max-width: 768px) {
  .checkout-progress {
    padding: 0 20px;
  }
  
  .step-number {
    width: 32px;
    height: 32px;
    font-size: 0.8rem;
  }
  
  .progress-step span {
    font-size: 0.8rem;
  }
  
  .option-content,
  .method-content {
    flex-direction: column;
    text-align: center;
    gap: 8px;
  }
  
  .option-price {
    margin-left: 0;
  }
  
  .cart-item-img {
    width: 60px;
    height: 60px;
  }
}
</style>