<template>
  <div class="about-page">
    <Header />

    <main class="about-content">
      <!-- Première section -->
      <section class="about-block">
        <img src="@/assets/about2.jpg" alt="Restaurant intérieur" class="about-img" />
        <p class="about-text">
          Chez <strong>Zeduc-space</strong>, nous croyons que chaque plat doit être une expérience unique. 
          Nos burgers, nos boissons et nos desserts sont faits maison, avec des ingrédients frais 
          soigneusement sélectionnés pour offrir une saveur authentique et inoubliable.
        </p>
      </section>

      <!-- Deuxième section -->
      <section class="about-block">
        <img src="@/assets/about1.jpg" alt="Équipe du restaurant" class="about-img" />
        <p class="about-text">
          Notre équipe passionnée travaille chaque jour pour allier qualité, rapidité et convivialité. 
          Nous mettons un point d’honneur à accueillir nos clients dans une ambiance chaleureuse et moderne, 
          où chaque repas devient un moment de plaisir et de partage.
        </p>
      </section>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import Header from "@/component/Header.vue";
import Footer from "@/component/Footer.vue";
</script>

<style scoped>
@import "@/assets/site.css";

.about-page {
  background: rgba(207, 189, 151, 0.8);
}

.about-content {
  max-width: 1100px;
  margin: 32px auto;
  padding: 0 16px;
  display: flex;
  flex-direction: column;
  gap: 40px;
}


.about-block {
  background: beige;
  border-radius: 10px;
  padding-bottom: 20px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.about-img {
  width: 100%;
  height: 400px;
  object-fit: cover;
  display: block;
}

.about-text {
  color: var(--text-dark);
  font-size: 16px;
  line-height: 1.6;
  padding: 16px 24px;
  text-align: justify;
}


@media (max-width: 768px) {
  .about-img {
    height: 260px;
  }

  .about-text {
    font-size: 14px;
    padding: 14px 16px;
  }
}
</style>