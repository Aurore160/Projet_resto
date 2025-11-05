<template>
  <header class="site-header">
    <div class="header-inner">
      <router-link to="/" class="logo"><img src="@/assets/ZEDUC.png" alt="logo" /></router-link>

      <nav class="main-nav">
        <router-link to="/" class="nav-item">Accueil</router-link>
        <router-link to="/menu" class="nav-item">Menu</router-link>
        <router-link to="/promotion" class="nav-item">Promotion</router-link>
        <router-link to="/quiz" class="nav-item">Quiz</router-link>
        <router-link to="/history" class="nav-item">Historique</router-link>
        <router-link to="/contact" class="nav-item">Contact</router-link>
        <router-link to="/favorites" class="nav-item">Favoris</router-link>
        <router-link to="/orders" class="nav-item">Commandes</router-link>
        <router-link to="/reviews" class="nav-item">Mes avis </router-link>
        
      </nav>

      <div class="user-cart">
        <div v-if="store.state.user" class="user-block">
          <span class="user-name">{{ store.state.user.firstName || store.state.user.email }}</span>
          <button class="user-icon" @click="toggleProfile">
            <img v-if="store.state.user.avatar" :src="store.state.user.avatar" alt="avatar" />
            <i v-else class="fas fa-user-circle fa-2x"></i>
          </button>
        </div>

        <button class="notif-toggle" @click="toggleNotif">
          <i class="fas fa-bell"></i>
          <span v-if="unreadCount" class="notif-count">{{ unreadCount }}</span>
        </button>

        <button class="cart-toggle" @click="showCart = !showCart">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-count" v-if="store.state.cart.length">{{ store.state.cart.length }}</span>
        </button>

        <button class="cart-toggle" @click="showCart = !showCart">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-count" v-if="store.state.cart.length">{{ store.state.cart.length }}</span>
        </button>
      </div>
    </div>

    
    <aside class="cart-panel" :class="{ open: showCart }">
      <header>
        <h3>Panier</h3>
        <button @click="showCart=false">✕</button>
      </header>

      <div v-if="store.state.cart.length === 0" class="empty">Votre panier est vide</div>

      <div class="cart-items" v-else>
        <div v-for="it in store.state.cart" :key="it.id" class="cart-item">
          <img :src="it.image" alt="it.name" />
          <div class="ci-info">
            <strong>{{ it.name }}</strong>
            <small>{{ it.description }}</small>
            <div class="qty">
              <button @click="changeQty(it.id, it.qty-1)">-</button>
              <span>{{ it.qty }}</span>
              <button @click="changeQty(it.id, it.qty+1)">+</button>
            </div>
          </div>
          <div class="ci-price">{{ formatPrice(it.price) }}</div>
        </div>
      </div>

      <div v-if="notifOpen" class="notif-panel" @click.self="notifOpen = false">
        <div class="notif-card">
          <h3>Notifications</h3>
          <ul>
            <li v-for="n in store.state.notifications" :key="n.id">
              <span>{{ n.text }}</span>
              <small>{{ n.date }}</small>
            </li>
          </ul>
        </div>
      </div>

      <div class="cart-footer">
        <div>Subtotal: {{ formatNumber(store.subtotal) }}</div>
        <div>Tax: {{ formatNumber(store.tax) }}</div>
        <div>Points: {{ store.state.points }} pts (-{{ (store.state.points/100).toFixed(2) }}$)</div>
        <div class="total">Total: {{ formatNumber(store.total) }}</div>

        <button class="pay-btn" @click="pay">Payer</button>
      </div>
    </aside>

    
    <div v-if="profileOpen" class="profile-overlay" @click.self="closeProfile">
      <div class="profile-card">
        <h3>Mon profil</h3>
        <form @submit.prevent="saveProfile">
          <label>Photo (URL)</label>
          <input v-model="form.avatar" placeholder="URL image" />
          <label>Prénom</label>
          <input v-model="form.firstName" />
          <label>Nom</label>
          <input v-model="form.lastName" />
          <label>Email</label>
          <input v-model="form.email" type="email" />
          <label>Mot de passe</label>
          <input v-model="form.password" type="password" placeholder="Laissez vide pour ne pas changer" />
          <div class="profile-actions">
            <button type="submit">Valider</button>
            <button type="button" @click="closeProfile">Fermer</button>
          </div>
        </form>
      </div>
    </div>

  </header>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useUserStore } from "@/stores/userStore.js";
const userStore = useUserStore();

const showCart = ref(false);
const profileOpen = ref(false);
const form = reactive({
  avatar: store.state.user?.avatar || "",
  firstName: store.state.user?.firstName || "",
  lastName: store.state.user?.lastName || "",
  email: store.state.user?.email || "",
  password: ""
});

const notifOpen = ref(false);
function toggleNotif() {
  notifOpen.value = !notifOpen.value;
  store.markAllNotificationsAsSeen();
}

const unreadCount = computed(
  () => store.state.notifications.filter((n) => !n.seen).length
);

function toggleProfile() { profileOpen.value = !profileOpen.value; }
function closeProfile() { profileOpen.value = false; }


const formatPrice = s => typeof s === "number" ? `$${s}` : s;
const formatNumber = v => `$${(v.value ?? v).toFixed(2)}`;

function changeQty(id, qty) { store.updateQty(id, qty); }

function saveProfile() {
  const u = { ...store.state.user, avatar: form.avatar, firstName: form.firstName, lastName: form.lastName, email: form.email };
  if (form.password) u.password = form.password; 
  store.setUser(u);
  profileOpen.value = false;
}

async function pay() {
  if (store.state.cart.length === 0) { alert("Votre panier est vide"); return; }

 
  if (confirm(`Payer ${formatNumber(store.total)} ?`)) {
    
    const invoice = {
      id: Date.now(),
      date: new Date().toISOString(),
      items: JSON.parse(JSON.stringify(store.state.cart)),
      subtotal: store.subtotal.value,
      tax: store.tax.value,
      total: store.total.value,
      user: store.state.user
    };
    store.pushInvoice(invoice);
    
    store.addPoints(Math.round(store.subtotal.value * 10));
    store.clearCart();
    alert("Paiement réussi ! Facture ajoutée à l’historique.");
    showCart.value = false;
  } else {
    alert("Paiement annulé ou moyen invalide.");
  }

  if (store.state.cart.length === 0) {
    alert("Votre panier est vide");
    return;
  }

  if (confirm(`Payer ${formatNumber(store.total)} ?`)) {
    const invoice = {
      id: Date.now(),
      date: new Date().toISOString(),
      items: JSON.parse(JSON.stringify(store.state.cart)),
      subtotal: store.subtotal.value,
      tax: store.tax.value,
      total: store.total.value,
      user: store.state.user,
    };
    store.pushInvoice(invoice);
    store.addPoints(Math.round(store.subtotal.value * 10));
    store.addNotification(
      `Commande #${invoice.id} payée avec succès (${store.total.value}$).`
    );
    store.clearCart();
    alert("Paiement réussi !");
    showCart.value = false;
  }
}
</script>

<style scoped>
@import "@/assets/site.css";


.user-cart { 
    display:flex; 
    gap:12px; 
    align-items:center; 
}

.user-name { 
    margin-right:8px; 
    font-weight:600; 
    color:#000; 
}
.user-icon img { 
    width:36px; 
    height:36px; 
    border-radius:50%; 
    object-fit:cover; 
}

.user-icon { 
    background:transparent; 
    border:none; 
    cursor:pointer; 
}

.cart-toggle { 
    position:relative; 
    background:transparent; 
    border:none; 
    cursor:pointer; 
}
.cart-count { 
    position:absolute; 
    top:-6px; 
    right:-6px; 
    background:#000;
    color:#fff;
    border-radius:50%; 
    padding:2px 6px; 
    font-size:12px 
}


.cart-panel { 
    position:fixed; 
    right:-420px; 
    top:0; 
    width:380px; 
    height:100vh; 
    background:#fff; 
    box-shadow:-5px 0 20px rgba(0,0,0,0.15); 
    transition:right .25s; 
    z-index:80; 
    display:flex; 
    flex-direction:column; 
}

.cart-panel.open { 
    right:0; 
}

.cart-panel header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:12px 16px; 
    border-bottom:1px solid #eee;
}

.cart-items { 
    padding:12px; 
    overflow:auto; 
    flex:1; 
}

.cart-item { 
    display:flex; 
    gap:8px; 
    align-items:center; 
    padding:8px 0; 
    border-bottom:1px solid #f2f2f2; 
}

.cart-item img{ 
    width:64px; 
    height:64px; 
    object-fit:cover; 
    border-radius:8px;
}

.notif-toggle {
  background: transparent;
  border: none;
  position: relative;
  cursor: pointer;
}
.notif-count {
  position: absolute;
  top: -6px;
  right: -6px;
  background: red;
  color: #fff;
  font-size: 12px;
  border-radius: 50%;
  padding: 2px 6px;
}

.notif-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 320px;
  height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  z-index: 95;
  display: flex;
  justify-content: flex-end;
}

.notif-card {
  background: #fff;
  width: 300px;
  height: 100%;
  overflow-y: auto;
  padding: 16px;
  border-left: 1px solid #ddd;
}

.notif-card ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.notif-card li {
  border-bottom: 1px solid #eee;
  padding: 8px 0;
}

.ci-info small{ 
    display:block; 
    color:#666; 
    font-size:12px;
}

.qty { 
    display:flex; 
    gap:8px; 
    align-items:center 
}

.qty button{ 
    width:28px; 
    height:28px; 
    border-radius:6px; 
    border:1px solid #ddd; 
    background:#f6f6f6; 
    cursor:pointer; 
}

.ci-price { 
    margin-left:auto; 
    font-weight:700; 
}


.cart-footer { 
    padding:12px; 
    border-top:1px solid #eee; 
    display:flex; 
    flex-direction:column; 
    gap:8px; 
}

.pay-btn{ 
    background:var(--accent); 
    color:#fff; 
    border:none; 
    padding:10px; 
    border-radius:8px; 
    cursor:pointer; 
}


.profile-overlay{ 
    position:fixed; 
    inset:0; 
    background: rgba(0,0,0,0.45); 
    display:flex; 
    justify-content:flex-end; 
    z-index:90; 
}

.profile-card { 
    width:360px; 
    background: #fff; 
    padding:18px; 
    margin:48px; 
    border-radius:10px; 
    box-shadow:0 8px 30px rgba(0,0,0,0.3); 
}

.profile-card h3{ 
    margin-top:0; 
}

.profile-card form { 
    display:flex; 
    flex-direction:column; 
    gap:8px; 
}

.profile-actions { 
    display:flex; 
    gap:8px; 
    justify-content:flex-end; 
    margin-top:8px 
}

.profile-card input{ 
    padding:8px; 
    border-radius:6px; 
    border:1px solid #ddd; 
}

</style>