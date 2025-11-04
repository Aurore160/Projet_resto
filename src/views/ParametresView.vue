<template>
  <div class="parametres container py-4">
    <div v-if="current && current.role === 'admin'">
      <h2></h2>
      <p class="text-muted">Gérer les réclamations, rôles et règles de sécurité.</p>

      <!-- Boutons d'exportation -->
      <div class="d-flex justify-content-between mb-3">
        <div>
          <button class="btn btn-outline-primary me-2" @click="exportComplaintsReport">
            <i class="bi bi-file-earmark-pdf"></i> Exporter Réclamations PDF
          </button>
          <button class="btn btn-outline-secondary" @click="exportSettingsReport">
            <i class="bi bi-file-earmark-text"></i> Exporter Rapport Complet
          </button>
        </div>
        <button class="btn btn-success" @click="saveAllSettings">
          <i class="bi bi-save"></i> Sauvegarder Tout
        </button>
      </div>

      <div class="layout-container">
        <!-- Section Réclamations -->
        <div class="complaints-section">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Gestion des Réclamations</h4>
                <button class="btn btn-sm btn-outline-info" @click="refreshComplaints">
                  <i class="bi bi-arrow-clockwise"></i> Actualiser
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Client</th>
                      <th>Sujet</th>
                      <th>Date</th>
                      <th>Statut</th>
                      <th>Assigné à</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="complaint in complaints" :key="complaint.id">
                      <td>
                        <div>
                          <strong>{{ complaint.userName }}</strong>
                          <br>
                          <small class="text-muted">{{ complaint.userEmail }}</small>
                        </div>
                      </td>
                      <td>{{ complaint.subject }}</td>
                      <td>{{ formatDate(complaint.date) }}</td>
                      <td>
                        <span :class="`badge bg-${getStatusColor(complaint.status)}`">
                          {{ getStatusText(complaint.status) }}
                        </span>
                      </td>
                      <td>
                        <span v-if="complaint.assignedTo" class="text-success">
                          {{ complaint.assignedTo }}
                        </span>
                        <span v-else class="text-muted">Non assigné</span>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-info me-1" @click="viewComplaintDetails(complaint)">
                          <i class="bi bi-eye"></i> Voir
                        </button>
                        <button class="btn btn-sm btn-warning me-1" @click="openAssignmentModal(complaint)">
                          <i class="bi bi-person-plus"></i> Assigner
                        </button>
                        <button class="btn btn-sm btn-success" @click="resolveComplaint(complaint.id)" 
                                v-if="complaint.status !== 'resolved'">
                          <i class="bi bi-check-circle"></i> Résoudre
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Sections Configuration -->
        <div class="settings-sections">
          <div class="row">
            <!-- Paramètres de l'Application -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h5><i class="bi bi-gear"></i> Paramètres Application</h5>
                  <div class="app-settings">
                    <!-- Heures d'ouverture -->
                    <div class="mb-3">
                      <label class="form-label"><strong>Heures d'Ouverture</strong></label>
                      <div class="row g-2">
                        <div class="col">
                          <input type="time" class="form-control" v-model="appSettings.openingTime">
                          <small class="text-muted">Ouverture</small>
                        </div>
                        <div class="col">
                          <input type="time" class="form-control" v-model="appSettings.closingTime">
                          <small class="text-muted">Fermeture</small>
                        </div>
                      </div>
                    </div>

                    <!-- Jours d'ouverture -->
                    <div class="mb-3">
                      <label class="form-label"><strong>Jours d'Ouverture</strong></label>
                      <div class="days-checkboxes">
                        <div class="form-check" v-for="day in daysOfWeek" :key="day">
                          <input class="form-check-input" type="checkbox" :id="`day-${day}`" 
                                 v-model="appSettings.openDays" :value="day">
                          <label class="form-check-label" :for="`day-${day}`">
                            {{ day }}
                          </label>
                        </div>
                      </div>
                    </div>

                    <!-- Politiques -->
                    <div class="mb-3">
                      <label class="form-label"><strong>Politiques</strong></label>
                      <div class="policies-settings">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="terms" v-model="appSettings.termsAccepted">
                          <label class="form-check-label" for="terms">
                            Conditions générales
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="privacy" v-model="appSettings.privacyPolicy">
                          <label class="form-check-label" for="privacy">
                            Politique de confidentialité
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="returns" v-model="appSettings.returnPolicy">
                          <label class="form-check-label" for="returns">
                            Politique de retours
                          </label>
                        </div>
                      </div>
                    </div>

                    <!-- Paramètres supplémentaires -->
                    <div class="mb-3">
                      <label class="form-label"><strong>Devise</strong></label>
                      <select class="form-select" v-model="appSettings.currency">
                        <option value="EUR">Euro (€)</option>
                        <option value="USD">Dollar ($)</option>
                        <option value="XOF">Franc FC</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label class="form-label"><strong>Langue par défaut</strong></label>
                      <select class="form-select" v-model="appSettings.defaultLanguage">
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                      </select>
                    </div>

                    <button class="btn btn-primary btn-sm" @click="saveAppSettings">
                      <i class="bi bi-check-lg"></i> Appliquer
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Gestion des Rôles -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="bi bi-people"></i> Gestion des Rôles</h5>
                    <button class="btn btn-sm btn-outline-primary" @click="openRoleModal(null)">
                      <i class="bi bi-plus"></i> Nouveau
                    </button>
                  </div>
                  <div class="roles-management">
                    <div class="role-item mb-3" v-for="role in roles" :key="role.id">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                          <h6 class="mb-1">{{ role.name }}</h6>
                          <small class="text-muted">{{ role.description }}</small>
                          <div class="mt-1">
                            <span class="badge bg-light text-dark me-1" v-for="permission in role.permissions" :key="permission">
                              {{ permission }}
                            </span>
                          </div>
                        </div>
                        <div class="role-actions ms-2">
                          <button class="btn btn-sm btn-outline-secondary me-1" @click="openRoleModal(role)">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-danger" @click="deleteRole(role.id)" 
                                  v-if="role.id !== 1">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Règles de Sécurité -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h5><i class="bi bi-shield-lock"></i> Règles de Sécurité</h5>
                  <div class="security-rules">
                    <div class="security-item mb-3" v-for="rule in securityRules" :key="rule.id">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" :id="`rule-${rule.id}`" 
                               v-model="rule.enabled" @change="updateSecurityRule(rule)">
                        <label class="form-check-label" :for="`rule-${rule.id}`">
                          <strong>{{ rule.name }}</strong>
                        </label>
                      </div>
                      <small class="text-muted">{{ rule.description }}</small>
                    </div>
                  </div>
                  <button class="btn btn-warning btn-sm" @click="applySecuritySettings">
                    <i class="bi bi-shield-check"></i> Appliquer Sécurité
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="alert alert-warning">
      Accès restreint : vous devez être administrateur pour voir cette page.
    </div>

    <!-- Modal Détails Réclamation -->
    <div class="modal fade" id="complaintDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails de la réclamation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedComplaint">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <strong>Client:</strong><br>
                  {{ selectedComplaint.userName }}<br>
                  <small class="text-muted">{{ selectedComplaint.userEmail }}</small>
                </div>
                <div class="mb-3">
                  <strong>Date:</strong><br>
                  {{ formatDate(selectedComplaint.date) }}
                </div>
                <div class="mb-3">
                  <strong>Statut:</strong><br>
                  <span :class="`badge bg-${getStatusColor(selectedComplaint.status)}`">
                    {{ getStatusText(selectedComplaint.status) }}
                  </span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <strong>Assigné à:</strong><br>
                  <span v-if="selectedComplaint.assignedTo" class="text-success">
                    {{ selectedComplaint.assignedTo }}
                  </span>
                  <span v-else class="text-muted">Non assigné</span>
                </div>
                <div class="mb-3">
                  <strong>Sujet:</strong><br>
                  {{ selectedComplaint.subject }}
                </div>
              </div>
            </div>
            <div class="mb-3">
              <strong>Message:</strong>
              <div class="p-3 bg-light rounded mt-2">
                {{ selectedComplaint.message }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Assignation -->
    <div class="modal fade" id="assignmentModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Assigner la réclamation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="complaintToAssign">
            <div class="mb-3">
              <strong>Réclamation:</strong> {{ complaintToAssign.subject }}
            </div>
            <div class="mb-3">
              <label for="assignTo" class="form-label">Assigner à:</label>
              <select class="form-select" id="assignTo" v-model="selectedAssignee">
                <option value="">Sélectionner une personne</option>
                <option v-for="person in assignablePeople" :key="person.id" :value="person.name">
                  {{ person.name }} ({{ person.role }})
                </option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary" @click="assignComplaint" :disabled="!selectedAssignee">
              Assigner
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Gestion des Rôles -->
    <div class="modal fade" id="roleModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditingRole ? 'Modifier le rôle' : 'Nouveau rôle' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="roleName" class="form-label">Nom du rôle</label>
              <input type="text" class="form-control" id="roleName" v-model="roleForm.name">
            </div>
            <div class="mb-3">
              <label for="roleDescription" class="form-label">Description</label>
              <textarea class="form-control" id="roleDescription" v-model="roleForm.description" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Permissions</label>
              <div class="permissions-list">
                <div class="form-check" v-for="permission in availablePermissions" :key="permission">
                  <input class="form-check-input" type="checkbox" :id="`perm-${permission}`" 
                         v-model="roleForm.permissions" :value="permission">
                  <label class="form-check-label" :for="`perm-${permission}`">
                    {{ permission }}
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary" @click="saveRole">
              {{ isEditingRole ? 'Modifier' : 'Créer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Modal } from 'bootstrap'
import { getCurrentUser } from '../services/mockAdminService'

const current = ref(null)
const selectedComplaint = ref(null)
const complaintToAssign = ref(null)
const selectedAssignee = ref('')
const isEditingRole = ref(false)

// Données des réclamations
const complaints = ref([
  {
    id: 1,
    userName: 'Jean Dupont',
    userEmail: 'jean.dupont@email.com',
    subject: 'Problème de livraison',
    message: 'Ma commande n\'est pas arrivée à la date prévue. J\'ai passé commande il y a 5 jours et le statut indique toujours "en préparation". Pouvez-vous me donner des informations sur le délai de livraison ?',
    date: '2024-01-15',
    status: 'pending',
    assignedTo: ''
  },
  {
    id: 2,
    userName: 'Marie Martin',
    userEmail: 'marie.martin@email.com',
    subject: 'Produit défectueux',
    message: 'Le produit que j\'ai reçu ne fonctionne pas correctement. Il s\'agit d\'un mixeur qui fait un bruit anormal et ne mixe pas correctement les aliments.',
    date: '2024-01-14',
    status: 'in_progress',
    assignedTo: 'Employé A'
  }
])

// Paramètres de l'application
const appSettings = reactive({
  openingTime: '08:00',
  closingTime: '18:00',
  openDays: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'],
  termsAccepted: true,
  privacyPolicy: true,
  returnPolicy: false,
  currency: 'EUR',
  defaultLanguage: 'fr'
})

// Gestion des rôles
const roles = ref([
  {
    id: 1,
    name: 'Administrateur',
    description: 'Accès complet au système',
    permissions: ['Gestion utilisateurs', 'Paramètres', 'Rapports', 'Sécurité']
  },
  {
    id: 2,
    name: 'Gérant',
    description: 'Gestion quotidienne',
    permissions: ['Gestion stocks', 'Commandes', 'Employés', 'Clients']
  },
  {
    id: 3,
    name: 'Employé',
    description: 'Tâches basiques',
    permissions: ['Ventes', 'Clients', 'Produits']
  }
])

const roleForm = reactive({
  id: null,
  name: '',
  description: '',
  permissions: []
})

// Règles de sécurité
const securityRules = ref([
  {
    id: 1,
    name: 'Double authentification',
    description: 'Requiert une vérification en deux étapes pour la connexion',
    enabled: false
  },
  {
    id: 2,
    name: 'Expiration des mots de passe',
    description: 'Les mots de passe expirent après 90 jours',
    enabled: true
  },
  {
    id: 3,
    name: 'Déconnexion automatique',
    description: 'Déconnexion après 30 minutes d\'inactivité',
    enabled: true
  }
])

// Données supplémentaires
const assignablePeople = ref([
  { id: 1, name: 'Admin Principal', role: 'Administrateur' },
  { id: 2, name: 'Gérant A', role: 'Gérant' },
  { id: 3, name: 'Employé B', role: 'Employé' }
])

const daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']
const availablePermissions = [
  'Gestion utilisateurs', 'Paramètres', 'Rapports', 'Sécurité', 
  'Gestion stocks', 'Commandes', 'Employés', 'Clients', 'Ventes', 'Produits'
]

// Méthodes utilitaires
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const getStatusText = (status) => {
  const statusMap = {
    pending: 'En attente',
    in_progress: 'En cours',
    resolved: 'Résolu'
  }
  return statusMap[status] || status
}

const getStatusColor = (status) => {
  const colorMap = {
    pending: 'warning',
    in_progress: 'info',
    resolved: 'success'
  }
  return colorMap[status] || 'secondary'
}

// Méthodes réclamations
const viewComplaintDetails = (complaint) => {
  selectedComplaint.value = complaint
  const modal = new Modal(document.getElementById('complaintDetailsModal'))
  modal.show()
}

const openAssignmentModal = (complaint) => {
  complaintToAssign.value = complaint
  selectedAssignee.value = complaint.assignedTo || ''
  const modal = new Modal(document.getElementById('assignmentModal'))
  modal.show()
}

const assignComplaint = () => {
  if (complaintToAssign.value && selectedAssignee.value) {
    const complaint = complaints.value.find(c => c.id === complaintToAssign.value.id)
    if (complaint) {
      complaint.assignedTo = selectedAssignee.value
      complaint.status = 'in_progress'
      const modal = Modal.getInstance(document.getElementById('assignmentModal'))
      modal.hide()
      complaintToAssign.value = null
      selectedAssignee.value = ''
    }
  }
}

const resolveComplaint = (id) => {
  const complaint = complaints.value.find(c => c.id === id)
  if (complaint) {
    complaint.status = 'resolved'
  }
}

const refreshComplaints = () => {
  // Simuler un rafraîchissement des données
  console.log('Actualisation des réclamations...')
}

// Méthodes paramètres application
const saveAppSettings = () => {
  console.log('Paramètres application sauvegardés:', appSettings)
  alert('Paramètres de l\'application sauvegardés avec succès!')
}

// Méthodes gestion des rôles
const openRoleModal = (role) => {
  if (role) {
    // Édition
    isEditingRole.value = true
    Object.assign(roleForm, role)
  } else {
    // Nouveau rôle
    isEditingRole.value = false
    Object.assign(roleForm, {
      id: null,
      name: '',
      description: '',
      permissions: []
    })
  }
  const modal = new Modal(document.getElementById('roleModal'))
  modal.show()
}

const saveRole = () => {
  if (!roleForm.name.trim()) {
    alert('Le nom du rôle est requis')
    return
  }

  if (isEditingRole.value) {
    // Modification
    const index = roles.value.findIndex(r => r.id === roleForm.id)
    if (index !== -1) {
      roles.value[index] = { ...roleForm }
    }
  } else {
    // Nouveau rôle
    const newRole = {
      ...roleForm,
      id: Math.max(...roles.value.map(r => r.id)) + 1
    }
    roles.value.push(newRole)
  }

  const modal = Modal.getInstance(document.getElementById('roleModal'))
  modal.hide()
  alert('Rôle sauvegardé avec succès!')
}

const deleteRole = (roleId) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')) {
    roles.value = roles.value.filter(role => role.id !== roleId)
  }
}

// Méthodes sécurité
const updateSecurityRule = (rule) => {
  console.log(`Règle ${rule.name} ${rule.enabled ? 'activée' : 'désactivée'}`)
}

const applySecuritySettings = () => {
  console.log('Paramètres de sécurité appliqués:', securityRules.value)
  alert('Paramètres de sécurité appliqués avec succès!')
}

// Méthodes exportation
const exportComplaintsReport = () => {
  // Simulation d'export PDF
  const reportData = {
    title: 'Rapport des Réclamations',
    date: new Date().toLocaleDateString('fr-FR'),
    complaints: complaints.value,
    stats: {
      total: complaints.value.length,
      pending: complaints.value.filter(c => c.status === 'pending').length,
      inProgress: complaints.value.filter(c => c.status === 'in_progress').length,
      resolved: complaints.value.filter(c => c.status === 'resolved').length
    }
  }
  
  console.log('Export PDF des réclamations:', reportData)
  alert('Rapport PDF des réclamations généré avec succès!')
  
  // Dans une vraie application, vous utiliseriez une librairie comme jsPDF
  // window.jsPDF().text('Rapport Réclamations', 10, 10).save('rapport-reclamations.pdf')
}

const exportSettingsReport = () => {
  const reportData = {
    title: 'Rapport Complet des Paramètres',
    date: new Date().toLocaleDateString('fr-FR'),
    appSettings: appSettings,
    roles: roles.value,
    securityRules: securityRules.value,
    complaints: {
      total: complaints.value.length,
      data: complaints.value
    }
  }
  
  console.log('Export rapport complet:', reportData)
  alert('Rapport complet exporté avec succès!')
  
  // Téléchargement simulé
  const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(reportData, null, 2))
  const downloadAnchorNode = document.createElement('a')
  downloadAnchorNode.setAttribute("href", dataStr)
  downloadAnchorNode.setAttribute("download", "rapport-parametres.json")
  document.body.appendChild(downloadAnchorNode)
  downloadAnchorNode.click()
  downloadAnchorNode.remove()
}

const saveAllSettings = () => {
  saveAppSettings()
  applySecuritySettings()
  alert('Tous les paramètres ont été sauvegardés!')
}

onMounted(async () => { 
  current.value = await getCurrentUser() 
})
</script>

<style scoped>
.layout-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.complaints-section, .settings-sections {
  width: 100%;
}

.table-responsive {
  overflow-x: auto;
  max-width: 100%;
}

.table {
  width: 100%;
  max-width: 100%;
  table-layout: fixed;
}

.table th,
.table td {
  word-wrap: break-word;
  vertical-align: middle;
}

/* Styles pour les paramètres */
.app-settings .days-checkboxes {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.policies-settings {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.roles-management .role-item {
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #f8f9fa;
}

.role-actions {
  flex-shrink: 0;
}

.permissions-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  padding: 1rem;
}

.security-rules .security-item {
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
  .settings-sections .row {
    flex-direction: column;
  }
  
  .settings-sections .col-md-4 {
    width: 100%;
    margin-bottom: 1rem;
  }
  
  .app-settings .days-checkboxes {
    grid-template-columns: 1fr;
  }
  
  .table {
    font-size: 0.9rem;
  }
  
  .btn {
    font-size: 0.8rem;
  }
}
</style>