<template>
    <div class="col-md-4 text-center text-md-end">
          <button  class="btn text-dark me-2 px-4 rounded-pill fw-semibold">
            Se connecter 
          </button>
          <button class="btn text-dark px-4 rounded-pill fw-semibold">
            S'inscrire
          </button>
        </div>
</template>

<style scoped>
.btn-menunav{
  background-color: var(--accent-color);
  /* box-shadow: 0 0px 6px rgba(0, 0, 0, 0.1); */
  border: var(--secondary-color);
}
.btn{
  background-color: var(--primary-color);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: var(--secondary-color);
  transition: all 0.3s ease; 
  text-transform: uppercase; 
  letter-spacing: 1px;
}
.btn:hover{
  background-color: var(--accent-color);
}
.btn:active{
  color:var(--success-color);
}
</style>