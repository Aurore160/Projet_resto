<template>
  <div class="parametres container py-4">
    <div v-if="current && current.role === 'admin'">
      <h2></h2>
      <p class="text-muted">Gérer les réclamations, rôles et règles de sécurité.</p>

      <div class="row">
        <div class="col-md-8">
          <ComplaintsList />
        </div>
        <div class="col-md-4">
          <div class="card mb-3">
            <div class="card-body">
              <RolePermissions />
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <SecurityRules />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="alert alert-warning">
      Accès restreint : vous devez être administrateur pour voir cette page.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ComplaintsList from '../components/ComplaintsList.vue'
import RolePermissions from '../components/RolePermissions.vue'
import SecurityRules from '../components/SecurityRules.vue'
import { getCurrentUser } from '../services/mockAdminService'

const current = ref(null)
onMounted(async ()=>{ current.value = await getCurrentUser() })
</script>

<style scoped>
.parametres{ padding-top: 1rem }
</style>