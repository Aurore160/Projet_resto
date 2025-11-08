<template>
  <div class="employee-accounts">
    <div class="header">
      <h2>Gestion des Comptes Employés</h2>
    </div>

    <!-- Formulaire de création -->
    <div class="creation-section">
      <h3>Créer un Nouveau Compte</h3>
      <form @submit.prevent="handleSubmit" class="account-form">
        <div class="form-grid">
          <div class="form-group">
            <label for="nom">Nom *</label>
            <input
              type="text"
              id="nom"
              v-model="form.nom"
              required
              placeholder="Entrez le nom"
            >
          </div>

          <div class="form-group">
            <label for="postnom">Post-nom *</label>
            <input
              type="text"
              id="postnom"
              v-model="form.postnom"
              placeholder="Entrez le post-nom"
            >
          </div>

          <div class="form-group">
            <label for="prenom">Prénom *</label>
            <input
              type="text"
              id="prenom"
              v-model="form.prenom"
              required
              placeholder="Entrez le prénom"
            >
          </div>

          <div class="form-group">
            <label for="email">Adresse mail *</label>
            <input
              type="email"
              id="email"
              v-model="form.email"
              required
              placeholder="email@entreprise.com"
            >
          </div>

          <div class="form-group">
            <label for="password">Mot de passe *</label>
            <input
              type="text"
              id="password"
              v-model="form.password"
              required
              placeholder="Créez un mot de passe"
            >
          </div>

          <div class="form-group">
            <label for="role">Rôle *</label>
            <select
              id="role"
              v-model="form.role"
              required
              class="role-select"
            >
              <option 
                v-for="role in availableRoles" 
                :key="role.value" 
                :value="role.value"
              >
                {{ role.label }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            {{ isEditing ? 'Modifier le compte' : 'Créer le compte' }}
          </button>
          <button type="button" class="btn btn-primary" @click="resetForm">
            Réinitialiser
          </button>
          <button 
            v-if="isEditing" 
            type="button" 
            class="btn btn-primary" 
            @click="cancelEdit"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>

    <!-- Tableau des comptes -->
    <div class="table-section">
      <h3>Comptes Employés Existants</h3>
      
      <div v-if="employees.length === 0" class="empty-state">
        <p>Aucun compte employé n'a été créé</p>
      </div>

      <div v-else class="table-wrapper">
        <table class="accounts-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Post-nom</th>
              <th>Prénom</th>
              <th>Adresse mail</th>
              <th>Mot de passe</th>
              <th>Rôle</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(employee, index) in employees" :key="index">
              <td>{{ employee.nom }}</td>
              <td>{{ employee.postnom }}</td>
              <td>{{ employee.prenom }}</td>
              <td>{{ employee.email }}</td>
              <td class="password-field">{{ employee.password }}</td>
              <td>
                <span 
                  class="role-badge" 
                  :class="`role-${employee.role}`"
                >
                  {{ getRoleLabel(employee.role) }}
                </span>
              </td>
              <td class="action-buttons">
                <button 
                  class="btn-action btn-edit"
                  @click="editEmployee(index)"
                  title="Modifier"
                >
                  ✏️
                </button>
                <button 
                  class="btn-action btn-delete"
                  @click="deleteEmployee(index)"
                  title="Supprimer"
                >
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  // Définit les rôles disponibles pour ce composant
  allowedRoles: {
    type: Array,
    default: () => ['employe', 'gerant'] // Par défaut, tous les rôles sont disponibles
  },
  // Titre personnalisable
  title: {
    type: String,
    default: 'Gestion des Comptes Employés'
  }
})

// Émits
const emit = defineEmits(['employee-created', 'employee-updated', 'employee-deleted'])

// Données réactives
const employees = ref([])
const isEditing = ref(false)
const editIndex = ref(null)

const form = reactive({
  nom: '',
  postnom: '',
  prenom: '',
  email: '',
  password: '',
  role: ''
})

// Computed properties
const availableRoles = computed(() => {
  const rolesMap = {
    employe: { value: 'employe', label: 'Employé' },
    gerant: { value: 'gerant', label: 'Gérant' }
  }
  
  return props.allowedRoles.map(role => rolesMap[role]).filter(Boolean)
})

const defaultRole = computed(() => {
  return availableRoles.value[0]?.value || 'employe'
})

// Méthodes
const handleSubmit = () => {
  if (isEditing.value) {
    // Modification
    employees.value[editIndex.value] = { ...form }
    emit('employee-updated', { index: editIndex.value, employee: { ...form } })
    isEditing.value = false
    editIndex.value = null
  } else {
    // Création
    employees.value.push({ ...form })
    emit('employee-created', { employee: { ...form } })
  }
  resetForm()
}

const editEmployee = (index) => {
  const employee = employees.value[index]
  Object.assign(form, employee)
  isEditing.value = true
  editIndex.value = index
}

const deleteEmployee = (index) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
    const deletedEmployee = employees.value[index]
    employees.value.splice(index, 1)
    emit('employee-deleted', { index, employee: deletedEmployee })
  }
}

const resetForm = () => {
  form.nom = ''
  form.postnom = ''
  form.prenom = ''
  form.email = ''
  form.password = ''
  form.role = defaultRole.value
}

const cancelEdit = () => {
  isEditing.value = false
  editIndex.value = null
  resetForm()
}

const getRoleLabel = (roleValue) => {
  const role = availableRoles.value.find(r => r.value === roleValue)
  return role?.label || roleValue
}

// Initialisation
onMounted(() => {
  form.role = defaultRole.value
})
</script>

<style scoped>
.employee-accounts {
  max-width: 100%;
  background: var(--success-color);
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.header {
  background: var(--filtre-color);
  color: var(--secondary-color);
  padding: 20px 30px;
}

.header h2 {
  margin: 0;
  font-size: 1.6em;
  font-weight: bold;
}

.creation-section,
.table-section {
  padding: 25px 30px;
}

.creation-section {
  border-bottom: 1px solid var(--primary-color);
  background-color: var(--success-color);
}

.creation-section h3,
.table-section h3 {
  margin-top: 0;
  margin-bottom: 20px;
  color: var(--secondary-color);
  font-size: 1.2em;
}

.account-form {
  margin-top: 15px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 5px;
  font-weight: 600;
  color: var(--fin-color);
  font-size: 0.9em;
}

.form-group input,
.form-group select {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.3s;
  background-color: var(--filtre-color);
  color: var(--fin-color);
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--success-color);
}

.role-select {
  background-color: var(--primary-color);
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background-color: var(--accent-color);
  color: var(--secondary-color);
}

.btn-primary:hover {
  background-color: var(--hover-color);
  color: var(--secondary-color);
  transform: translateY(3px);
}
.btn-primary:active {
  background-color: var(--secondary-color);
  color: var(--primary-color);
  transform: translateY(3px);
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--fin-color);
  font-style: italic;
  background-color: var(--secondary-color);
  border-radius: 4px;
}

.table-wrapper {
  overflow-x: auto;
  border: 1px solid #eaeaea;
  border-radius: 4px;
}

.accounts-table {
  color: var(--secondary-color);
  width: 100%;
  border-collapse: collapse;
  background: var(--success-color);
}
.accounts-table:hover{
 background-color: var(--primary-color);
 color: var(--secondary-color);
}

.accounts-table th {
  background-color: var(--secondary-color);
  color: var(--primary-color);
  padding: 12px 15px;
  text-align: left;
  font-weight: 600;
  border-bottom: var(--fin-color);
  font-size: 0.9em;
}

.accounts-table td {
  padding: 12px 15px;
  border-bottom: 1px solid #eaeaea;
  font-size: 0.9em;
}

.accounts-table tr:last-child td {
  border-bottom: none;
}

.accounts-table tr:hover {
  background-color: var(--accent-color);
  color: var(--secondary-color);
}

.password-field {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: var(--secondary-color);
}

.role-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 600;
  text-transform: uppercase;
}

.role-employe {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}
.action-buttons {
  display: flex;
  gap: 5px;
}

.btn-action {
  padding: 6px 10px;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.3s;
}

.btn-edit {
  background-color: var(--success-color);
  color: white;
}

.btn-edit:hover {
  background-color: var(--secondary-color);
}

.btn-delete {
  background-color: var(--success-color);
  color: white;
}

.btn-delete:hover {
  background-color: var(--secondary-color);
}

/* Responsive */
@media (max-width: 768px) {
  .creation-section,
  .table-section {
    padding: 20px 15px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }

  .accounts-table {
    font-size: 12px;
  }

  .accounts-table th,
  .accounts-table td {
    padding: 8px 10px;
  }
}
</style>