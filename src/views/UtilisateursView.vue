<template>
  <div class="admin-dashboard">
    <!-- Header avec fond dégradé -->
    <div class="dashboard-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col">
            <h1 class="dashboard-title">
              <i class="bi bi-people-fill"></i>
              Gestion des Utilisateurs
            </h1>
            <p class="dashboard-subtitle">Administrez et gérez tous les utilisateurs de votre plateforme</p>
          </div>
          <div class="col-auto">
            <button class="btn btn-primary btn-add-user" @click="openAddUserModal">
              <i class="bi bi-person-add"></i>
              Nouvel Utilisateur
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
              <div class="stat-label">Utilisateurs Totaux</div>
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
              <div class="stat-label">Utilisateurs Actifs</div>
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
              <div class="stat-label">Utilisateurs Inactifs</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stat-card admin-users">
            <div class="stat-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ adminUsers }}</div>
              <div class="stat-label">Administrateurs</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Barre de contrôle principale -->
      <div class="control-panel card border-0 shadow-lg mb-4">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
              <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input
                  v-model="search"
                  type="text"
                  class="search-input"
                  placeholder="Rechercher un utilisateur..."
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
                    <option value="client">Client</option>
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

      <!-- Tableau des utilisateurs -->
      <div class="card border-0 shadow-lg">
        <div class="card-header bg-transparent py-3">
          <h5 class="card-title mb-0">
            <i class="bi bi-list-ul me-2"></i>
            Liste des Utilisateurs
            <span class="badge bg-primary ms-2">{{ filteredUsers.length }}</span>
          </h5>
        </div>
        
        <div class="card-body p-0">
          <div class="table-container">
            <table class="user-table">
              <thead>
                <tr>
                  <th class="text-center">#</th>
                  <th>Utilisateur</th>
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
                  <td class="text-center user-index">
                    {{ (currentPage - 1) * perPage + index + 1 }}
                  </td>
                  
                  <td>
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
                  
                  <td>
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
                  
                  <td>
                    <span :class="['role-badge', `role-${user.role}`]">
                      <i :class="getRoleIcon(user.role)" class="me-1"></i>
                      {{ formatRole(user.role) }}
                    </span>
                  </td>
                  
                  <td class="text-center">
                    <div class="status-toggle">
                      <button
                        :class="['status-btn', user.statut === 'actif' ? 'active' : 'inactive']"
                        @click="toggleStatus(user)"
                      >
                        <span class="status-dot"></span>
                        {{ user.statut === 'actif' ? 'Actif' : 'Inactif' }}
                      </button>
                    </div>
                  </td>
                  
                  <td class="text-center">
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
                    <h5 class="mt-3">Aucun utilisateur trouvé</h5>
                    <p class="text-muted">Aucun utilisateur ne correspond à vos critères de recherche.</p>
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
          <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info">
              Affichage de <strong>{{ Math.min(filteredUsers.length, (currentPage - 1) * perPage + 1) }}</strong>
              à <strong>{{ Math.min(currentPage * perPage, filteredUsers.length) }}</strong>
              sur <strong>{{ filteredUsers.length }}</strong> utilisateurs
            </div>
            
            <nav>
              <ul class="pagination custom-pagination">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <a class="page-link" href="#" @click.prevent="goPage(currentPage - 1)">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
                
                <li
                  v-for="p in visiblePages"
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
    </div>

    <!-- Modal utilisateur -->
    <UserForm v-model:modelValue="showUserModal" :user="editingUser" @save="onSaveUser" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import UserForm from '../components/UserForm.vue'
import { addToast } from '../services/toastService'

// Données utilisateurs
const users = ref([
  { id: 1, nom: 'Jean Dupont', email: 'jean@example.com', telephone: '+243 970 123 456', role: 'client', statut: 'actif' },
  { id: 2, nom: 'Aline Moke', email: 'aline@example.com', telephone: '+243 970 987 654', role: 'employe', statut: 'actif' },
  { id: 3, nom: 'Kevin Samba', email: 'kevin@example.com', telephone: '+243 971 444 222', role: 'gestionnaire', statut: 'inactif' },
  { id: 4, nom: 'Sarah Kanza', email: 'sarah@example.com', telephone: '+243 972 888 999', role: 'admin', statut: 'actif' },
  { id: 5, nom: 'Marc Tumba', email: 'marc@example.com', telephone: '+243 973 111 222', role: 'client', statut: 'actif' },
  { id: 6, nom: 'Lisa Mbala', email: 'lisa@example.com', telephone: '+243 974 333 444', role: 'employe', statut: 'inactif' }
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
const adminUsers = computed(() => users.value.filter(u => u.role === 'admin').length)

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
  // Implémentez la vue détaillée ici
  alert(`Vue détaillée de ${user.nom}`)
}

const onSaveUser = (u) => {
  const existing = users.value.find(x => x.id === u.id)
  if (existing) {
    Object.assign(existing, u)
    addToast({ title: 'Utilisateur', message: `${u.nom} mis à jour`, type: 'info' })
  } else {
    users.value.unshift(u)
    addToast({ title: 'Utilisateur', message: `${u.nom} ajouté`, type: 'success' })
  }
}

const deleteUser = (user) => {
  if (!confirm(`Supprimer définitivement l'utilisateur "${user.nom}" ?`)) return
  users.value = users.value.filter(u => u.id !== user.id)
  addToast({ title: 'Utilisateur', message: `${user.nom} supprimé`, type: 'warning' })
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
  if (p < 1 || p > totalPages.value || p === '...') return
  currentPage.value = p
}

// Utilitaires
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRoleIcon = (role) => {
  const icons = {
    client: 'bi bi-person',
    employe: 'bi bi-briefcase',
    gestionnaire: 'bi bi-gear',
    admin: 'bi bi-shield-check'
  }
  return icons[role] || 'bi bi-person'
}

const formatRole = (role) => {
  const roles = {
    client: 'Client',
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
  a.download = `utilisateurs_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  addToast({ title: 'Export', message: 'Liste exportée avec succès', type: 'success' })
}
</script>

<style scoped>
.admin-dashboard {
  min-height: 100vh;
  /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
}

.dashboard-header {
  /* background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%); */
  backdrop-filter: blur(10px);
  padding: 2rem 0;
  /* border-bottom: 1px solid rgba(255, 255, 255, 0.1); */
}

.dashboard-title {
  color: white;
  font-weight: 700;
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  margin-bottom: 0;
}

.btn-add-user {
  background: linear-gradient(135deg, #00b4db, #0083b0);
  border: none;
  padding: 12px 24px;
  font-weight: 600;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 180, 219, 0.3);
  transition: all 0.3s ease;
}

.btn-add-user:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 180, 219, 0.4);
}

/* Cartes de statistiques */
.stats-cards {
  margin-top: -2rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-right: 1rem;
  color: white;
}

.total-users .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.active-users .stat-icon { background: linear-gradient(135deg, #4ecdc4, #44a08d); }
.inactive-users .stat-icon { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }
.admin-users .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }

.stat-number {
  font-size: 2rem;
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
.control-panel {
  border-radius: 16px;
  overflow: hidden;
}

.search-box {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 45px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f8fafc;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 140px;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 4px;
}

.filter-select {
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

.btn-reset, .btn-export {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-export {
  background: linear-gradient(135deg, #48bb78, #38a169);
  border: none;
}

.btn-export:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

/* Tableau */
.user-table {
  width: 100%;
  border-collapse: collapse;
}

.user-table th {
  background: #f7fafc;
  padding: 1rem;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 2px solid #e2e8f0;
  text-align: left;
}

.user-table td {
  padding: 1rem;
  border-bottom: 1px solid #edf2f7;
}

.user-row:hover {
  background: #f7fafc;
  transform: scale(1.01);
  transition: all 0.2s ease;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1rem;
}

.user-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 2px;
}

.user-email {
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

.role-client { background: #e6fffa; color: #234e52; }
.role-employe { background: #ebf8ff; color: #1a365d; }
.role-gestionnaire { background: #faf5ff; color: #44337a; }
.role-admin { background: #fff5f5; color: #742a2a; }

.status-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.status-btn.active {
  background: #c6f6d5;
  color: #22543d;
}

.status-btn.inactive {
  background: #fed7d7;
  color: #742a2a;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-btn.active .status-dot { background: #38a169; }
.status-btn.inactive .status-dot { background: #e53e3e; }

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

.btn-delete {
  background: #fed7d7;
  color: #e53e3e;
}

.btn-delete:hover {
  background: #feb2b2;
  transform: scale(1.1);
}

.btn-view {
  background: #f0fff4;
  color: #38a169;
}

.btn-view:hover {
  background: #c6f6d5;
  transform: scale(1.1);
}

/* État vide */
.empty-state {
  color: #718096;
}

.empty-icon {
  font-size: 4rem;
  color: #cbd5e0;
}

/* Pagination */
.custom-pagination .page-link {
  border: none;
  color: #4a5568;
  padding: 8px 16px;
  margin: 0 2px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.custom-pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.custom-pagination .page-link:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.pagination-info {
  color: #718096;
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-title {
    font-size: 2rem;
  }
  
  .stats-cards {
    margin-top: 0;
  }
  
  .user-info {
    flex-direction: column;
    text-align: center;
    gap: 8px;
  }
  
  .user-avatar {
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
}
</style>