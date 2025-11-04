<template>
  <div class="home-page">
    <!-- Header -->
    <header class="header-section">
      <nav class="navbar navbar-expand-lg">
        <div class="container">
          <!-- Logo -->
          <a class="navbar-brand" href="#">
            <span class="logo-text">Miam Miam</span>
          </a>

          <!-- Boutons connexion/inscription -->
          <div class="header-buttons">
            <button class="btn btn-custom btn-login">Connexion</button>
            <button class="btn btn-custom btn-signup">Inscription</button>
          </div>
        </div>
      </nav>
    </header>

    <!-- Hero Section avec image -->
    <section class="hero-section">
      <div class="hero-image">
        <img src="/src/images/hero-image.jpg" alt="Plat principal" class="img-fluid">
        <div class="hero-content">
          <h1 class="hero-title">Bienvenu(e)</h1>
          <h2 class="hero-subtitle">Découvrez nos délices culinaires</h2>
          <p class="hero-text">Des plats préparés avec passion pour vos papilles</p>
          <div class="hero-buttons">
            <button class="btn btn-hero btn-primary">Commander maintenant</button>
            <button class="btn btn-hero btn-secondary">Voir le menu</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Catégories avec images rondes -->
    <section class="categories-section">
      <div class="container">
        <h2 class="section-title">Nos Catégories</h2>
        <div class="row g-4">
          <div v-for="category in categories" :key="category.id" class="col-6 col-md-3">
            <div class="category-card text-center">
              <div class="category-image">
                <img :src="category.image" :alt="category.name" class="rounded-circle">
              </div>
              <h5 class="category-name">{{ category.name }}</h5>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Plats en box -->
    <section class="dishes-section">
      <div class="container">
        <h2 class="section-title">Nos Plats Populaires</h2>
        <div class="row g-4">
          <div v-for="dish in dishes" :key="dish.id" class="col-md-6 col-lg-4">
            <div class="dish-card">
              <img :src="dish.image" :alt="dish.name" class="dish-image">
              <div class="dish-content">
                <h5 class="dish-name">{{ dish.name }}</h5>
                <p class="dish-description">{{ dish.description }}</p>
                <div class="dish-footer">
                  <span class="dish-price">{{ dish.price }} FC</span>
                  <button class="btn btn-add">+</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section alternée image/description -->
    <section class="featured-section">
      <div class="container">
        <!-- Premier feature - Image à gauche -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6">
            <img src="/src/images/burger.jpg" alt="Plat spécial" class="feature-image img-fluid">
          </div>
          <div class="col-md-6">
            <div class="feature-content">
              <h3 class="feature-title">Notre Spécialité Maison</h3>
              <p class="feature-description">
                Découvrez notre plat signature préparé avec des ingrédients frais 
                et locaux. Une explosion de saveurs qui ravira vos papilles.
              </p>
              <ul class="feature-list">
                <li>Ingrédients 100% frais</li>
                <li>Préparation traditionnelle</li>
                <li>Saveurs authentiques</li>
              </ul>
              <button class="btn btn-feature">Découvrir</button>
            </div>
          </div>
        </div>

        <!-- Deuxième feature - Image à droite -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6 order-md-2">
            <img src="/src/images/voiture.jpg" alt="Dessert spécial" class="feature-image img-fluid">
          </div>
          <div class="col-md-6 order-md-1">
            <div class="feature-content">
              <h3 class="feature-title">Desserts Maison</h3>
              <p class="feature-description">
                Nos desserts sont préparés quotidiennement par nos pâtissiers 
                experts. Des douceurs qui termineront votre repas en beauté.
              </p>
              <ul class="feature-list">
                <li>Pâtisseries fraîches</li>
                <li>Recettes traditionnelles</li>
                <li>Ingrédients de qualité</li>
              </ul>
              <button class="btn btn-feature">Voir les desserts</button>
            </div>
          </div>
        </div>

        <!-- Troisième feature - Image à gauche -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6">
            <img src="/src/images/fumbwa.jpg" alt="Plat spécial" class="feature-image img-fluid">
          </div>
          <div class="col-md-6">
            <div class="feature-content">
              <h3 class="feature-title">Plats Traditionnels</h3>
              <p class="feature-description">
                Nos plats traditionnels sont préparés selon des recettes authentiques 
                transmises de génération en génération.
              </p>
              <ul class="feature-list">
                <li>Recettes ancestrales</li>
                <li>Saveurs authentiques</li>
                <li>Ingrédients locaux</li>
              </ul>
              <button class="btn btn-feature">Découvrir</button>
            </div>
          </div>
        </div>

        <!-- Quatrième feature - Image à droite -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6 order-md-2">
            <img src="/src/images/tshaka-complet.jpg" alt="Pâtes" class="feature-image img-fluid">
          </div>
          <div class="col-md-6 order-md-1">
            <div class="feature-content">
              <h3 class="feature-title">Cuisine Internationale</h3>
              <p class="feature-description">
                Voyagez à travers le monde avec nos plats internationaux préparés 
                par nos chefs expérimentés.
              </p>
              <ul class="feature-list">
                <li>Saveurs du monde</li>
                <li>Techniques modernes</li>
                <li>Présentation raffinée</li>
              </ul>
              <button class="btn btn-feature">Explorer</button>
            </div>
          </div>
        </div>

        <!-- Cinquième feature - Image à gauche -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6">
            <img src="/src/images/oseille.jpg" alt="Dessert" class="feature-image img-fluid">
          </div>
          <div class="col-md-6">
            <div class="feature-content">
              <h3 class="feature-title">Atelier Pâtisserie</h3>
              <p class="feature-description">
                Nos pâtissiers créent des desserts uniques qui émerveilleront 
                vos sens et termineront votre repas en beauté.
              </p>
              <ul class="feature-list">
                <li>Créations originales</li>
                <li>Produits de saison</li>
                <li>Présentation artistique</li>
              </ul>
              <button class="btn btn-feature">Voir les desserts</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer amélioré -->
    <footer class="footer-section">
      <div class="container">
        <div class="footer-content">
          <div class="row g-5">
            <div class="col-lg-4">
              <div class="footer-brand">
                <h4 class="footer-title">Miam Miam</h4>
                <p class="footer-text">
                  Votre restaurant de confiance pour des moments culinaires inoubliables. 
                  Nous nous engageons à vous offrir les meilleures saveurs.
                </p>
                <div class="social-links">
                  <a href="#" class="social-link">📘</a>
                  <a href="#" class="social-link">📷</a>
                  <a href="#" class="social-link">🐦</a>
                </div>
              </div>
            </div>
            
            <div class="col-lg-2">
              <h5 class="footer-heading">Navigation</h5>
              <ul class="footer-links">
                <li><a href="#" class="footer-link">Accueil</a></li>
                <li><a href="#" class="footer-link">Menu</a></li>
                <li><a href="#" class="footer-link">À propos</a></li>
                <li><a href="#" class="footer-link">Contact</a></li>
                <li><a href="#" class="footer-link">Promotions</a></li>
              </ul>
            </div>
            
            <div class="col-lg-3">
              <h5 class="footer-heading">Contact</h5>
              <div class="contact-info">
                <div class="contact-item">
                  <span class="contact-icon">📞</span>
                  <span class="contact-text">+243 01 23 45 67</span>
                </div>
                <div class="contact-item">
                  <span class="contact-icon">✉️</span>
                  <span class="contact-text">contact@miammiam.ci</span>
                </div>
                <div class="contact-item">
                  <span class="contact-icon">📍</span>
                  <span class="contact-text">Kinshasa, RDC</span>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3">
              <h5 class="footer-heading">Horaires</h5>
              <div class="opening-hours">
                <div class="hours-item">
                  <span class="days">Lun - Ven</span>
                  <span class="hours">8h - 22h</span>
                </div>
                <div class="hours-item">
                  <span class="days">Samedi</span>
                  <span class="hours">9h - 23h</span>
                </div>
                <div class="hours-item">
                  <span class="days">Dimanche</span>
                  <span class="hours">9h - 20h</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="footer-bottom">
          <div class="footer-divider"></div>
          <div class="footer-copyright">
            <p>&copy; 2024 Miam Miam. Tous droits réservés. | Conçu avec amour pour les amateurs de bonne cuisine</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// Données des catégories
const categories = ref([
  { id: 1, name: 'Desserts', image: '/src/images/desserts.jpg' },
  { id: 2, name: 'Breakfast', image: '/src/images/breakfast.jpg' },
  { id: 3, name: 'Take Away', image: '/src/images/takeaway.jpg' },
  { id: 4, name: 'Salades', image: '/src/images/salades.jpg' },
  { id: 5, name: 'Drinks', image: '/src/images/drinks.jpg' },
  { id: 6, name: 'Plats Principaux', image: '/src/images/plats.jpg' },
  { id: 7, name: 'Grillades', image: '/src/images/poulet-braise.jpg' },
  { id: 8, name: 'Pâtes', image: '/src/images/pates-carbonara.jpg' }
])

// Données des plats
const dishes = ref([
  {
    id: 1,
    name: 'Poulet Braisé',
    description: 'Poulet braisé accompagné d\'alloco et riz',
    price: '6,500',
    image: '/src/images/poulet-braise.jpg'
  },
  {
    id: 2,
    name: 'Poisson Grillé',
    description: 'Poisson frais grillé avec plantain et légumes',
    price: '7,500',
    image: '/src/images/poisson-grille.jpg'
  },
  {
    id: 3,
    name: 'Salade César',
    description: 'Salade fraîche avec poulet grillé et sauce césar',
    price: '4,500',
    image: '/src/images/salade-cesar.jpg'
  },
  {
    id: 4,
    name: 'Pâtes Carbonara',
    description: 'Pâtes fraîches avec sauce carbonara crémeuse',
    price: '5,500',
    image: '/src/images/pates-carbonara.jpg'
  },
  {
    id: 5,
    name: 'Burger Maison',
    description: 'Burger avec viande hachée et frites croustillantes',
    price: '4,800',
    image: '/src/images/burger.jpg'
  },
  {
    id: 6,
    name: 'Tiramisu',
    description: 'Dessert italien au café et mascarpone',
    price: '2,800',
    image: '/src/images/tiramisu.jpg'
  }
])
</script>

<style scoped>
/* Variables de couleurs */
:root {
  --primary-color: #E4DBC6;
  --secondary-color: #000000;
  --text-color: #333333;
  --white: #ffffff;
  --accent-color: #8B4513;
}

.home-page {
  background-color: var(--primary-color);
  font-family: 'Arial', sans-serif;
}

/* Header */
.header-section {
  background-color: var(--primary-color);
  padding: 0.2rem 0;
  border-bottom: 2px solid #d4c9ac;
  /* box-shadow: 0 2px 10px rgba(239, 237, 237, 0.1); */
}

.navbar-brand .logo-text {
  /* font-size: 2.5rem; */
  font-weight: bold;
  /* color: var(--white); */
  text-transform: uppercase;
  letter-spacing: 1px;
}

.header-buttons {
  display: flex;
  gap: 1.5rem;
  color: #e2dede;
}

.btn-custom {
  border-radius: 50px;
  padding: 0.75rem 2.5rem;
  font-weight: bold;
  font-size: 1rem;
  color: #ffffff;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}


.btn-custom:hover {
  transform: scale(1.05);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Hero Section - Agrandie à 50% */
.hero-section {
  margin-bottom: 4rem;
}

.hero-image {
  position: relative;
  height: 80vh;
  overflow: hidden;
}

.hero-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.7);
}

.hero-content {
  position: absolute;
  top: 50%;
  left: 10%;
  transform: translateY(-50%);
  text-align: left;
  color: var(--white);
  max-width: 600px;
  padding: 2rem;
}

.hero-title {
  font-size: 4rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  /*text-shadow: 3px 3px 6px rgba(0,0,0,0.8);*/
  color: #e2dede;
  line-height: 1.1;
}

.hero-subtitle {
  font-size: 2.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
 color: #e2dede;
  
}

.hero-text {
  font-size: 1.3rem;
  margin-bottom: 2.5rem;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
  color: #e2dede;
  line-height: 1.6;
}

.hero-buttons {
  display: flex;
  gap: 1.5rem;
  justify-content: flex-start;
  color: #e2dede;
}

.btn-hero {
  border-radius: 50px;
  padding: 1rem 2.5rem;
  font-weight: bold;
  font-size: 1.1rem;
  min-width: 220px;
  border: 3px solid var(--primary-color);
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.btn-primary {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.btn-secondary {
  background-color: var(--primary-color);
  color: var(--white);
  border-color: var(--white);
}

.btn-hero:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

/* Catégories avec effet d'agrandissement */
.categories-section {
  padding: 4rem 0;
  background-color: var(--white);
}

.section-title {
  text-align: center;
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 3rem;
  color: var(--secondary-color);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.category-card {
  padding: 1.5rem;
  transition: all 0.4s ease;
}

.category-card:hover {
  transform: translateY(-10px);
}

.category-image {
  margin-bottom: 1.5rem;
  transition: all 0.4s ease;
}

.category-image img {
  width: 140px;
  height: 140px;
  object-fit: cover;
  border: 4px solid var(--primary-color);
  transition: all 0.4s ease;
}

.category-card:hover .category-image img {
  transform: scale(1.15);
  border-color: var(--accent-color);
}

.category-name {
  font-weight: bold;
  color: var(--secondary-color);
  margin: 0;
  font-size: 1.1rem;
  transition: color 0.3s ease;
}

.category-card:hover .category-name {
  color: var(--accent-color);
}

/* Plats */
.dishes-section {
  padding: 4rem 0;
  background-color: var(--primary-color);
}

.dish-card {
  background: var(--white);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  transition: all 0.4s ease;
  border: 2px solid transparent;
}

.dish-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
  border-color: var(--accent-color);
}

.dish-image {
  width: 100%;
  height: 220px;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.dish-card:hover .dish-image {
  transform: scale(1.05);
}

.dish-content {
  padding: 2rem;
}

.dish-name {
  font-weight: bold;
  color: var(--secondary-color);
  margin-bottom: 0.75rem;
  font-size: 1.3rem;
}

.dish-description {
  color: #666;
  font-size: 1rem;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.dish-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dish-price {
  font-weight: bold;
  color: var(--accent-color);
  font-size: 1.4rem;
}

.btn-add {
  background-color: var(--secondary-color);
  color: var(--white);
  border: none;
  border-radius: 50%;
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  font-weight: bold;
  transition: all 0.3s ease;
}

.btn-add:hover {
  background-color: var(--accent-color);
  transform: scale(1.1);
}

/* Featured Sections */
.featured-section {
  padding: 4rem 0;
  background-color: var(--white);
}

.feature-item {
  margin-bottom: 5rem;
}

.feature-image {
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
  transition: transform 0.4s ease;
}

.feature-item:hover .feature-image {
  transform: scale(1.02);
}

.feature-content {
  padding: 3rem;
}

.feature-title {
  font-size: 2.2rem;
  font-weight: bold;
  color: var(--secondary-color);
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.feature-description {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 2rem;
  line-height: 1.7;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin-bottom: 2.5rem;
}

.feature-list li {
  padding: 0.75rem 0;
  position: relative;
  padding-left: 2rem;
  font-size: 1.1rem;
  color: #555;
}

.feature-list li:before {
  content: "✓";
  position: absolute;
  left: 0;
  color: var(--accent-color);
  font-weight: bold;
  font-size: 1.2rem;
}

.btn-feature {
  background-color: var(--secondary-color);
  color: var(--white);
  border: none;
  border-radius: 50px;
  padding: 1rem 2.5rem;
  font-weight: bold;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.btn-feature:hover {
  background-color: var(--accent-color);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(62, 60, 60, 0.2);
}

/* Footer amélioré */
.footer-section {
  background: linear-gradient(135deg, #5c5b5b 0%, #525252 100%);
  color: var(--white);
  padding: 4rem 0 1rem;
}

.footer-content {
  margin-bottom: 3rem;
}

.footer-title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 1.5rem;
  color: var(--primary-color);
}

.footer-text {
  color: #ccc;
  line-height: 1.6;
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 45px;
  height: 45px;
  background: var(--primary-color);
  color: var(--secondary-color);
  border-radius: 50%;
  text-decoration: none;
  font-size: 1.2rem;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: var(--accent-color);
  color: var(--white);
  transform: translateY(-3px);
}

.footer-heading {
  font-size: 1.3rem;
  font-weight: bold;
  margin-bottom: 1.5rem;
  color: var(--primary-color);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.footer-links {
  list-style: none;
  padding: 0;
}

.footer-link {
  color: #ccc;
  text-decoration: none;
  display: block;
  padding: 0.5rem 0;
  transition: all 0.3s ease;
  font-size: 1.1rem;
}

.footer-link:hover {
  color: var(--primary-color);
  padding-left: 0.5rem;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.contact-icon {
  font-size: 1.2rem;
}

.contact-text {
  color: #ccc;
  font-size: 1.1rem;
}

.opening-hours {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hours-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #444;
}

.days {
  color: #ccc;
  font-size: 1.1rem;
}

.hours {
  color: var(--primary-color);
  font-weight: bold;
  font-size: 1.1rem;
}

.footer-bottom {
  margin-top: 3rem;
}

.footer-divider {
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
  margin-bottom: 2rem;
}

.footer-copyright {
  text-align: center;
  color: #999;
  font-size: 1rem;
}

.footer-copyright p {
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-subtitle {
    font-size: 1.8rem;
  }
  
  .hero-content {
    left: 5%;
    padding: 1rem;
  }
  
  .hero-buttons {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-buttons {
    flex-direction: column;
    gap: 1rem;
  }
  
  .btn-hero {
    min-width: 200px;
  }
}
</style>