<template>
   <!-- Header -->
    <header class="header-section fixed-top ">
      <nav class="navbar navbar-expand-lg">
        <div class="container">
          <Logo/>
          <Menunav/>
          <Boutonsign/>
        </div>
      </nav>
    </header>
</template>


<script setup>
</script>



<style scoped>

/* Header */
.header-section {
  background-color: var(--primary-color);
  padding: 0.2rem 0;
  border-bottom: 2px solid #d4c9ac;
  box-shadow: 0 2px 10px rgba(239, 237, 237, 0.1);
}
</style>