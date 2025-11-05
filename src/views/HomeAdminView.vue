<template>
  <div class="home-admin">
    <HeaderSpecified />

    <main class="admin-main container">
      <div class="layout">
       
        <aside class="admin-sidebar">
          <ul>
            <li :class="{ active: activeTab === 'addProduct' }" @click="activeTab = 'addProduct'">Ajouter produit</li>
            <li :class="{ active: activeTab === 'listProduct' }" @click="activeTab = 'listProduct'">Liste produit</li>
            <li :class="{ active: activeTab === 'addPromo' }" @click="activeTab = 'addPromo'">Ajouter promotion</li>
            <li :class="{ active: activeTab === 'listPromo' }" @click="activeTab = 'listPromo'">Liste promotion</li>
            <li :class="{ active: activeTab === 'addEvent' }" @click="activeTab = 'addEvent'">Ajouter événement</li>
            <li :class="{ active: activeTab === 'listEvent' }" @click="activeTab = 'listEvent'">Liste événement</li>
          </ul>
        </aside>

        
        <section class="admin-content">
         
          <div class="top-controls">
            <input v-model="searchTerm" placeholder="Rechercher..." class="search" />
            <select v-model="filterCategory" class="select">
              <option value="">Toutes catégories</option>
              <option v-for="c in categories" :key="c">{{ c }}</option>
            </select>
            <button class="btn" @click="clearFilters">Réinitialiser</button>
          </div>

          
          <div v-if="activeTab === 'addProduct'" class="panel panel-dark">
            <h3>Ajouter produit</h3>
            <div class="add-grid">
              <div class="image-upload">
                <label class="img-placeholder" v-if="!newProduct.imageData">
                  <input type="file" accept="image/*" @change="onProductImage" />
                  <span>Charger une image</span>
                </label>
                <div class="img-preview" v-else>
                  <img :src="newProduct.imageData" alt="preview" />
                </div>
              </div>

              <div class="form">
                <label>Nom produit</label>
                <input v-model="newProduct.name" />

                <label>Description produit</label>
                <textarea v-model="newProduct.description"></textarea>

                <div class="row">
                  <div class="col">
                    <label>Catégorie</label>
                    <select v-model="newProduct.category">
                      <option v-for="c in categories" :key="c">{{ c }}</option>
                    </select>
                  </div>
                  <div class="col">
                    <label>Price</label>
                    <input v-model.number="newProduct.price" type="number" />
                  </div>
                </div>

                <button class="btn accent" @click="addProduct">Ajouter</button>
              </div>
            </div>
          </div>

          
          <div v-if="activeTab === 'listProduct'" class="panel panel-dark">
            <h3>Liste produit</h3>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Catégorie</th>
                  <th>Prix</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in filteredProducts" :key="p.id">
                  <td class="img-cell"><img :src="p.image || placeholder" /></td>
                  <td>{{ p.name }}</td>
                  <td>{{ p.category }}</td>
                  <td>{{ formatPrice(p.price) }}</td>
                  <td class="actions">
                    <button class="icon" @click="addToCartFromAdmin(p)" title="Ajouter au panier"> Ajouter au panier</button>
                    <button class="icon" @click="editProduct(p)" title="Modifier"> Modifier</button>
                    <button class="icon danger" @click="deleteProduct(p.id)" title="Supprimer"> Supprimer </button>
                  </td>
                </tr>
                <tr v-if="filteredProducts.length === 0">
                  <td colspan="5" class="empty">Aucun produit trouvé.</td>
                </tr>
              </tbody>
            </table>
          </div>

         
          <div v-if="activeTab === 'addPromo'" class="panel panel-dark">
            <h3>Ajouter promotion</h3>
            <div class="add-grid">
              <div class="image-upload">
                <label class="img-placeholder" v-if="!newPromo.imageData">
                  <input type="file" accept="image/*" @change="onPromoImage" />
                  <span>Charger une image</span>
                </label>
                <div class="img-preview" v-else>
                  <img :src="newPromo.imageData" alt="preview" />
                </div>
              </div>

              <div class="form">
                <label>Nom promotion</label>
                <input v-model="newPromo.name" />

                <label>Description promotion</label>
                <textarea v-model="newPromo.description"></textarea>

                <div class="row">
                  <div class="col">
                    <label>Produit (sélectionne un produit existant)</label>
                    <select v-model="newPromo.productId">
                      <option value="">-- Choisir un produit --</option>
                      <option v-for="p in products" :value="p.id" :key="p.id">{{ p.name }} — {{ p.category }}</option>
                    </select>
                  </div>
                  <div class="col">
                    <label>Qte</label>
                    <input v-model.number="newPromo.quantity" type="number" />
                  </div>
                  <div class="col">
                    <label>Prix promo</label>
                    <input v-model.number="newPromo.price" type="number" />
                  </div>
                </div>

                <button class="btn accent" @click="addPromotion">Ajouter promotion</button>
              </div>
            </div>
          </div>

         
          <div v-if="activeTab === 'listPromo'" class="panel panel-dark">
            <h3>Liste promotion</h3>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Qte</th>
                  <th>Catégorie</th>
                  <th>Prix</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pr in filteredPromotions" :key="pr.id">
                  <td class="img-cell"><img :src="pr.image || placeholder" /></td>
                  <td>{{ pr.name }}</td>
                  <td>{{ pr.quantity }}</td>
                  <td>{{ pr.category }}</td>
                  <td>{{ formatPrice(pr.price) }}</td>
                  <td class="actions">
                    <button class="icon" @click="addToCartFromPromo(pr)" title="Ajouter au panier">🛒</button>
                    <button class="icon danger" @click="deletePromo(pr.id)" title="Supprimer">🗑️</button>
                  </td>
                </tr>
                <tr v-if="filteredPromotions.length === 0">
                  <td colspan="6" class="empty">Aucune promotion trouvée.</td>
                </tr>
              </tbody>
            </table>
          </div>

          
          <div v-if="activeTab === 'addEvent' || activeTab === 'listEvent'" class="panel panel-dark">
            <h3 v-if="activeTab === 'addEvent'">Ajouter événement (à compléter)</h3>
            <h3 v-else>Liste événement (à compléter)</h3>
            <p>Fonctionnalité événement à pousser selon besoin.</p>
          </div>
        </section>
      </div>
    </main>

    <FooterSpecified />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import {useUserStore} from "@/stores/userStore.js";
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";

const useStore = useUserStore();

const placeholder = "/assets/placeholder.png"; 


const activeTab = ref("listProduct");
const searchTerm = ref("");
const filterCategory = ref("");


const categories = ["Dessert", "Breakfast", "Dinner", "Salade", "Drink"];


const products = ref(JSON.parse(localStorage.getItem("products") || "[]"));
const promotions = ref(JSON.parse(localStorage.getItem("promotions") || "[]"));


const newProduct = ref({
  id: null,
  name: "",
  description: "",
  category: categories[0],
  price: 0,
  imageData: null, 
});


const newPromo = ref({
  id: null,
  name: "",
  description: "",
  productId: "",
  quantity: 1,
  price: 0,
  imageData: null,
});


function saveProducts() {
  localStorage.setItem("products", JSON.stringify(products.value));
  store.addNotification("Liste produits mise à jour");
}
function savePromotions() {
  localStorage.setItem("promotions", JSON.stringify(promotions.value));
  store.addNotification("Liste promotions mise à jour");
}


function onProductImage(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (ev) => (newProduct.value.imageData = ev.target.result);
  reader.readAsDataURL(file);
}

function onPromoImage(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (ev) => (newPromo.value.imageData = ev.target.result);
  reader.readAsDataURL(file);
}


function addProduct() {
  if (!newProduct.value.name) return alert("Nom requis");
  const p = {
    id: Date.now(),
    name: newProduct.value.name,
    description: newProduct.value.description,
    category: newProduct.value.category,
    price: Number(newProduct.value.price),
    image: newProduct.value.imageData || "",
  };
  products.value.unshift(p);
  // sauvegarde globale
  saveProducts();

  // mettre à dispo dans le store pour HomeUserView / etc.
  if (!store.products) store.products = [];
  store.products.unshift(p);
  localStorage.setItem("store_products", JSON.stringify(store.products));

  // reset
  Object.assign(newProduct.value, { id: null, name: "", description: "", category: categories[0], price: 0, imageData: null });
  store.addNotification(`Produit "${p.name}" ajouté`);
}

// ajouter une  promotion
function addPromotion() {
  if (!newPromo.value.name) return alert("Nom promo requis");
  const linkedProd = products.value.find(p => p.id === Number(newPromo.value.productId));
  const pr = {
    id: Date.now(),
    name: newPromo.value.name,
    description: newPromo.value.description,
    productId: newPromo.value.productId || (linkedProd ? linkedProd.id : ""),
    quantity: Number(newPromo.value.quantity),
    price: Number(newPromo.value.price) || (linkedProd ? linkedProd.price : 0),
    category: linkedProd ? linkedProd.category : (categories[0]),
    image: newPromo.value.imageData || (linkedProd ? linkedProd.image : ""),
  };
  promotions.value.unshift(pr);
  savePromotions();

  // reset
  Object.assign(newPromo.value, { id: null, name: "", description: "", productId: "", quantity: 1, price: 0, imageData: null });
  store.addNotification(`Promotion "${pr.name}" ajoutée`);
}

// filters pour filtrer par catégorie 
const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const term = searchTerm.value.trim().toLowerCase();
    if (filterCategory.value && p.category !== filterCategory.value) return false;
    if (!term) return true;
    return p.name.toLowerCase().includes(term) || (p.description && p.description.toLowerCase().includes(term));
  });
});
const filteredPromotions = computed(() => {
  const term = searchTerm.value.trim().toLowerCase();
  return promotions.value.filter(pr => {
    if (filterCategory.value && pr.category !== filterCategory.value) return false;
    if (!term) return true;
    return pr.name.toLowerCase().includes(term) || (pr.description && pr.description.toLowerCase().includes(term));
  });
});


function addToCartFromAdmin(item) {
  const cartItem = { id: item.id, name: item.name, price: item.price, qty: 1, image: item.image, description: item.description || "" };
  store.addToCart(cartItem);
}

function addToCartFromPromo(promo) {
  const cartItem = { id: `promo-${promo.id}`, name: promo.name, price: promo.price, qty: 1, image: promo.image, description: promo.description || '' };
  store.addToCart(cartItem);
}

function deleteProduct(id) {
  if (!confirm('Supprimer ce produit ?')) return;
  products.value = products.value.filter(p => p.id !== id);
  saveProducts();
  store.addNotification('Produit supprimé');
}

function deletePromo(id) {
  if (!confirm( "Supprimer cette promotion ?")) return;
  promotions.value = promotions.value.filter(p => p.id !== id);
  savePromotions();
  store.addNotification("Promotion supprimée");
}

function editProduct(p) {
  
  newProduct.value = { ...p, imageData: p.image };
  activeTab.value = "addProduct";
  store.addNotification("Édition : modifiez puis cliquez 'Ajouter' (cela créera une nouvelle version).");
}

function clearFilters() {
  searchTerm.value = "";
  filterCategory.value = "";
}

function formatPrice(v) {
  if (v === null || v === undefined) return "-";
  
  const symbol = store.currencySymbol || "$";
  return `${symbol}${Number(v).toFixed(2)}`;
}


onMounted(() => {
  
  if (store.products && Array.isArray(store.products) && store.products.length) {
    products.value = [...store.products];
  } else {
    localStorage.setItem("products", JSON.stringify(products.value));
  }
  if (store.promotions && Array.isArray(store.promotions) && store.promotions.length) {
    promotions.value = [...store.promotions];
  } else {
    localStorage.setItem("promotions", JSON.stringify(promotions.value));
  }
});
</script>

<style scoped>
@import "@/assets/site.css";

.admin-main {
  padding: 30px 0;
}

.layout {
  display: flex;
  gap: 20px;
}


.admin-sidebar {
  width: 220px;
  background: #d6c7a6;
  border-radius: 8px;
  padding: 18px;
}
.admin-sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.admin-sidebar li {
  padding: 12px 10px;
  cursor: pointer;
  color: #222;
  border-radius: 6px;
  margin-bottom: 6px;
}
.admin-sidebar li.active {
  background: #cfbd97;
  color: white;
}


.admin-content {
  flex: 1;
}


.top-controls {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 18px;
}
.search {
  flex: 1;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
.select {
  padding: 10px;
  border-radius: 8px;
}


.panel {
  border-radius: 12px;
  padding: 18px;
  margin-bottom: 18px;
}
.panel-dark {
  background: #111;
  color: #fff;
}


.add-grid {
  display: flex;
  gap: 18px;
}
.image-upload {
  width: 200px;
}
.img-placeholder {
  background: rgba(255,255,255,0.05);
  height: 140px;
  display:flex;
  align-items:center;
  justify-content:center;
  border: 1px dashed rgba(255,255,255,0.08);
  border-radius:6px;
  cursor:pointer;
  color:#fff;
}

.img-placeholder input { 
    display:none; 
}

.img-preview img { 
    width:200px; 
    height:140px; 
    object-fit:cover; 
    border-radius:6px; 
}


.form label { 
    display:block; 
    margin-top:8px; 
    color:#ddd; 
}

.form input, .form textarea, .form select {
  width:100%;
  padding:8px;
  border-radius:8px;
  border: none;
  margin-top:6px;
}

.row { 
    display:flex; 
    gap:12px; 
    margin-top:8px; 
}

.col { flex:1; }


.admin-table {
  width:100%;
  border-collapse: collapse;
  margin-top:12px;
}
.admin-table th, .admin-table td {
  padding: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  text-align: left;
  color: #ddd;
}
.admin-table th { 
    color:#bbb; 
}

.img-cell img { 
    width:56px; 
    height:56px; 
    object-fit:cover; 
    border-radius:6px; 
}


.actions { 
    display:flex; 
    gap:8px; 
    justify-content:flex-end; 
}

.icon { 
    background:transparent; 
    border:none; 
    color:#fff; 
    cursor:pointer; 
    padding:6px; 
    border-radius:6px; 
}
.icon.danger { 
    color:#e74c3c; 
}


.empty { 
    text-align:center; 
    padding:16px; 
    color:#ccc; 
}


@media (max-width: 900px) {
  .layout { flex-direction: column; }
  .admin-sidebar { width: 100%; display:flex; overflow-x:auto; }
  .add-grid { flex-direction:column; }
}
</style>
