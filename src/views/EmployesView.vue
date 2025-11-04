<template>
  <div class="employees-management">
    <!-- Header amélioré -->
    <div class="management-header mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="page-title">
            <i class="bi bi-people-fill me-3"></i>
            
          </h1>
          <p class="page-subtitle">Administrez l'ensemble de votre personnel</p>
        </div>
        <button class="btn btn-primary btn-add-employee" @click="openAddUserModal">
          <i class="bi bi-person-add me-2"></i>
          Ajouter un Employé
        </button>
      </div>
    </div>

    <!-- Cartes de statistiques modernes -->
    <div class="row stats-row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card">
          <div class="stat-icon total">
            <i class="bi bi-people"></i>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ totalUsers }}</div>
            <div class="stat-label">Total Employés</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card">
          <div class="stat-icon active">
            <i class="bi bi-person-check"></i>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ activeUsers }}</div>
            <div class="stat-label">Employés Actifs</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card">
          <div class="stat-icon inactive">
            <i class="bi bi-person-x"></i>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ inactiveUsers }}</div>
            <div class="stat-label">Employés Inactifs</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card">
          <div class="stat-icon managers">
            <i class="bi bi-star"></i>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ managerUsers }}</div>
            <div class="stat-label">Gestionnaires</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Barre de contrôle moderne -->
    <div class="control-bar card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-5 mb-3 mb-md-0">
            <div class="search-container">
              <i class="bi bi-search search-icon"></i>
              <input
                v-model="search"
                type="text"
                class="search-input"
                placeholder="Rechercher un employé..."
              />
            </div>
          </div>
          
          <div class="col-md-7">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
              <!-- Bouton filtre avec panel -->
              <div class="filter-container">
                <button class="btn btn-outline-primary btn-filter" @click="showFilter = !showFilter">
                  <i class="bi bi-funnel me-2"></i>
                  Filtrer
                  <i class="bi bi-chevron-down ms-1" :class="{ 'rotate-180': showFilter }"></i>
                </button>
                
                <div v-if="showFilter" class="filter-dropdown">
                  <div class="filter-section">
                    <label class="filter-label">Rôle</label>
                    <select v-model="filterRole" class="filter-select">
                      <option value="">Tous les rôles</option>
                      <option value="employe">Employé</option>
                      <option value="gestionnaire">Gestionnaire</option>
                      <option value="admin">Administrateur</option>
                    </select>
                  </div>
                  
                  <div class="filter-section">
                    <label class="filter-label">Statut</label>
                    <select v-model="filterStatus" class="filter-select">
                      <option value="">Tous les statuts</option>
                      <option value="actif">Actif</option>
                      <option value="inactif">Inactif</option>
                    </select>
                  </div>
                  
                  <div class="filter-section">
                    <label class="filter-label">Trier par</label>
                    <select v-model="sortOrder" class="filter-select">
                      <option value="">Ordre par défaut</option>
                      <option value="az">Nom (A → Z)</option>
                      <option value="za">Nom (Z → A)</option>
                    </select>
                  </div>
                  
                  <div class="filter-actions">
                    <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                      Réinitialiser
                    </button>
                    <button class="btn btn-sm btn-primary" @click="applyFilters">
                      Appliquer
                    </button>
                  </div>
                </div>
              </div>

              <button class="btn btn-success btn-export" @click="exportToCSV">
                <i class="bi bi-file-earmark-excel me-2"></i>
                Exporter
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tableau moderne -->
    <div class="card border-0 shadow-lg">
      <div class="card-header bg-transparent py-3">
        <h5 class="card-title mb-0">
          <i class="bi bi-list-ul me-2"></i>
          Liste des Employés
          <span class="badge bg-primary ms-2">{{ filteredUsers.length }}</span>
        </h5>
      </div>
      
      <div class="card-body p-0">
        <div class="table-container">
          <table class="employees-table">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Employé</th>
                <th>Contact</th>
                <th>Rôle</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(user, index) in paginatedUsers"
                :key="user.id"
                class="employee-row"
              >
                <td class="text-center employee-index">
                  {{ (currentPage - 1) * perPage + index + 1 }}
                </td>
                
                <td>
                  <div class="employee-info">
                    <div class="employee-avatar">
                      {{ getInitials(user.nom) }}
                    </div>
                    <div class="employee-details">
                      <div class="employee-name">{{ user.nom }}</div>
                      <div class="employee-role">{{ formatRole(user.role) }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-info">
                    <div class="contact-item">
                      <i class="bi bi-envelope me-2 text-primary"></i>
                      {{ user.email }}
                    </div>
                    <div class="contact-item">
                      <i class="bi bi-telephone me-2 text-success"></i>
                      {{ user.telephone }}
                    </div>
                  </div>
                </td>
                
                <td>
                  <span :class="['role-badge', `role-${user.role}`]">
                    <i :class="getRoleIcon(user.role)" class="me-1"></i>
                    {{ formatRole(user.role) }}
                  </span>
                </td>
                
                <td class="text-center">
                  <div class="status-container">
                    <span :class="['status-badge', user.statut === 'actif' ? 'status-active' : 'status-inactive']">
                      <span class="status-dot"></span>
                      {{ user.statut === 'actif' ? 'Actif' : 'Inactif' }}
                    </span>
                  </div>
                </td>
                
                <td class="text-center">
                  <div class="action-buttons">
                    <button
                      class="btn-action btn-edit"
                      @click="editUser(user)"
                      title="Modifier"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    
                    <button
                      class="btn-action btn-toggle"
                      :class="user.statut === 'actif' ? 'btn-warning' : 'btn-success'"
                      @click="toggleStatus(user)"
                      :title="user.statut === 'actif' ? 'Désactiver' : 'Activer'"
                    >
                      <i :class="user.statut === 'actif' ? 'bi bi-person-x' : 'bi bi-person-check'"></i>
                    </button>
                    
                    <button
                      class="btn-action btn-delete"
                      @click="deleteUser(user)"
                      title="Supprimer"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              
              <!-- État vide -->
              <tr v-if="filteredUsers.length === 0">
                <td colspan="6" class="text-center py-5 empty-state">
                  <div class="empty-icon">
                    <i class="bi bi-search"></i>
                  </div>
                  <h5 class="mt-3 text-muted">Aucun employé trouvé</h5>
                  <p class="text-muted mb-3">Aucun employé ne correspond à vos critères de recherche.</p>
                  <button class="btn btn-primary" @click="resetFilters">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Réinitialiser les filtres
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination améliorée -->
      <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
          <div class="pagination-info">
            Affichage de <strong>{{ Math.min(filteredUsers.length, (currentPage - 1) * perPage + 1) }}</strong>
            à <strong>{{ Math.min(currentPage * perPage, filteredUsers.length) }}</strong>
            sur <strong>{{ filteredUsers.length }}</strong> employés
          </div>
          
          <nav>
            <ul class="pagination modern-pagination">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <a class="page-link" href="#" @click.prevent="goPage(currentPage - 1)">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>
              
              <li
                v-for="p in totalPages"
                :key="p"
                class="page-item"
                :class="{ active: p === currentPage }"
              >
                <a class="page-link" href="#" @click.prevent="goPage(p)">
                  {{ p }}
                </a>
              </li>
              
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <a class="page-link" href="#" @click.prevent="goPage(currentPage + 1)">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal utilisateur -->
    <UserForm v-model:modelValue="showUserModal" :user="editingUser" @save="onSaveUser" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import UserForm from '../components/UserForm.vue'
import { addToast } from '../services/toastService'

// Données employés
const users = ref([
  { id: 2, nom: 'Aline Moke', email: 'aline@example.com', telephone: '+243 970 987 654', role: 'employe', statut: 'actif' },
  { id: 3, nom: 'Kevin Samba', email: 'kevin@example.com', telephone: '+243 971 444 222', role: 'gestionnaire', statut: 'inactif' },
  { id: 4, nom: 'Sarah Kanza', email: 'sarah@example.com', telephone: '+243 972 888 999', role: 'admin', statut: 'actif' },
  { id: 5, nom: 'Marc Tumba', email: 'marc@example.com', telephone: '+243 973 111 222', role: 'employe', statut: 'actif' },
  { id: 6, nom: 'Lisa Mbala', email: 'lisa@example.com', telephone: '+243 974 333 444', role: 'gestionnaire', statut: 'inactif' }
])

// États de recherche et filtres
const search = ref('')
const filterRole = ref('')
const filterStatus = ref('')
const sortOrder = ref('')
const showFilter = ref(false)
const currentPage = ref(1)
const perPage = ref(8)

// Modal
const showUserModal = ref(false)
const editingUser = ref(null)

// Computed
const filteredUsers = computed(() => {
  let list = users.value.filter(user => {
    const matchSearch =
      user.nom.toLowerCase().includes(search.value.toLowerCase()) ||
      user.email.toLowerCase().includes(search.value.toLowerCase())

    const matchRole = filterRole.value ? user.role === filterRole.value : true
    const matchStatus = filterStatus.value ? user.statut === filterStatus.value : true

    return matchSearch && matchRole && matchStatus
  })

  if (sortOrder.value === 'az') {
    list = list.slice().sort((a, b) => a.nom.localeCompare(b.nom))
  } else if (sortOrder.value === 'za') {
    list = list.slice().sort((a, b) => b.nom.localeCompare(a.nom))
  }

  return list
})

const totalUsers = computed(() => users.value.length)
const activeUsers = computed(() => users.value.filter(u => u.statut === 'actif').length)
const inactiveUsers = computed(() => users.value.filter(u => u.statut === 'inactif').length)
const managerUsers = computed(() => users.value.filter(u => u.role === 'gestionnaire').length)

const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / perPage.value)))
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredUsers.value.slice(start, start + perPage.value)
})

// Méthodes
const openAddUserModal = () => {
  editingUser.value = null
  showUserModal.value = true
}

const editUser = (user) => {
  editingUser.value = { ...user }
  showUserModal.value = true
}

const onSaveUser = (u) => {
  const existing = users.value.find(x => x.id === u.id)
  if (existing) {
    Object.assign(existing, u)
    addToast({ title: 'Employé', message: `${u.nom} mis à jour`, type: 'info' })
  } else {
    users.value.unshift(u)
    addToast({ title: 'Employé', message: `${u.nom} ajouté`, type: 'success' })
  }
}

const deleteUser = (user) => {
  if (!confirm(`Supprimer définitivement l'employé "${user.nom}" ?`)) return
  users.value = users.value.filter(u => u.id !== user.id)
  addToast({ title: 'Employé', message: `${user.nom} supprimé`, type: 'warning' })
}

const toggleStatus = (user) => {
  user.statut = user.statut === 'actif' ? 'inactif' : 'actif'
  const action = user.statut === 'actif' ? 'activé' : 'désactivé'
  addToast({ title: 'Statut', message: `${user.nom} ${action}`, type: 'info' })
}

const applyFilters = () => {
  showFilter.value = false
  currentPage.value = 1
}

const resetFilters = () => {
  search.value = ''
  filterRole.value = ''
  filterStatus.value = ''
  sortOrder.value = ''
  currentPage.value = 1
  showFilter.value = false
}

const goPage = (p) => {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
}

// Utilitaires
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRoleIcon = (role) => {
  const icons = {
    employe: 'bi bi-person',
    gestionnaire: 'bi bi-star',
    admin: 'bi bi-shield-check'
  }
  return icons[role] || 'bi bi-person'
}

const formatRole = (role) => {
  const roles = {
    employe: 'Employé',
    gestionnaire: 'Gestionnaire',
    admin: 'Administrateur'
  }
  return roles[role] || role
}

const exportToCSV = () => {
  const rows = [['#', 'Nom', 'Email', 'Téléphone', 'Rôle', 'Statut']]
  filteredUsers.value.forEach((u, i) => rows.push([i + 1, u.nom, u.email, u.telephone, u.role, u.statut]))
  const csv = rows.map(r => r.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `employes_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  addToast({ title: 'Export', message: 'Liste exportée avec succès', type: 'success' })
}
</script>

<style scoped>
.employees-management {
  padding: 2rem;
  /* background: #f8f9fa; */
  min-height: 100vh;
}

/* Header */
.management-header {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.page-title {
  color: #2d3748;
  font-weight: 700;
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin-bottom: 0;
}

.btn-add-employee {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  padding: 12px 24px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.3s ease;
}

.btn-add-employee:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
}

/* Cartes de statistiques */
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid #e2e8f0;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-right: 1rem;
  color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.active { background: linear-gradient(135deg, #48bb78, #38a169); }
.stat-icon.inactive { background: linear-gradient(135deg, #f56565, #e53e3e); }
.stat-icon.managers { background: linear-gradient(135deg, #ed8936, #dd6b20); }

.stat-number {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Barre de contrôle */
.control-bar {
  border-radius: 12px;
}

.search-container {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 45px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
}

/* Filtres */
.filter-container {
  position: relative;
}

.btn-filter {
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.bi-chevron-down {
  transition: transform 0.3s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.filter-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 280px;
}

.filter-section {
  margin-bottom: 1rem;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 6px;
  font-size: 0.9rem;
}

.filter-select {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
}

.filter-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 1rem;
}

.btn-export {
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-export:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

/* Tableau */
.employees-table {
  width: 100%;
  border-collapse: collapse;
}

.employees-table th {
  background: #f7fafc;
  padding: 1rem;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 2px solid #e2e8f0;
  text-align: left;
}

.employees-table td {
  padding: 1rem;
  border-bottom: 1px solid #edf2f7;
}

.employee-row:hover {
  background: #f7fafc;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1rem;
}

.employee-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 2px;
}

.employee-role {
  color: #718096;
  font-size: 0.85rem;
}

.contact-item {
  display: flex;
  align-items: center;
  color: #4a5568;
  font-size: 0.9rem;
  margin-bottom: 4px;
}

.role-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
}

.role-employe { background: #ebf8ff; color: #1a365d; }
.role-gestionnaire { background: #faf5ff; color: #44337a; }
.role-admin { background: #fff5f5; color: #742a2a; }

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.status-active {
  background: #c6f6d5;
  color: #22543d;
}

.status-inactive {
  background: #fed7d7;
  color: #742a2a;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-active .status-dot { background: #38a169; }
.status-inactive .status-dot { background: #e53e3e; }

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-action {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-edit {
  background: #ebf8ff;
  color: #3182ce;
}

.btn-edit:hover {
  background: #bee3f8;
  transform: scale(1.1);
}

.btn-toggle {
  color: white;
}

.btn-toggle:hover {
  transform: scale(1.1);
}

.btn-delete {
  background: #fed7d7;
  color: #e53e3e;
}

.btn-delete:hover {
  background: #feb2b2;
  transform: scale(1.1);
}

/* État vide */
.empty-state {
  color: #718096;
}

.empty-icon {
  font-size: 3rem;
  color: #cbd5e0;
}

/* Pagination */
.modern-pagination .page-link {
  border: none;
  color: #4a5568;
  padding: 8px 16px;
  margin: 0 2px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.modern-pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.modern-pagination .page-link:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.pagination-info {
  color: #718096;
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
  .employees-management {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .employee-info {
    flex-direction: column;
    text-align: center;
    gap: 8px;
  }
  
  .employee-avatar {
    width: 40px;
    height: 40px;
    font-size: 0.9rem;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }
  
  .btn-action {
    width: 32px;
    height: 32px;
  }
  
  .filter-dropdown {
    right: -50px;
    min-width: 250px;
  }
}
</style>