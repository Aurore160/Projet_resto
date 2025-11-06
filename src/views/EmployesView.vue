<template>
  <div class="admin-dashboard">
    <!-- Header avec fond dégradé -->
    <div class="dashboard-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col">
            <h1 class="dashboard-title">
              <i class="bi bi-people-fill"></i>
              Gestion des Employés
            </h1>
            <p class="dashboard-subtitle">Administrez et gérez tous les employés de votre plateforme</p>
          </div>
          <div class="col-auto">
            <button class="btn btn-primary btn-add-user" @click="openAddUserModal">
              <i class="bi bi-person-add"></i>
              Nouvel Employé
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="container-fluid mt-4">
      <div class="row stats-cards">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stat-card total-users">
            <div class="stat-icon">
              <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ totalUsers }}</div>
              <div class="stat-label">Employés Totaux</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stat-card active-users">
            <div class="stat-icon">
              <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ activeUsers }}</div>
              <div class="stat-label">Employés Actifs</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stat-card inactive-users">
            <div class="stat-icon">
              <i class="bi bi-person-x"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ inactiveUsers }}</div>
              <div class="stat-label">Employés Inactifs</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stat-card admin-users">
            <div class="stat-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ managerUsers }}</div>
              <div class="stat-label">Gestionnaires</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Barre de contrôle principale -->
      <div class="control-panel card border-0 shadow-sm mb-4">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
              <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input
                  v-model="search"
                  type="text"
                  class="search-input"
                  placeholder="Rechercher un employé..."
                />
              </div>
            </div>
            
            <div class="col-md-8">
              <div class="d-flex flex-wrap gap-3 justify-content-md-end">
                <!-- Filtres rapides -->
                <div class="filter-group">
                  <label class="filter-label">Rôle</label>
                  <select v-model="filterRole" class="filter-select" @change="applyFilters">
                    <option value="">Tous les rôles</option>
                    <option value="employe">Employé</option>
                    <option value="gestionnaire">Gestionnaire</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>

                <div class="filter-group">
                  <label class="filter-label">Statut</label>
                  <select v-model="filterStatus" class="filter-select" @change="applyFilters">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                  </select>
                </div>

                <div class="filter-group">
                  <label class="filter-label">Trier par</label>
                  <select v-model="sortOrder" class="filter-select" @change="applyFilters">
                    <option value="">Ordre par défaut</option>
                    <option value="az">Nom (A-Z)</option>
                    <option value="za">Nom (Z-A)</option>
                  </select>
                </div>

                <button class="btn btn-outline-secondary btn-reset" @click="resetFilters">
                  <i class="bi bi-arrow-clockwise"></i>
                  Réinitialiser
                </button>

                <button class="btn btn-success btn-export" @click="exportToCSV">
                  <i class="bi bi-file-earmark-excel"></i>
                  Exporter
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tableau des employés -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3">
          <h5 class="card-title mb-0">
            <i class="bi bi-list-ul me-2"></i>
            Liste des Employés
            <span class="badge bg-primary ms-2">{{ filteredUsers.length }}</span>
          </h5>
        </div>
        
        <div class="card-body p-0">
          <div class="table-container">
            <table class="user-table">
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
                  class="user-row"
                >
                  <td class="text-center user-index" data-label="#">
                    {{ (currentPage - 1) * perPage + index + 1 }}
                  </td>
                  
                  <td data-label="Employé">
                    <div class="user-info">
                      <div class="user-avatar">
                        {{ getInitials(user.nom) }}
                      </div>
                      <div class="user-details">
                        <div class="user-name">{{ user.nom }}</div>
                        <div class="user-email">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>
                  
                  <td data-label="Contact">
                    <div class="contact-info">
                      <div class="contact-item">
                        <i class="bi bi-envelope me-2"></i>
                        {{ user.email }}
                      </div>
                      <div class="contact-item">
                        <i class="bi bi-telephone me-2"></i>
                        {{ user.telephone }}
                      </div>
                    </div>
                  </td>
                  
                  <td data-label="Rôle">
                    <span :class="['role-badge', `role-${user.role}`]">
                      <i :class="getRoleIcon(user.role)" class="me-1"></i>
                      {{ formatRole(user.role) }}
                    </span>
                  </td>
                  
                  <td class="text-center" data-label="Statut">
                    <div class="status-toggle">
                      <button
                        :class="['status-btn', user.statut]"
                        @click="toggleStatus(user)"
                      >
                        <span class="status-dot"></span>
                        {{ user.statut === 'actif' ? 'Actif' : 'Inactif' }}
                      </button>
                    </div>
                  </td>
                  
                  <td class="text-center" data-label="Actions">
                    <div class="action-buttons">
                      <button
                        class="btn-action btn-edit"
                        @click="editUser(user)"
                        title="Modifier"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      
                      <button
                        class="btn-action btn-delete"
                        @click="deleteUser(user)"
                        title="Supprimer"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                      
                      <button
                        class="btn-action btn-view"
                        @click="viewUser(user)"
                        title="Voir détails"
                      >
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                
                <!-- État vide -->
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="6" class="text-center py-5 empty-state">
                    <div class="empty-icon">
                      <i class="bi bi-people"></i>
                    </div>
                    <h5 class="mt-3">Aucun employé trouvé</h5>
                    <p class="text-muted">Aucun employé ne correspond à vos critères de recherche.</p>
                    <button class="btn btn-primary mt-2" @click="resetFilters">
                      Réinitialiser les filtres
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="pagination-info">
              Affichage de <strong>{{ Math.min(filteredUsers.length, (currentPage - 1) * perPage + 1) }}</strong>
              à <strong>{{ Math.min(currentPage * perPage, filteredUsers.length) }}</strong>
              sur <strong>{{ filteredUsers.length }}</strong> employés
            </div>
            
            <nav>
              <ul class="pagination custom-pagination mb-0">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <a class="page-link" href="#" @click.prevent="goPage(currentPage - 1)">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
                
                <li
                  v-for="p in visiblePages"
                  :key="p"
                  class="page-item"
                  :class="{ active: p === currentPage, disabled: p === '...' }"
                >
                  <span v-if="p === '...'" class="page-link">...</span>
                  <a v-else class="page-link" href="#" @click.prevent="goPage(p)">
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
    </div>

    <!-- Modal employé -->
<EmployerForm v-model:modelValue="showUserModal" :employee="editingUser" @save="onSaveUser" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import EmployerForm from '../components/EmployerForm.vue'
import { addToast } from '../services/toastService'

// Données employés
// Données employés
const users = ref([
  { 
    id: 2, 
    nom: 'Aline Moke', 
    email: 'aline@example.com', 
    telephone: '+243 970 987 654', 
    role: 'employe', 
    statut: 'actif',
    matricule: 'EMP001',
    poste: 'Serveur',
    salaire: 1200000,
    typeContrat: 'CDI',
    dateEmbauche: '2023-01-15'
  },
  { 
    id: 3, 
    nom: 'Kevin Samba', 
    email: 'kevin@example.com', 
    telephone: '+243 971 444 222', 
    role: 'gestionnaire', 
    statut: 'inactif',
    matricule: 'EMP002',
    poste: 'Gestionnaire de stock',
    salaire: 2500000,
    typeContrat: 'CDD',
    dateEmbauche: '2022-06-01',
    dateFinContrat: '2023-12-31'
  },
  { 
    id: 4, 
    nom: 'Sarah Kanza', 
    email: 'sarah@example.com', 
    telephone: '+243 972 888 999', 
    role: 'admin', 
    statut: 'actif',
    matricule: 'EMP003',
    poste: 'Administrateur système',
    salaire: 3500000,
    typeContrat: 'CDI',
    dateEmbauche: '2021-03-10'
  }
])

// États de recherche et filtres
const search = ref('')
const filterRole = ref('')
const filterStatus = ref('')
const sortOrder = ref('')
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

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
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

const viewUser = (user) => {
  alert(`Vue détaillée de ${user.nom}`)
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
  currentPage.value = 1
}

const resetFilters = () => {
  search.value = ''
  filterRole.value = ''
  filterStatus.value = ''
  sortOrder.value = ''
  currentPage.value = 1
}

const goPage = (p) => {
  if (typeof p !== 'number' || p < 1 || p > totalPages.value) return
  currentPage.value = p
}

// Utilitaires
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRoleIcon = (role) => {
  const icons = {
    employe: 'bi bi-briefcase',
    gestionnaire: 'bi bi-gear',
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
:root {
  --color-primary: #e0d6be;
  --color-secondary: #a89f91;
  --color-accent: #7a6e5e;
  --color-light: #f5f3f0;
  --color-dark: #3a352f;
  --color-success: #4caf50;
  --color-warning: #ff9800;
  --color-danger: #f44336;
}

.admin-dashboard {
  min-height: 100vh;
  background-color: #ffffff;
}

.dashboard-header {
  background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
  color: white;
  padding: 2rem 0;
}

.dashboard-title {
  color: black;
  font-weight: 700;
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: rgba(10, 10, 10, 0.9);
  font-size: 1.05rem;
  margin-bottom: 0;
}

.btn-add-user {
  background: #dcca9b;
  border: none;
  padding: 12px 24px;
  font-weight: 600;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(138, 129, 116, 0.3);
  color: white;
  transition: all 0.3s ease;
}

.btn-add-user:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(138, 129, 116, 0.4);
  background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
  color:black;
}

/* Cartes de statistiques */
.stats-cards {
  margin-top: -1.5rem;
}

.stat-card {
  background: #e0d6be;
  border-radius: 14px;
  padding: 1.4rem;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--color-secondary);
  transition: all 0.25s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin-right: 1rem;
  color: white;
}

.total-users .stat-icon { background: var(--color-primary); }
.active-users .stat-icon { background: var(--color-success); }
.inactive-users .stat-icon { background: var(--color-danger); }
.admin-users .stat-icon { background: var(--color-accent); }

.stat-number {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--color-dark);
  line-height: 1;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Barre de contrôle */
.control-panel {
  border-radius: 14px;
  overflow: hidden;
  background: var(--color-secondary);
}

.search-box {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 45px;
  border: 2px solid #eae7e2;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: var(--color-light);
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 3px rgba(138, 129, 116, 0.15);
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 130px;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 4px;
}

.filter-select {
  padding: 8px 12px;
  border: 2px solid #eae7e2;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.btn-reset, .btn-export {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-reset {
  border-color: #ccc;
  color: #555;
  background: #dcca9b;
}

.btn-export {
  background: #e5d8bb;
  border: none;
  color: rgb(16, 15, 15);
}

.btn-export:hover {
  background: #43a047;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
}

/* Tableau */
.table-container {
  overflow-x: auto;
}

.user-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.95rem;
}

.user-table th {
  background-color: var(--color-light);
  color: var(--color-dark);
  font-weight: 600;
  padding: 14px 16px;
  text-align: left;
  border-bottom: 2px solid #eae7e2;
}

.user-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f0eee9;
  color: #444;
}

.user-row {
  transition: background 0.2s ease;
}

.user-row:hover {
  background-color: #faf9f7;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
}

.user-name {
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 2px;
}

.user-email {
  color: #777;
  font-size: 0.85rem;
}

.contact-item {
  display: flex;
  align-items: center;
  color: #555;
  font-size: 0.9rem;
  margin-bottom: 3px;
}

.role-badge {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.role-employe { background: #eef7ff; color: #1a558c; }
.role-gestionnaire { background: #f8f5f0; color: var(--color-primary); }
.role-admin { background: #f9f0f0; color: #9a3b3b; }

.status-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.status-btn.actif {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.status-btn.inactif {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.status-btn.actif .status-dot { background: var(--color-success); }
.status-btn.inactif .status-dot { background: var(--color-danger); }

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-action {
  width: 34px;
  height: 34px;
  border: 1px solid #eae7e2;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
  transition: all 0.2s ease;
}

.btn-edit{background: #05e210;}
.btn-delete{ background: #f90707;}
.btn-view{background:#1976d2}

.btn-action:hover {
  background: var(--color-secondary);
  color: black;
  border-color: var(--color-primary);
  transform: scale(1.08);
}

.btn-edit:hover { background: #046e099d; color: #2e7d32; border-color: #2e7d32; }
.btn-delete:hover { background: #f907078d; color: var(--color-danger); border-color: var(--color-danger); }
.btn-view:hover { background: #05458e81; color: #1976d2; border-color: #1976d2; }

/* État vide */
.empty-state {
  color: #777;
}

.empty-icon {
  font-size: 4rem;
  color: #e0e0e0;
}

/* Pagination */
.custom-pagination .page-link {
  border: 1px solid #eae7e2;
  color: var(--color-dark);
  padding: 8px 12px;
  margin: 0 2px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.custom-pagination .page-item.active .page-link {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.custom-pagination .page-link:hover:not(.disabled) {
  background: var(--color-light);
  color: var(--color-primary);
}

.custom-pagination .page-item.disabled {
  pointer-events: none;
  opacity: 0.6;
}

.pagination-info {
  color: #777;
  font-size: 0.9rem;
}

/* Responsive mobile - tableau en cartes */
@media (max-width: 768px) {
  .user-table,
  .user-table thead,
  .user-table tbody,
  .user-table th,
  .user-table td,
  .user-table tr {
    display: block;
  }

  .user-table thead tr {
    position: absolute;
    top: -9999px;
    left: -9999px;
  }

  .user-table tr {
    border: 1px solid #eae7e2;
    margin-bottom: 16px;
    border-radius: 12px;
    padding: 16px;
    background: white;
  }

  .user-table td {
    border: none;
    position: relative;
    padding-left: 50% !important;
    text-align: right;
    padding-top: 10px;
    padding-bottom: 10px;
  }

  .user-table td:before {
    content: attr(data-label) ": ";
    position: absolute;
    left: 16px;
    width: 48%;
    font-weight: 600;
    color: var(--color-primary);
    text-align: left;
    font-size: 0.95rem;
  }

  .action-buttons {
    justify-content: flex-start;
    gap: 10px;
  }

  .btn-action {
    width: 38px;
    height: 38px;
  }
}
</style>