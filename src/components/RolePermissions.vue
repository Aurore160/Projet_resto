<template>
  <div class="role-permissions">
    <h3>Gestion des rôles et permissions</h3>
    <table class="table table-sm">
      <thead>
        <tr><th>Utilisateur</th><th>Rôle</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.nom }}<br/><small>{{ u.email }}</small></td>
          <td>
            <select v-model="u.role" @change="changeRole(u)" class="form-select form-select-sm">
              <option value="employe">Employé</option>
              <option value="gestionnaire">Gestionnaire</option>
              <option value="admin">Admin</option>
            </select>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-primary" @click="save(u)">Enregistrer</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import * as svc from '../services/mockAdminService'

const users = ref([])
onMounted(async ()=>{ users.value = await svc.getUsers() })

function changeRole(u){ /* local change only until saved */ }
async function save(u){ await svc.setUserRole(u.id, u.role); alert('Rôle mis à jour') }
</script>

<style scoped>
.role-permissions .table td{ vertical-align:middle }
</style>
