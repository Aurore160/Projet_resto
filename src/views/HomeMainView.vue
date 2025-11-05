<template>
    <div class="home-wrap">
        <Header />
        
        <section class="hero">
            <div class="hero-content">
                <h1> Bienvenue </h1>
                <h3> Chez Zeduc-space </h3>
                <p> Les meilleurs burgers & plats maison - servi avec amour. </p>
                <div class="hero-actions">
                    <router-link to="/" class="btn transparent"> Se connecter </router-link>
                    <router-link to="/register" class="btn transparent"> S'inscrire </router-link>
                </div>
            </div>
        </section>

        <section class="tabs-section">
            <div class="tabs-row">
                <button v-for="t in tabs" :key="t.key" :class="['tab-btn', { active: activeTab === t.key }]" @click="activeTab = t.key">
                    <img :src="t.img" :alt="t.label">
                    <span>{{ t.label }}</span>
                </button>
            </div>

            <div class="tab-content">
                <div v-if="activeTabItems.length === 0" class="empty"> Aucun plat dans cette catégorie.</div>
                    <div class="menu-grid">
                        <article v-for="item in activeTabItems" :key="item.id" class="menu-card">
                            <img class="menu-img" :src="item.image" :alt="item.name" >
                            <div class="menu-info">
                                <h3 class="menu-title"> {{ item.name }}</h3>
                                <p class="menu-desc">{{ item.description }}</p>
                            </div>
                            <div class="menu-price">{{ item.price }}</div>
                        </article>
                    </div>
            </div>
        </section>

        <section class="intro">
            <p>
                Nous préparons chaque plat avec des ingrédients frais. Découvrez notre sélection du moment,
                et commandez directement depuis la page Menu.
            </p>
        </section>

        <section class="alternating">
            <div v-for="(block, idx) in alternatingBlocks" :key="idx" :class="['alt-row', { reverse: idx % 2 === 1}]">
                <img :src="block.image" alt="section image">
                <div class="alt-text">
                    <h3>{{ block.title }}</h3>
                    <p>{{ block.text }}</p>
                </div>
            </div>
        </section>
        <Footer />
    </div>
</template>

<script setup>

import { ref, computed } from "vue";
import Header from "@/component/Header.vue";
import Footer from "@/component/Footer.vue";
import drinkImg from "@/assets/drink.jpg";
import saladeImg from "@/assets/salade.jpg";
import dinnerImg from "@/assets/dinner.jpg";
import breakfastImg from "@/assets/breakfast.jpg";
import dessertsImg from "@/assets/dessert.jpg";

import drinkImage from "@/assets/margarita.jpg";
import saladeImage from "@/assets/brocoli.jpg";
import dinnerImage from "@/assets/poulet.jpg";
import breakfastImage from "@/assets/gaufre.jpg";
import dessertsImage from "@/assets/glace.jpg"; 

import miamiaImage from "@/assets/left1.jpg";
import parkingImage from "@/assets/right1.jpg";
import promoImage from "@/assets/left2.jpg";
import quizImage from "@/assets/right2.jpg"; 




const tabs = [
    { key: "drink", label: "Drink", img: drinkImg },
    { key: "salade", label: "Salade", img: saladeImg},
    { key: "dinner", label: "Dinner", img: dinnerImg},
    { key: "breakfast", label: "Breakfast", img: breakfastImg},
    { key: "desserts", label: "Desserts", img: dessertsImg}
];

const allItems = [
    {id:1, cat:"drink", name:"Margarita Menthe", description:"Midori-liqueur, Menthe, Citron", price: "$4.60", image: drinkImage },
    {id:2, cat:"salade", name:"Brocoli aux tomates fraîches", description:"brocoli, tomate, poivre jaune", price: "$6.80", image: saladeImage },
    {id:3, cat:"dinner", name:"Poulet frit avec pommes de terre", description:"Poulet, Pomme de terre", price: "$10.60", image: dinnerImage },
    {id:4, cat:"breakfast", name:"Gaufre à la fraise", description:"Gaufre, Chocolat, Fraise", price: "$2.60", image: breakfastImage },
    {id:5, cat:"desserts", name:"Glace à la fraise et au cacao", description:"Glace, Cacao, Fraise", price: "$1.60", image: dessertsImage}

];

const activeTab = ref("drink");

const activeTabItems = computed(() => allItems.filter(i => i.cat === activeTab.value));

const alternatingBlocks = [
    {image: miamiaImage , title: "MIA MIAM", text: "Chez Zeduc-space, Vous mangerez à votre satiété à moins prix. Goûtez aux saveurs succulantes de nos plats faits aux mains de nos meilleurs chefs"},
    {image: parkingImage , title: "Session Parking", text: "Savez-vous qu'avec Zeduc-space vous pouvez faire une fête entre amis ou en famille ? Oui, c'est possible Zeduc-space te livre tes plats funs jusqu'au lieu de la manifestation. Alors, n'hésitez surtout pas à placer votre confiance en Zeduc-space"},
    {image: promoImage , title: "Session Promo", text: "Tous les jours ne se ressemblent pas chez Zeduc-space, il y a des jours où ton ventre est plein à un prix réduit. Profitez des journées Promo de Zeduc-space en commençant par avoir un compte Zeduc-space. Attends-tu toujours le bon moment pour avoir un compte Zeduc-space? le bon momment c'est maintenant"},
    {image: quizImage , title: "Quiz time", text: "Ne vous arrêtez pas qu'à nourrir votre estomac et votre ventre. Zeduc-space nourrit également votre cerveau avce une session Quiz time pour gagner des points réutilisables pour vos achats. N'est-ce pas intérressant ?"}
];
</script>

<style scoped>

@import "@/assets/site.css";
.hero{
    height: 500px;
    background: url("@/assets/burger.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    
    display:flex;
    align-items:center;
    color: #fff;
    position:relative;
}
.hero-content{
  max-width:1200px;
  margin: 0 auto;
  padding: 20px;
}
.hero h1{ 
    font-size:68px; 
    margin-left: 0px; 
    color:#fff; 
    text-align: left;
    font-weight: bold;
    padding-bottom: 0px;
}
.hero p{ 
    margin:0 0 16px; 
    color:#fff; 
    text-align: left;
    font-weight: bold;
}

.hero h3 {
    color:#cfbd97;
    font-weight: bold;
    font-size:38px;
    margin-top: 0px;

}


.btn.transparent{
  background: rgba(255,255,255,0.14);
  color: #fff;
  padding:10px 16px;
  margin-right:12px;
  border-radius:28px;
  text-decoration:none;
  display:inline-block;
}


.tabs-section{ 
    max-width:1200px; 
    background-color: #f6f0e7;
    margin:24px auto; 
    padding: 0 16px; 
    color:#000;

}
.tabs-row{ 
    display:flex; 
    gap:14px; 
    margin-bottom:18px; 
    flex-wrap:wrap; 
    align-items:center; 
    color: #e6d7b8;
}
.tab-btn{
  display:flex;
  align-items:center;
  gap:10px;
  background:#d6c7a6;
  border-radius:40px;
  padding:8px 12px;
  border:none;
  cursor:pointer;
  box-shadow:0 2px 6px rgba(0,0,0,0.08);
  margin-top: 10px;
}
.tab-btn img{ width:48px; height:48px; object-fit:cover; border-radius:50%; border:3px solid #fff; }
.tab-btn.active{ outline:3px solid #d6c7a6; }


.menu-grid{
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap:14px;
  background-color: #f6f0e7;
  
}
.menu-card{
  background: #d6c7a6;
  padding:12px;
  border-radius:12px;
  display:grid;
  grid-template-columns: 96px 1fr;
  grid-template-rows: auto 1fr;
  gap:8px;
  align-items:start;
  position:relative;
}
.menu-img{ width:96px; height:96px; object-fit:cover; border-radius:10px; grid-row:1 / span 2; }
.menu-title{ margin:0; font-size:16px; }
.menu-desc{ color:#1c1c1c; font-size:13px; margin-top:6px; }
.menu-price{ position:absolute; bottom:8px; right:12px; font-weight:700; color:#000; }


.intro{ max-width:1200px; margin: 18px auto; padding:0 16px; color:#2b2b2b; }


.alternating{ 
    max-width:1200px; 
    margin:24px auto; 
    padding:0 16px; 
    display:flex; 
    flex-direction:column; 
    gap:18px; }
.alt-row{ 
    display:flex; 
    gap:18px; 
    align-items:center; 
    background:#f6f0e7; 
    padding:18px; 
    border-radius:12px; }

.alt-row img{ 
    width:48%; 
    height:240px; 
    object-fit:cover; 
    border-radius:8px; }

.alt-text{ 
    width:52%; 
    padding:8px; 
    color:#222; }
.alt-row.reverse{ 
    flex-direction: row-reverse; }


@media (max-width: 900px){
  .hero{ 
    height:260px; 
}
  .hero h1{ 
    font-size:28px; 
}
  
    .alt-row img{ 
    width:45%; 
    height:180px; 
}
  .menu-card{ 
    grid-template-columns:80px 1fr; 
    grid-template-rows:auto 1fr; 
}
.menu-title {
    font-size: 14px;
    padding-left: 10px;
}
.menu-desc {
        padding-left: 10px;
    }
}
@media (max-width: 600px){
  .tabs-row{ 
    justify-content:center; 
}
  .menu-grid{ 
    grid-template-columns: 1fr; 
}
  .alt-row{ 
    flex-direction:column; 
}
  .alt-row.reverse{ 
    flex-direction:column; 
}
  .alt-row img{ 
    width:100%; 
    height:220px; 
}

    .menu-title {
    font-size: 14px;
    padding-left: 10px;
}
    .menu-desc {
        padding-left: 10px;
    }
}
</style>