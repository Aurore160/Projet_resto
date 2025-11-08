<template>
  <div class="col-md-12 text-center">
    <!-- Bouton Accueil supprimé -->
    
    <router-link to="/gerant/commandes">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Commandes
      </button>
    </router-link>
    
    <router-link to="/gerant/comptes">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Comptes
      </button>
    </router-link>  
    
    <router-link to="/gerant/reclamations">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Réclamations
      </button>  
    </router-link>

    <router-link to="/gerant/statistiques">
      <button class="btn text-dark me-1 px-4 rounded-pill fw-semibold">
        Statistiques
      </button>  
    </router-link>
  </div>
</template>

<script setup>
// Le script reste le même
</script>

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