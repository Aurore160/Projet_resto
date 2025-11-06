<template>
  <div class="dashboard-complet">
    <!-- Header -->
    <div class="dashboard-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col">
            <h1 class="dashboard-title">
              <i class="bi bi-speedometer2"></i><strong> 
              Reclamation et Gestion de la Rentabilité 
            </strong></h1>
            <p class="dashboard-subtitle">Gestion des réclamations et analyse financière</p>
          </div>
          <div class="col-auto">
            <div class="btn-group">
              <button class="btn btn-primary" @click="exportReclamationsCSV">
                <i class="bi bi-file-earmark-excel"></i>
                Export Réclamations
              </button>
              <button class="btn btn-success" @click="exportRentabiliteCSV">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                Export Rentabilité
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DEUX TABLEAUX COMPLETS -->
    <div class="container-fluid mt-4">
      
      <!-- TABLEAU 1: RÉCLAMATIONS SIMPLIFIÉ -->
      <div class="row mb-5">
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="bi bi-chat-left-text me-2"></i>
                Gestion des Réclamations Clients
                <span class="badge bg-primary ms-2">{{ filteredReclamations.length }}</span>
              </h5>
              <div class="header-stats">
                <span class="badge bg-warning me-2">{{ ouvertReclamations }} Ouvertes</span>
                <span class="badge bg-info me-2">{{ assigneReclamations }} Assignées</span>
                <span class="badge bg-success">{{ resoluReclamations }} Résolues</span>
              </div>
            </div>
            
            <div class="card-body p-0">
              <!-- Barre de contrôle réclamations -->
              <div class="control-panel p-3 border-bottom">
                <div class="row align-items-center">
                  <div class="col-md-4 mb-2 mb-md-0">
                    <div class="search-box">
                      <i class="bi bi-search search-icon"></i>
                      <input
                        v-model="searchReclamations"
                        type="text"
                        class="search-input"
                        placeholder="Rechercher une réclamation..."
                      />
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                      <select v-model="filterStatut" class="filter-select" @change="applyFiltersReclamations">
                        <option value="">Tous les statuts</option>
                        <option value="ouvert">Ouvert</option>
                        <option value="assigné">Assigné</option>
                        <option value="résolu">Résolu</option>
                      </select>

                      <select v-model="filterPriorite" class="filter-select" @change="applyFiltersReclamations">
                        <option value="">Toutes priorités</option>
                        <option value="faible">Faible</option>
                        <option value="moyenne">Moyenne</option>
                        <option value="élevée">Élevée</option>
                        <option value="urgente">Urgente</option>
                      </select>

                      <button class="btn btn-outline-secondary btn-sm" @click="resetFiltersReclamations">
                        <i class="bi bi-arrow-clockwise"></i>
                        Réinitialiser
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tableau réclamations SIMPLIFIÉ -->
              <div class="table-container">
                <table class="detailed-table">
                  <thead>
                    <tr>
                      <th class="text-center">#</th>
                      <th>Client</th>
                      <th>Sujet</th>
                      <th>Type</th>
                      <th>Priorité</th>
                      <th>Date</th>
                      <th>Statut</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(reclamation, index) in filteredReclamations" :key="reclamation.id">
                      <td class="text-center">{{ index + 1 }}</td>
                      
                      <td>
                        <div class="client-info">
                          <div class="client-avatar">
                            {{ getInitials(reclamation.clientNom) }}
                          </div>
                          <div class="client-details">
                            <div class="client-name">{{ reclamation.clientNom }}</div>
                            <div class="client-contact">{{ reclamation.clientEmail }}</div>
                          </div>
                        </div>
                      </td>
                      
                      <td>
                        <div class="sujet-text">
                          {{ reclamation.sujet }}
                        </div>
                      </td>
                      
                      <td>
                        <span :class="['type-badge', `type-${reclamation.type}`]">
                          {{ formatType(reclamation.type) }}
                        </span>
                      </td>
                      
                      <td>
                        <span :class="['priorite-badge', `priorite-${reclamation.priorite}`]">
                          {{ formatPriorite(reclamation.priorite) }}
                        </span>
                      </td>
                      
                      <td>
                        <div class="date-info">
                          {{ formatDate(reclamation.date) }}
                        </div>
                      </td>
                      
                      <td>
                        <span :class="['statut-badge', `statut-${reclamation.statut}`]">
                          {{ formatStatut(reclamation.statut) }}
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <div class="action-buttons">
                          <button class="btn-action btn-view" @click="viewReclamationDetails(reclamation)" title="Voir détails">
                            <i class="bi bi-eye"></i>
                          </button>
                          
                          <button v-if="reclamation.statut !== 'résolu'" 
                                  class="btn-action btn-assign" 
                                  @click="assignReclamation(reclamation)" 
                                  title="Assigner">
                            <i class="bi bi-person-plus"></i>
                          </button>
                          
                          <button v-if="reclamation.statut !== 'résolu'" 
                                  class="btn-action btn-resolve" 
                                  @click="resolveReclamation(reclamation)" 
                                  title="Résoudre">
                            <i class="bi bi-check-lg"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    
                    <tr v-if="filteredReclamations.length === 0">
                      <td colspan="8" class="text-center py-4">
                        <div class="empty-state">
                          <i class="bi bi-chat-left display-4 text-muted"></i>
                          <p class="mt-2 mb-0">Aucune réclamation trouvée</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLEAU 2: RENTABILITÉ DIVISÉ EN DEUX -->
      <div class="row">
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="bi bi-graph-up me-2"></i>
                Analyse de Rentabilité par Plat
                <span class="badge bg-primary ms-2">{{ filteredAnalyses.length }}</span>
              </h5>
              <div class="header-stats">
                <span class="badge bg-success me-2">{{ platsRentables }} Rentables</span>
                <span class="badge bg-danger me-2">{{ platsDeficitaires }} Déficitaires</span>
                <span class="badge bg-warning">{{ platsEquilibre }} Équilibre</span>
              </div>
            </div>
            
            <div class="card-body p-0">
              <!-- Barre de contrôle rentabilité -->
              <div class="control-panel p-3 border-bottom">
                <div class="row align-items-center">
                  <div class="col-md-4 mb-2 mb-md-0">
                    <div class="search-box">
                      <i class="bi bi-search search-icon"></i>
                      <input
                        v-model="searchRentabilite"
                        type="text"
                        class="search-input"
                        placeholder="Rechercher un plat..."
                      />
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                      <select v-model="filterRentabilite" class="filter-select" @change="applyFiltersRentabilite">
                        <option value="">Tous statuts</option>
                        <option value="Rentable">Rentable</option>
                        <option value="Déficitaire">Déficitaire</option>
                        <option value="Équilibre">Équilibre</option>
                      </select>

                      <select v-model="sortBy" class="filter-select" @change="applySortRentabilite">
                        <option value="profit_net">Tri par Profit</option>
                        <option value="chiffre_affaires_total">Tri par CA</option>
                        <option value="marge_beneficiaire">Tri par Marge</option>
                      </select>

                      <button class="btn btn-outline-secondary btn-sm" @click="resetFiltersRentabilite">
                        <i class="bi bi-arrow-clockwise"></i>
                        Réinitialiser
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PARTIE 1: Informations de base et ventes -->
              <div class="table-container border-bottom">
                <table class="detailed-table">
                  <thead>
                    <tr>
                      <th>Plat</th>
                      <th class="text-center">Prix</th>
                      <th class="text-center">Ventes</th>
                      <th class="text-center">Commandes</th>
                      <th class="text-end">Chiffre d'Affaires</th>
                      <th class="text-end">Dépenses</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="analyse in filteredAnalyses" :key="analyse.id_menuitem">
                      <td>
                        <div class="plat-info">
                          <div class="plat-avatar">
                            {{ getInitials(analyse.nom) }}
                          </div>
                          <div class="plat-details">
                            <div class="plat-name">{{ analyse.nom }}</div>
                            <div class="plat-id">ID: {{ analyse.id_menuitem }}</div>
                          </div>
                        </div>
                      </td>
                      
                      <td class="text-center">
                        <span class="prix-value">
                          {{ formatCurrency(analyse.prix) }}
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <span class="ventes-badge">
                          {{ analyse.nombre_ventes }}
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <span class="commandes-badge">
                          {{ analyse.nombre_commandes }}
                        </span>
                      </td>
                      
                      <td class="text-end">
                        <div class="montant ca">
                          {{ formatCurrency(analyse.chiffre_affaires_total) }}
                        </div>
                      </td>
                      
                      <td class="text-end">
                        <div class="montant depenses">
                          {{ formatCurrency(analyse.depenses_total) }}
                        </div>
                      </td>
                      
                      <td class="text-center">
                        <div class="action-buttons">
                          <button class="btn-action btn-details" @click="viewRentabiliteDetails(analyse)" title="Voir détails complets">
                            <i class="bi bi-eye"></i>
                          </button>
                          
                          <button class="btn-action btn-chart" @click="viewChart(analyse)" title="Graphique">
                            <i class="bi bi-bar-chart"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- PARTIE 2: Analyse financière -->
              <div class="table-container">
                <table class="detailed-table">
                  <thead>
                    <tr>
                      <th>Plat</th>
                      <th class="text-end">Profit Net</th>
                      <th class="text-center">Marge</th>
                      <th class="text-center">Statut</th>
                      <th class="text-center">Dépenses Moy.</th>
                      <th class="text-center">Nb. Dépenses</th>
                      <th class="text-center">Quantité Vendue</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="analyse in filteredAnalyses" :key="analyse.id_menuitem + '-details'">
                      <td>
                        <div class="plat-info">
                          <div class="plat-avatar">
                            {{ getInitials(analyse.nom) }}
                          </div>
                          <div class="plat-details">
                            <div class="plat-name">{{ analyse.nom }}</div>
                          </div>
                        </div>
                      </td>
                      
                      <td class="text-end">
                        <div :class="['montant', 'profit', analyse.profit_net >= 0 ? 'positif' : 'negatif']">
                          {{ formatCurrency(analyse.profit_net) }}
                        </div>
                      </td>
                      
                      <td class="text-center">
                        <span :class="['marge-badge', getMargeClass(analyse.marge_beneficiaire)]">
                          {{ analyse.marge_beneficiaire }}%
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <span :class="['statut-badge', `statut-${analyse.statut_rentabilite.toLowerCase()}`]">
                          {{ analyse.statut_rentabilite }}
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <div class="moyenne-depense">
                          {{ formatCurrency(analyse.moyenne_depense) }}
                        </div>
                      </td>
                      
                      <td class="text-center">
                        <span class="depenses-count">
                          {{ analyse.nombre_depenses }}
                        </span>
                      </td>
                      
                      <td class="text-center">
                        <span class="quantite-badge">
                          {{ analyse.total_quantite_vendue }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DÉTAILS RÉCLAMATION -->
    <div v-if="selectedReclamation" class="modal-overlay" @click.self="selectedReclamation = null">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Détails de la Réclamation</h3>
          <button class="btn-close" @click="selectedReclamation = null">×</button>
        </div>
        
        <div class="modal-body">
          <div class="section" v-if="selectedReclamation">
            <h4>Informations Client</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Nom :</label>
                <span>{{ selectedReclamation.clientNom }}</span>
              </div>
              <div class="info-item">
                <label>Email :</label>
                <span>{{ selectedReclamation.clientEmail }}</span>
              </div>
              <div class="info-item">
                <label>Téléphone :</label>
                <span>{{ selectedReclamation.clientTelephone }}</span>
              </div>
            </div>
          </div>

          <div class="section">
            <h4>Détails Réclamation</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Sujet :</label>
                <span>{{ selectedReclamation.sujet }}</span>
              </div>
              <div class="info-item">
                <label>Type :</label>
                <span :class="['type-badge', `type-${selectedReclamation.type}`]">
                  {{ formatType(selectedReclamation.type) }}
                </span>
              </div>
              <div class="info-item">
                <label>Priorité :</label>
                <span :class="['priorite-badge', `priorite-${selectedReclamation.priorite}`]">
                  {{ formatPriorite(selectedReclamation.priorite) }}
                </span>
              </div>
              <div class="info-item">
                <label>Statut :</label>
                <span :class="['statut-badge', `statut-${selectedReclamation.statut}`]">
                  {{ formatStatut(selectedReclamation.statut) }}
                </span>
              </div>
              <div class="info-item">
                <label>Date :</label>
                <span>{{ formatDate(selectedReclamation.date) }}</span>
              </div>
            </div>
          </div>

          <div class="section">
            <h4>Description</h4>
            <div class="description-box">
              {{ selectedReclamation.description }}
            </div>
          </div>

          <div class="section" v-if="selectedReclamation.employeAssigné">
            <h4>Assignation</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Assigné à :</label>
                <span>{{ selectedReclamation.employeAssigné }}</span>
              </div>
              <div class="info-item">
                <label>Rôle :</label>
                <span>{{ selectedReclamation.employeRole }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="selectedReclamation = null">Fermer</button>
          <button v-if="selectedReclamation.statut !== 'résolu'" 
                  class="btn btn-primary" 
                  @click="assignReclamation(selectedReclamation)">
            Assigner
          </button>
          <button v-if="selectedReclamation.statut !== 'résolu'" 
                  class="btn btn-success" 
                  @click="resolveReclamation(selectedReclamation)">
            Marquer comme résolu
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DÉTAILS RENTABILITÉ -->
    <div v-if="selectedAnalyse" class="modal-overlay" @click.self="selectedAnalyse = null">
      <div class="modal-content large-modal">
        <div class="modal-header">
          <h3>Détails de Rentabilité - {{ selectedAnalyse.nom }}</h3>
          <button class="btn-close" @click="selectedAnalyse = null">×</button>
        </div>
        
        <div class="modal-body">
          <div class="section">
            <h4>Informations de Base</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Plat :</label>
                <span>{{ selectedAnalyse.nom }}</span>
              </div>
              <div class="info-item">
                <label>Prix Unitaire :</label>
                <span>{{ formatCurrency(selectedAnalyse.prix) }}</span>
              </div>
              <div class="info-item">
                <label>Prix Moyen Réel :</label>
                <span>{{ formatCurrency(selectedAnalyse.prix_moyen_reel) }}</span>
              </div>
              <div class="info-item">
                <label>ID :</label>
                <span>{{ selectedAnalyse.id_menuitem }}</span>
              </div>
            </div>
          </div>

          <div class="section">
            <h4>Performance des Ventes</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Nombre de Ventes :</label>
                <span>{{ selectedAnalyse.nombre_ventes }}</span>
              </div>
              <div class="info-item">
                <label>Quantité Totale Vendue :</label>
                <span>{{ selectedAnalyse.total_quantite_vendue }}</span>
              </div>
              <div class="info-item">
                <label>Nombre de Commandes :</label>
                <span>{{ selectedAnalyse.nombre_commandes }}</span>
              </div>
            </div>
          </div>

          <div class="section">
            <h4>Analyse Financière</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Chiffre d'Affaires :</label>
                <span class="montant ca">{{ formatCurrency(selectedAnalyse.chiffre_affaires_total) }}</span>
              </div>
              <div class="info-item">
                <label>Dépenses Total :</label>
                <span class="montant depenses">{{ formatCurrency(selectedAnalyse.depenses_total) }}</span>
              </div>
              <div class="info-item">
                <label>Nombre de Dépenses :</label>
                <span>{{ selectedAnalyse.nombre_depenses }}</span>
              </div>
              <div class="info-item">
                <label>Dépense Moyenne :</label>
                <span>{{ formatCurrency(selectedAnalyse.moyenne_depense) }}</span>
              </div>
            </div>
          </div>

          <div class="section">
            <h4>Rentabilité</h4>
            <div class="info-grid">
              <div class="info-item">
                <label>Profit Net :</label>
                <span :class="['montant', 'profit', selectedAnalyse.profit_net >= 0 ? 'positif' : 'negatif']">
                  {{ formatCurrency(selectedAnalyse.profit_net) }}
                </span>
              </div>
              <div class="info-item">
                <label>Marge Bénéficiaire :</label>
                <span :class="['marge-badge', getMargeClass(selectedAnalyse.marge_beneficiaire)]">
                  {{ selectedAnalyse.marge_beneficiaire }}%
                </span>
              </div>
              <div class="info-item">
                <label>Statut :</label>
                <span :class="['statut-badge', `statut-${selectedAnalyse.statut_rentabilite.toLowerCase()}`]">
                  {{ selectedAnalyse.statut_rentabilite }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="selectedAnalyse = null">Fermer</button>
          <button class="btn btn-primary" @click="viewChart(selectedAnalyse)">
            Voir Graphique
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Données réclamations
const reclamations = ref([
  {
    id: 1,
    clientNom: 'Jean Dupont',
    clientEmail: 'jean.dupont@email.com',
    clientTelephone: '+243 970 123 456',
    sujet: 'Commande livrée avec 45 minutes de retard',
    description: 'La commande #CMD-001 était prévue pour 19h00 mais est arrivée à 19h45. Le livreur était désagréable et ne s\'est pas excusé du retard.',
    type: 'livraison',
    priorite: 'élevée',
    date: '2024-01-15T14:30:00',
    statut: 'ouvert',
    employeAssigné: null,
    employeRole: null
  },
  {
    id: 2,
    clientNom: 'Marie Lambert',
    clientEmail: 'marie.lambert@email.com', 
    clientTelephone: '+243 971 234 567',
    sujet: 'Plat froid à la réception',
    description: 'Le poulet grillé commandé était complètement froid à l\'arrivée. La sauce était figée. Très déçue par la qualité.',
    type: 'qualité',
    priorite: 'moyenne',
    date: '2024-01-14T19:15:00',
    statut: 'assigné',
    employeAssigné: 'Kevin Samba',
    employeRole: 'Gestionnaire qualité'
  }
])

// Données rentabilité
const analyses = ref([])

// États modals
const selectedReclamation = ref(null)
const selectedAnalyse = ref(null)

// Filtres réclamations
const searchReclamations = ref('')
const filterStatut = ref('')
const filterPriorite = ref('')

// Filtres rentabilité
const searchRentabilite = ref('')
const filterRentabilite = ref('')
const sortBy = ref('profit_net')

// Computed réclamations
const filteredReclamations = computed(() => {
  let list = reclamations.value.filter(reclamation => {
    const matchSearch =
      reclamation.clientNom.toLowerCase().includes(searchReclamations.value.toLowerCase()) ||
      reclamation.sujet.toLowerCase().includes(searchReclamations.value.toLowerCase())

    const matchStatut = filterStatut.value ? reclamation.statut === filterStatut.value : true
    const matchPriorite = filterPriorite.value ? reclamation.priorite === filterPriorite.value : true

    return matchSearch && matchStatut && matchPriorite
  })

  return list.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const totalReclamations = computed(() => reclamations.value.length)
const ouvertReclamations = computed(() => reclamations.value.filter(r => r.statut === 'ouvert').length)
const assigneReclamations = computed(() => reclamations.value.filter(r => r.statut === 'assigné').length)
const resoluReclamations = computed(() => reclamations.value.filter(r => r.statut === 'résolu').length)

// Computed rentabilité
const filteredAnalyses = computed(() => {
  let list = analyses.value.filter(analyse => {
    const matchSearch = analyse.nom.toLowerCase().includes(searchRentabilite.value.toLowerCase())
    const matchRentabilite = filterRentabilite.value ? analyse.statut_rentabilite === filterRentabilite.value : true

    return matchSearch && matchRentabilite
  })

  // Trier selon le critère choisi
  return list.sort((a, b) => {
    if (sortBy.value === 'profit_net') return b.profit_net - a.profit_net
    if (sortBy.value === 'chiffre_affaires_total') return b.chiffre_affaires_total - a.chiffre_affaires_total
    if (sortBy.value === 'marge_beneficiaire') return b.marge_beneficiaire - a.marge_beneficiaire
    return 0
  })
})

const platsRentables = computed(() => analyses.value.filter(a => a.statut_rentabilite === 'Rentable').length)
const platsDeficitaires = computed(() => analyses.value.filter(a => a.statut_rentabilite === 'Déficitaire').length)
const platsEquilibre = computed(() => analyses.value.filter(a => a.statut_rentabilite === 'Équilibre').length)

// Méthodes réclamations
const applyFiltersReclamations = () => {}
const resetFiltersReclamations = () => {
  searchReclamations.value = ''
  filterStatut.value = ''
  filterPriorite.value = ''
}

// Méthodes rentabilité
const applyFiltersRentabilite = () => {}
const applySortRentabilite = () => {}
const resetFiltersRentabilite = () => {
  searchRentabilite.value = ''
  filterRentabilite.value = ''
  sortBy.value = 'profit_net'
}

// Méthodes actions
const viewReclamationDetails = (reclamation) => {
  selectedReclamation.value = reclamation
}

const assignReclamation = (reclamation) => {
  alert(`Assigner réclamation #${reclamation.id}`)
}

const resolveReclamation = (reclamation) => {
  alert(`Résoudre réclamation #${reclamation.id}`)
}

const viewRentabiliteDetails = (analyse) => {
  selectedAnalyse.value = analyse
}

const viewChart = (analyse) => {
  alert(`Graphique pour: ${analyse.nom}`)
}

// Utilitaires
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatType = (type) => {
  const types = {
    'qualité': 'Qualité',
    'service': 'Service',
    'livraison': 'Livraison',
    'technique': 'Technique'
  }
  return types[type] || type
}

const formatPriorite = (priorite) => {
  const priorites = {
    'faible': 'Faible',
    'moyenne': 'Moyenne',
    'élevée': 'Élevée',
    'urgente': 'Urgente'
  }
  return priorites[priorite] || priorite
}

const formatStatut = (statut) => {
  const statuts = {
    'ouvert': 'Ouvert',
    'assigné': 'Assigné',
    'résolu': 'Résolu'
  }
  return statuts[statut] || statut
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getMargeClass = (marge) => {
  if (marge >= 30) return 'excellente'
  if (marge >= 20) return 'bonne'
  if (marge >= 10) return 'moyenne'
  if (marge >= 0) return 'faible'
  return 'negative'
}

// Export CSV
const exportReclamationsCSV = () => {
  alert('Export réclamations CSV')
}

const exportRentabiliteCSV = () => {
  alert('Export rentabilité CSV')
}

// Chargement des données
onMounted(() => {
  analyses.value = [
    {
      id_menuitem: 1,
      nom: 'Poulet Braisé aux Herbes',
      prix: 25,
      prix_moyen_reel: 25,
      total_quantite_vendue: 180,
      nombre_ventes: 150,
      nombre_commandes: 120,
      chiffre_affaires_total: 3750,
      depenses_total: 2250,
      nombre_depenses: 45,
      moyenne_depense: 50,
      profit_net: 1500,
      marge_beneficiaire: 40.0,
      statut_rentabilite: 'Rentable'
    },
    {
      id_menuitem: 2,
      nom: 'Poisson Grillé Sauce Citron',
      prix: 30,
      prix_moyen_reel: 30,
      total_quantite_vendue: 96,
      nombre_ventes: 80,
      nombre_commandes: 75,
      chiffre_affaires_total: 2400,
      depenses_total: 2000,
      nombre_depenses: 32,
      moyenne_depense: 62.5,
      profit_net: 400,
      marge_beneficiaire: 16.7,
      statut_rentabilite: 'Rentable'
    }
  ]
})
</script>

<style scoped>
.dashboard-complet {
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.large-modal {
  max-width: 800px;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #eae7e2;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #3a352f;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #777;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #eae7e2;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.section {
  margin-bottom: 24px;
}

.section h4 {
  color: #e0d6be;
  margin-bottom: 12px;
  font-size: 1.1rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item label {
  font-weight: 600;
  color: #777;
  font-size: 0.9rem;
  margin-bottom: 4px;
}

.description-box {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #e0d6be;
}

/* En-têtes de cartes */
.header-stats .badge {
  font-size: 0.75rem;
}

/* Tables détaillées */
.detailed-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.detailed-table th {
  background-color: #f5f3f0;
  color: #3a352f;
  font-weight: 600;
  padding: 12px 8px;
  border-bottom: 2px solid #eae7e2;
  white-space: nowrap;
}

.detailed-table td {
  padding: 10px 8px;
  border-bottom: 1px solid #f0eee9;
  vertical-align: middle;
}

/* Informations clients/plats */
.client-info, .plat-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.client-avatar, .plat-avatar {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: #e0d6be;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.client-details, .plat-details {
  min-width: 0;
}

.client-name, .plat-name {
  font-weight: 600;
  color: #3a352f;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.plat-id {
  font-size: 0.7rem;
  color: #777;
}

.contact-info {
  font-size: 0.8rem;
}

.contact-email {
  color: #3a352f;
  margin-bottom: 2px;
}

.contact-phone {
  color: #777;
}

/* Textes */
.sujet-text {
  font-weight: 500;
  color: #3a352f;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.description-text {
  color: #666;
  max-width: 200px;
  font-size: 0.8rem;
  line-height: 1.3;
}

.date-info {
  font-size: 0.8rem;
  color: #666;
  white-space: nowrap;
}

/* Badges */
.type-badge, .priorite-badge, .statut-badge, .marge-badge {
  padding: 4px 8px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-block;
  white-space: nowrap;
}

.type-qualité { background: #fff3cd; color: #856404; }
.type-service { background: #d1ecf1; color: #0c5460; }
.type-livraison { background: #d4edda; color: #155724; }
.type-technique { background: #e2e3e5; color: #383d41; }

.priorite-badge {
  padding: 3px 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.priorite-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.priorite-faible .priorite-dot { background: #28a745; }
.priorite-moyenne .priorite-dot { background: #ffc107; }
.priorite-élevée .priorite-dot { background: #fd7e14; }
.priorite-urgente .priorite-dot { background: #dc3545; }

.statut-ouvert { background: #fff3cd; color: #856404; }
.statut-assigné { background: #cce7ff; color: #004085; }
.statut-résolu { background: #d4edda; color: #155724; }
.statut-rentable { background: #d4edda; color: #155724; }
.statut-déficitaire { background: #f8d7da; color: #721c24; }
.statut-équilibre { background: #fff3cd; color: #856404; }

.marge-badge.excellente { background: #d4edda; color: #155724; }
.marge-badge.bonne { background: #cce7ff; color: #004085; }
.marge-badge.moyenne { background: #fff3cd; color: #856404; }
.marge-badge.faible { background: #f8d7da; color: #721c24; }
.marge-badge.negative { background: #f5c6cb; color: #721c24; }

/* Valeurs numériques */
.prix-value, .prix-moyen {
  font-weight: 600;
  color: #3a352f;
  font-size: 0.9rem;
}

.quantite-badge, .ventes-badge, .commandes-badge, .depenses-count {
  background: #e9ecef;
  color: #495057;
  padding: 4px 8px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
}

.montant {
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
}

.montant.ca { color: #28a745; }
.montant.depenses { color: #dc3545; }
.montant.moyenne-depense { color: #6c757d; }
.montant.profit.positif { color: #28a745; }
.montant.profit.negatif { color: #dc3545; }

/* Actions */
.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.btn-action {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}

.btn-view { background: #1976d2; }
.btn-assign { background: #ff9800; }
.btn-resolve { background: #4caf50; }
.btn-details { background: #1976d2; }
.btn-chart { background: #6f42c1; }
.btn-edit { background: #fd7e14; }

.btn-action:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

/* Barres de contrôle */
.control-panel {
  background: #faf9f7;
}

.search-box {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 35px;
  border: 1px solid #eae7e2;
  border-radius: 6px;
  font-size: 0.9rem;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #777;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #eae7e2;
  border-radius: 6px;
  font-size: 0.9rem;
  background: white;
}

/* États vides */
.empty-state {
  color: #6c757d;
}

.empty-state i {
  opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
  .detailed-table {
    font-size: 0.8rem;
  }
  
  .detailed-table th,
  .detailed-table td {
    padding: 6px 4px;
  }
  
  .header-stats {
    display: none;
  }
}

</style>

