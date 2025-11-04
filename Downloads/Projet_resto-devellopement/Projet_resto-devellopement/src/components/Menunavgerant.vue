<template>
    <div class="col-md-12 text-center ">
      <button class="btn text-dark me-4 px-4 rounded-pill fw-semibold">
        Accueil
      </button>
      <router-link to="/contact">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Commandes
      </button></router-link>
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Comptes
      </button>
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Evenements
      </button>
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Statistiques
      </button>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()
</script>



<style scoped>
.btn{
  background-color: var(--primary-color);
  /* box-shadow: 0 0px 6px rgba(0, 0, 0, 0.1); */
  border: var(--primary-color);
}
.btn:hover{
  background-color: var(--accent-color);
}

</style>

