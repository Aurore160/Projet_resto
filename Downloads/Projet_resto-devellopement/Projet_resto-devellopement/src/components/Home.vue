<template>
  <section class="hero-section">
    <div class="hero-image">
      <img src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\571fff618531d22fa694c1e419b309f6.jpg">
      <div class="hero-content">
        <Logo class="go"/>
        <p>Depuis 1974, nous écrivons l'histoire des papilles. Ici, <br>on ne sert pas que des plats, on sert des émotions.
           Des produits frais, <br>une cuisine généreuse et une ambiance chaleureuse vous attendent. <br>Prêt à vivre une expérience gustative unique ?
          Votre table vous attend.</p>
        
        <!-- Bouton Menu ajouté -->
        <button class="menu-btn">
          MENU
        </button>
        
        <h1 class="hero-title">Bienvenue.</h1>
      </div>
    </div>
  </section>
</template>

<script setup>

</script>


<style scoped>
.go {
  font-size: 400%;
}

.hero-section {
  margin-bottom: 1rem;
}

.hero-image {
  position: relative;
  height: 100vh;
  overflow: hidden;
}

.hero-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.5);
}

.hero-content {
  position: absolute;
  top: 60%;
  left: 10%;
  transform: translateY(-50%);
  text-align: left;
  color: var(--white);
  max-width: 2600px;
  padding: 2rem;
  padding-top: 0.3rem;
}

.hero-title {
  font-size: 6.1rem;
  font-weight: 1000;
  margin-bottom: 0.5rem;
  color: #e2dede;
  line-height: 1.1;
}

.hero-text {
  font-size: 1.4rem;
  margin-bottom: 2.5rem;
  color: #e2dede;
  line-height: 1.6;
}

/* Styles pour le bouton Menu */
.menu-btn {
  background: transparent;
  color: #e2dede;
  border: 2px solid #e2dede;
  padding: 15px 40px;
  font-size: 1.2rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.3s ease;
  margin: 25px 0;
  position: relative;
  overflow: hidden;
}

.menu-btn:hover {
  background: #e2dede;
  color: #333;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.menu-btn:active {
  transform: translateY(0);
}

/* Version alternative avec fond coloré */

.menu-btn {
  background: var(--primary-color); /* Or */
  color: var(--secondary-color);
  border: none;
  padding: 15px 40px;
  font-size: 1.2rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.3s ease;
  margin: 25px 0;
}

.menu-btn:hover {
  background: var(--hover-color);
  transform: translateY(-2px);
}

</style>