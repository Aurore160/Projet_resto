<template>
  <div class="promo-page">
    <HeaderSpecified />
    <section class="promo-grid">
      <article v-for="promo in promos" :key="promo.id" class="promo-card">
        <img :src="promo.image" alt="" />
        <div class="promo-info">
          <h3>{{ promo.name }}</h3>
          <p>{{ promo.description }}</p>
          <div class="price-row">
            <span class="old">{{ promo.oldPrice }}</span>
            <span class="new">{{ promo.newPrice }}</span>
            <button @click="addPromo(promo)">Ajouter au panier</button>
          </div>
        </div>
      </article>
    </section>
    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore} from "@/stores/userStore.js";

import miamiaImage from "@/assets/left1.jpg";
import parkingImage from "@/assets/right1.jpg";
import promoImage from "@/assets/left2.jpg";
import quizImage from "@/assets/right2.jpg"; 

const usestore = useUserStore();

const promos = [
  { id: 101, name:"Promo Parking", description:"Session parking avec burger", oldPrice:"$12.00", newPrice:"$8.00", image: parkingImage },
  { id: 102, name:"Menu Duo", description:"Deux burgers + boisson", oldPrice:"$25.00", newPrice:"$18.00", image: promoImage },
  { id: 103, name:"Dessert Pack", description:"3 desserts maison", oldPrice:"$10.00", newPrice:"$6.50", image: quizImage },
  { id: 104, name:"Petit-Déj Promo", description:"Pancakes + café", oldPrice:"$9.00", newPrice:"$6.00", image: miamiaImage }
];

function addPromo(p) {
  
  const price = parseFloat(p.newPrice.replace("$",""));
  store.addToCart({ id:p.id, name:p.name, description:p.description, price:`$${price.toFixed(2)}`, image:p.image }, 1);
  alert(`${p.name} ajouté au panier`);
}
</script>

<style scoped>


.promo-grid{ 
    display:grid; 
    gap:16px; 
    max-width:1200px; 
    margin:24px auto; 
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); 
    padding:0 16px;
}

.promo-card{ 
    background:beige; 
    border-radius:12px; 
    overflow:hidden; 
    display:flex; 
    gap:12px; 
    padding:12px; 
    align-items:center; 
}

.promo-card img{ 
    width:120px; 
    height:120px; 
    object-fit:cover; 
    border-radius:8px; 
}

.price-row{ 
    display:flex; 
    gap:12px; 
    align-items:center;
}

.old{ 
    text-decoration:line-through; 
    color:#777; 
}

.new{ 
    color:var(--accent); 
    font-weight:700; 
}

.price-row button{ 
    margin-left:auto; 
    background:var(--accent); 
    color:#fff; 
    border:none; 
    padding:8px 12px; 
    border-radius:6px; 
    cursor:pointer; 
}


</style>