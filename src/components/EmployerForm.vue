<!-- components/EmployerForm.vue -->
<template>
  <div v-if="modelValue" class="userform-backdrop">
    <div class="userform-card">
      <!-- En-tête fixe -->
      <div class="modal-header">
        <h4 class="mb-0">{{ employee ? 'Modifier l\'employé' : 'Configurer un Employé' }}</h4>
        <button class="btn-close" @click="close" aria-label="Fermer"></button>
      </div>

      <!-- Contenu scrollable -->
      <div class="modal-body">
        <form @submit.prevent="submit">
          <div class="row g-3">
            <!-- Sélection de l'utilisateur -->
            <div class="col-12">
              <label class="form-label">Sélectionner l'utilisateur <span class="text-danger">*</span></label>
              <div class="searchable-select">
                <input
                  v-model="userSearch"
                  type="text"
                  class="form-control"
                  placeholder="Rechercher un utilisateur..."
                  @input="filterUsers"
                />
                <div v-if="userSearch && filteredAvailableUsers.length > 0" class="search-results">
                  <div
                    v-for="user in filteredAvailableUsers"
                    :key="user.id"
                    class="search-result-item"
                    @click="selectUser(user)"
                  >
                    <div class="user-option">
                      <div class="user-option-name">{{ user.nom }}</div>
                      <div class="user-option-email">{{ user.email }}</div>
                      <div class="user-option-role">{{ formatRole(user.role) }}</div>
                    </div>
                  </div>
                </div>
                <div v-if="selectedUser" class="selected-user">
                  <div class="selected-user-info">
                    <strong>{{ selectedUser.nom }}</strong> - {{ selectedUser.email }} 
                    <span class="badge bg-secondary ms-2">{{ formatRole(selectedUser.role) }}</span>
                  </div>
                </div>
                <div v-else-if="!userSearch" class="form-text">
                  Commencez à taper pour rechercher un utilisateur
                </div>
              </div>
            </div>

            <!-- Informations professionnelles -->
            <div class="col-md-6">
              <label class="form-label">Matricule <span class="text-danger">*</span></label>
              <input 
                v-model="local.matricule" 
                required 
                class="form-control" 
                placeholder="EMP001" 
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Poste <span class="text-danger">*</span></label>
              <input 
                v-model="local.poste" 
                required 
                class="form-control" 
                placeholder="Ex: Serveur, Cuisinier, etc." 
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Salaire <span class="text-danger">*</span></label>
              <input 
                v-model="local.salaire" 
                type="number" 
                required 
                class="form-control" 
                placeholder="0"
                min="0"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Date d'embauche <span class="text-danger">*</span></label>
              <input 
                v-model="local.dateEmbauche" 
                type="date" 
                required 
                class="form-control" 
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Date de fin de contrat (si applicable)</label>
              <input 
                v-model="local.dateFinContrat" 
                type="date" 
                class="form-control" 
                :min="local.dateEmbauche"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Statut <span class="text-danger">*</span></label>
              <select v-model="local.statut" class="form-select" required>
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
              </select>
            </div>

            <!-- Informations complémentaires -->
            <div class="col-12">
              <label class="form-label">Notes supplémentaires (optionnel)</label>
              <textarea 
                v-model="local.notes" 
                rows="3" 
                class="form-control" 
                placeholder="Informations complémentaires sur l'employé..."
              ></textarea>
            </div>
          </div>
        </form>
      </div>

      <!-- Pied de page fixe -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="close">Annuler</button>
        <button
          type="button"
          class="btn btn-primary"
          @click="submit"
          :disabled="!selectedUser"
        >
          {{ employee ? 'Mettre à jour' : 'Configurer l\'employé' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  employee: { type: Object, default: null }
})
const emits = defineEmits(['update:modelValue', 'save'])

// Données simulées des utilisateurs disponibles (à remplacer par votre API)
const availableUsers = ref([
  { id: 1, nom: 'Jean Dupont', email: 'jean@example.com', telephone: '+243 970 123 456', role: 'client' },
  { id: 2, nom: 'Aline Moke', email: 'aline@example.com', telephone: '+243 970 987 654', role: 'employe' },
  { id: 3, nom: 'Kevin Samba', email: 'kevin@example.com', telephone: '+243 971 444 222', role: 'gestionnaire' },
  { id: 4, nom: 'Sarah Kanza', email: 'sarah@example.com', telephone: '+243 972 888 999', role: 'admin' },
  { id: 5, nom: 'Marc Tumba', email: 'marc@example.com', telephone: '+243 973 111 222', role: 'employe' },
  { id: 6, nom: 'Lisa Mbala', email: 'lisa@example.com', telephone: '+243 974 333 444', role: 'gestionnaire' }
])

const userSearch = ref('')
const selectedUser = ref(null)
const filteredAvailableUsers = ref([])

const local = ref(createEmptyEmployee())

function createEmptyEmployee() {
  return {
    userId: null,
    matricule: '',
    poste: '',
    salaire: null,
    dateEmbauche: '',
    dateFinContrat: '',
    statut: 'actif',
    notes: ''
  }
}

function resetForm() {
  local.value = createEmptyEmployee()
  selectedUser.value = null
  userSearch.value = ''
  filteredAvailableUsers.value = []
}

function close() {
  resetForm()
  emits('update:modelValue', false)
}

function filterUsers() {
  if (!userSearch.value.trim()) {
    filteredAvailableUsers.value = []
    return
  }

  const searchTerm = userSearch.value.toLowerCase()
  filteredAvailableUsers.value = availableUsers.value.filter(user => 
    user.nom.toLowerCase().includes(searchTerm) ||
    user.email.toLowerCase().includes(searchTerm)
  ).slice(0, 5) // Limiter à 5 résultats
}

function selectUser(user) {
  selectedUser.value = user
  local.value.userId = user.id
  userSearch.value = ''
  filteredAvailableUsers.value = []
}

function formatRole(role) {
  const roles = {
    client: 'Client',
    employe: 'Employé',
    gestionnaire: 'Gestionnaire',
    admin: 'Administrateur'
  }
  return roles[role] || role
}

function submit() {
  if (!selectedUser.value) {
    alert('Veuillez sélectionner un utilisateur')
    return
  }

  const payload = {
    id: props.employee ? props.employee.id : Date.now(),
    ...selectedUser.value, // Informations de l'utilisateur
    ...local.value         // Informations professionnelles
  }

  emits('save', payload)
  close()
}

// Synchronisation pour l'édition
watch(() => props.employee, (newEmployee) => {
  if (newEmployee) {
    // Remplir avec les données existantes
    local.value = {
      userId: newEmployee.id,
      matricule: newEmployee.matricule || '',
      poste: newEmployee.poste || '',
      salaire: newEmployee.salaire || null,
      dateEmbauche: newEmployee.dateEmbauche || '',
      dateFinContrat: newEmployee.dateFinContrat || '',
      statut: newEmployee.statut || 'actif',
      notes: newEmployee.notes || ''
    }
    
    // Trouver l'utilisateur correspondant
    const user = availableUsers.value.find(u => u.id === newEmployee.id)
    if (user) {
      selectedUser.value = user
    }
  } else {
    resetForm()
  }
}, { immediate: true })
</script>

<style scoped>
.userform-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(2, 6, 23, 0.5);
  z-index: 1200;
  padding: 1rem;
}

.userform-card {
  width: 720px;
  max-width: 100%;
  background: white;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  border: 1px solid #a89f91;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  padding: 18px 24px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
}

.modal-body {
  padding: 0 24px;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  justify-content: end;
  gap: 12px;
  border-top: 1px solid #eee;
  margin-top: auto;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #3a352f;
  margin-bottom: 6px;
}

.form-control,
.form-select {
  border: 1px solid #ddd;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 0.95rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #e0d6be;
  box-shadow: 0 0 0 3px rgba(138, 129, 116, 0.15);
}

.btn-close {
  background: transparent;
  border: 0;
  width: 28px;
  height: 28px;
  font-size: 1.2rem;
  color: #666;
  opacity: 1;
}

.btn-close:hover {
  color: #e0d6be;
}

.btn-primary {
  background: #e0d6be;
  border: none;
  padding: 10px 20px;
  font-weight: 600;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #7a6e5e;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f0f0f0;
  border: 1px solid #ddd;
  color: #444;
}

.btn-secondary:hover {
  background: #e5e5e5;
}

.text-danger {
  color: #dc3545;
}

/* Styles pour la sélection d'utilisateur */
.searchable-select {
  position: relative;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.search-result-item {
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background-color 0.2s;
}

.search-result-item:hover {
  background-color: #f8f9fa;
}

.search-result-item:last-child {
  border-bottom: none;
}

.user-option-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 2px;
}

.user-option-email {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 2px;
}

.user-option-role {
  font-size: 0.8rem;
  color: #888;
}

.selected-user {
  margin-top: 8px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.selected-user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-text {
  font-size: 0.85rem;
  color: #6c757d;
  margin-top: 4px;
}
</style>