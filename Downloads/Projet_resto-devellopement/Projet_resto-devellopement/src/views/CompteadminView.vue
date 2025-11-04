<template>
  <div class="compte-admin-view">
    <Compte
      :allowed-roles="['employe', 'gerant']" 
      title="Gestion des Comptes - Administration"
      @employee-created="handleEmployeeCreated"
      @employee-updated="handleEmployeeUpdated"
      @employee-deleted="handleEmployeeDeleted"
    />
  </div>
</template>

<script setup>
import Compte from '../components/Compte.vue'

const handleEmployeeCreated = (event) => {
  console.log('Employé créé:', event.employee)
  // Logique supplémentaire pour l'admin
}

const handleEmployeeUpdated = (event) => {
  console.log('Employé modifié:', event.employee)
  // Logique supplémentaire pour l'admin
}

const handleEmployeeDeleted = (event) => {
  console.log('Employé supprimé:', event.employee)
  // Logique supplémentaire pour l'admin
}
</script>