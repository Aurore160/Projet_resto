<template>
    <section class="hero-section">
      <div class="hero-image">
        <img src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\premium_photo-1748876812099-3fb134c3d319.avif" >
        <div class="hero-content">
          <h1 class="hero-title">COMMUNAUTE.</h1>
        </div>
      </div>
    </section>
    <section class="featured-section">
      <div class="container">
        <!-- Premier feature - Image à gauche -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6">
            <img src="c:\Users\sanru\OneDrive\Images\c26bd66f2dbb0eb16b47d7e16d7f77d1.jpg" class="feature-image img-fluid">
          </div>
          <div class="col-md-6">
            <div class="feature-content">
              <h3 class="feature-title">La Naissance d’un Mythe : Le Poulet de Grand-Mère (1974)</h3>
              <p class="feature-description">
                Tout commence avec un geste simple, presque ancestral. En 1974, Marie, notre fondatrice, souhaitait offrir à ses clients le réconfort d’un repas du dimanche, tous les jours de la semaine. Son « Poulet Grand-Mère » – un demi-poulet rôti lentement, accompagné de ses pommes de terre fondantes qui cuisent dans le jus de la volaille – est devenu instantanément une star.

                Son secret ? La simplicité et la régularité. La même marinade à base d’huile d’olive, de citron et d’un bouquet d’herbes secret. La même cuisson lente au four, qui permet à la chair de rester incroyablement juteuse sous une peau dorée et croustillante. Ce plat, réconfortant et généreux, a été le premier point de ralliement, le premier sujet de conversation de ceux qui allaient devenir les fidèles, les ambassadeurs, la famille Miam Miam.
              </p>
            </div>
          </div>
        </div>

        <!-- Quatrième feature - Image à droite -->
        <div class="feature-item row align-items-center mb-5">
          <div class="col-md-6 order-md-2">
            <img src="c:\Users\sanru\OneDrive\Images\83ec6bc7d8544224e31c2a63db998129.jpg" alt="Pâtes" class="feature-image img-fluid">
          </div>
          <div class="col-md-6 order-md-1">
            <div class="feature-content">
              <h3 class="feature-title">Cuisine Internationale</h3>
              <p class="feature-description">
                Au fil des ans, le Poulet Grand-Mère n’était plus seulement un plat, c’était une expérience partagée. Les clients revenaient pour lui, en parlaient à leurs amis, l’imposaient en rite à leurs nouveaux collègues. C’est alors qu’est née, naturellement, la première itération de la Communauté.

                Sur le livre d’or, les commentaires s’accumulaient : « La meilleure aile de poulet de ma vie ! », « Je viendrais bien tous les jours pour ce poulet ! ». Nous avons alors lancé la première carte de fidélité « Poulet-Addict », offrant son dixième poulet offert. C’était bien plus qu’une opération marketing ; c’était la reconnaissance officielle d’une famille de gourmands.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
</template>

<script setup>

</script>

<style scoped>
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

/* Featured Sections */
.featured-section {
  padding: 4rem 0;
  background-color: var(--primary-color);
}

.feature-item {
  margin-bottom: 5rem;
}

.feature-image {
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
  transition: transform 0.3s ease;
}

.feature-item:hover .feature-image {
  transform: scale(1.1);
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
  color: var(--fin-color);
}

.feature-list li:before {
  content: "✓";
  position: absolute;
  left: 0;
  color: var(--fin-color);
  font-weight: bold;
  font-size: 1.2rem;
}

.btn-feature {
  background-color: var(--secondary-color);
  color: var(--primary-color);
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
  color: var(--secondary-color);
}
</style>
