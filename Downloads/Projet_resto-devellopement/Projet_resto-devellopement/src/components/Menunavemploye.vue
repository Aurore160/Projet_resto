<template>
  <div class="col-md-12 text-center">
    <button class="btn text-dark me-4 px-4 rounded-pill fw-semibold">
      Accueil
    </button>
    <router-link to="/employe/commandes">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Commandes
      </button>
    </router-link>
  </div>
</template>

<style scoped>
.btn {
  background-color: var(--primary-color);
  border: var(--primary-color);
}

.btn:hover {
  background-color: var(--accent-color);
}

a {
  text-decoration: none;
}
</style>