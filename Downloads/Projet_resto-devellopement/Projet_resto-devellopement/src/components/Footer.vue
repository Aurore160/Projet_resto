<template>
  <footer class="text-light py-5">
    <div class="container">
      <!-- Section supérieure -->
      <div class="row align-items-center mb-5">
        <div class="col-md-8 text-center text-md-start">
          <h2 class="text fw-semibold mb-3">
            MIAM MIAM <br class="text d-none d-md-block" /> ZEDUC.
          </h2>
        </div>
        <div class="col-md-1 text-center text-md-end">
           <Logo2 />
        </div>
      </div>

      <hr class="border-secondary" />

      <!-- Section inférieure -->
      <div class="row mt-4">
        <!-- Reseaux--> 
        <div class="col-md-3 mb-4">
          <h5 class="fw-bold text">Reseaux</h5>
          <p class="text small mt-2">
            Consultez
          </p>
          <div class="mt-3">
            <a href="https://global.kfc.com/chicken-and-nutrition" class="text me-3"><i class="fab fa-facebook-f"></i></a>
            <a href="https://global.kfc.com/chicken-and-nutrition" class="text me-3"><i class="fab fa-twitter"></i></a>
            <a href="https://global.kfc.com/chicken-and-nutrition" class="text me-3"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://global.kfc.com/chicken-and-nutrition" class="text"><i class="fab fa-pinterest"></i></a>
          </div>
        </div>

        <!-- Menus -->
        <div 
          v-for="(menu, index) in menus" 
          :key="index"
          :class="menu.columnClass || 'col-6 col-md-2 mb-4'"
        >
          <h6 class="fw-semibold text">{{ menu.title }}</h6>
          <ul class="list-unstyled small mt-3">
            <li v-for="(item, itemIndex) in menu.items" :key="itemIndex">
              <a 
                :href="item.url" 
                class="text text-decoration-none d-block mb-2"
              >
                {{ item.text }}
              </a>
            </li>
          </ul>
        </div>


        <!-- Impact -->
        <div class="col-6 col-md-2 mb-4">
          <h6 class="fw-semibold text">Impact</h6>
          <ul class="list-unstyled small mt-3">
            <li><a href="#" class="text text-decoration-none d-block mb-2">Qui sommes-nous</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">Communaute</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2"></a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2"></a></li>
            <li><a href="#" class="text text-decoration-none d-block"></a></li>
          </ul>
        </div>

        <!-- Adresses -->
        <div class="col-6 col-md-2 mb-4">
          <h6 class="fw-semibold text">Adresses</h6>
          <ul class="list-unstyled small mt-3">
            <li><a href="#" class="text text-decoration-none d-block mb-2">Wenge Q/RIGHINI</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">C/LEMBA  Kinshasa</a></li>
            <li><a href="#" class="text text-decoration-none d-block">Rd.congo</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">+243 829815468</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">Zeduc@gmail.com</a></li>
          </ul>
        </div>

        <!-- Horaires -->
        <div class="col-6 col-md-3 mb-4">
          <h6 class="fw-semibold text">Horaires</h6>
          <ul class="list-unstyled small mt-3">
            <li><a href="#" class="text text-decoration-none d-block mb-2">Lun-Ven &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   8h-22h</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">Samedi &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    9h-21h</a></li>
            <li><a href="#" class="text text-decoration-none d-block mb-2">Dimanche &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  10h-20h</a></li>
          </ul>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const props = defineProps({
  menus: {
    
      }
  })
</script>

<style scoped>
@import "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";

/* Couleur de fond ajustée comme dans l’image */
footer {
  background-color: var(--primary-color) ;
}
.text{
  color: var(--success-color);
}

</style>