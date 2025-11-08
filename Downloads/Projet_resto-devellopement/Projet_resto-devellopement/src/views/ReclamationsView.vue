<template>
  <div class="reclamations-view">
    <div class="reclamations-container">
      <div class="page-header">
        <h1>Gestion des Réclamations</h1>
        <p>Consultez et gérez les réclamations des étudiants</p>
      </div>
      
      <!-- Filtres et recherche -->
      <div class="filters">
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Rechercher une réclamation..."
          class="search-input"
        >
        <select v-model="statusFilter" class="status-filter">
          <option value="all">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="resolved">Résolues</option>
          <option value="rejected">Rejetées</option>
        </select>
      </div>

      <!-- Liste des réclamations -->
      <div class="reclamations-list">
        <div 
          v-for="reclamation in filteredReclamations" 
          :key="reclamation.id"
          class="reclamation-card"
          :class="reclamation.status"
        >
          <div class="reclamation-header">
            <h3>{{ reclamation.title }}</h3>
            <span class="status-badge" :class="reclamation.status">
              {{ getStatusText(reclamation.status) }}
            </span>
          </div>
          
          <div class="reclamation-content">
            <p><strong>Étudiant:</strong> {{ reclamation.studentName }}</p>
            <p><strong>Date:</strong> {{ formatDate(reclamation.date) }}</p>
            <p><strong>Description:</strong></p>
            <p class="description">{{ reclamation.description }}</p>
            
            <!-- Section des réponses -->
            <div v-if="reclamation.responses.length" class="responses-section">
              <h4>Réponses proposées:</h4>
              <div 
                v-for="response in reclamation.responses" 
                :key="response.id"
                class="response-card"
              >
                <div class="response-header">
                  <span class="employee-name">{{ response.employeeName }}</span>
                  <span class="response-date">{{ formatDate(response.date) }}</span>
                </div>
                <p class="response-content">{{ response.content }}</p>
                <div class="response-actions" v-if="!response.validated">
                  <button 
                    @click="validateResponse(reclamation.id, response.id)"
                    class="btn-validate"
                  >
                    ✅ Valider
                  </button>
                  <button 
                    @click="rejectResponse(reclamation.id, response.id)"
                    class="btn-reject"
                  >
                    ❌ Rejeter
                  </button>
                </div>
                <div v-else class="validation-status">
                  <span :class="response.validated ? 'validated' : 'rejected'">
                    {{ response.validated ? 'Validée' : 'Rejetée' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Données réactives
const searchQuery = ref('')
const statusFilter = ref('all')
const reclamations = ref([])

// Données simulées
const mockData = [
  {
    id: 1,
    title: "Problème de connexion",
    studentName: "Jean Dupont",
    date: new Date('2024-01-15'),
    description: "Impossible de me connecter à la plateforme depuis 3 jours...",
    status: 'pending',
    responses: [
      {
        id: 1,
        employeeName: "Marie Lopez",
        date: new Date('2024-01-16'),
        content: "Veuillez réinitialiser votre mot de passe",
        validated: false
      }
    ]
  }
]

// Computed properties
const filteredReclamations = computed(() => {
  return reclamations.value.filter(reclamation => {
    const matchesSearch = reclamation.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         reclamation.studentName.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesStatus = statusFilter.value === 'all' || reclamation.status === statusFilter.value
    return matchesSearch && matchesStatus
  })
})

// Méthodes
const getStatusText = (status) => {
  const statusMap = {
    pending: 'En attente',
    resolved: 'Résolue',
    rejected: 'Rejetée'
  }
  return statusMap[status]
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR')
}

const validateResponse = async (reclamationId, responseId) => {
  const reclamation = reclamations.value.find(r => r.id === reclamationId)
  const response = reclamation.responses.find(r => r.id === responseId)
  response.validated = true
  reclamation.status = 'resolved'
}

const rejectResponse = async (reclamationId, responseId) => {
  const reclamation = reclamations.value.find(r => r.id === reclamationId)
  const response = reclamation.responses.find(r => r.id === responseId)
  response.validated = false
  reclamation.status = 'rejected'
}

// Cycle de vie
onMounted(() => {
  reclamations.value = mockData
})
</script>

<style scoped>
.reclamations-view {
  padding: 2rem;
  min-height: 100vh;
}

.reclamations-container {
  max-width: 1000px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  text-align: center;
}

.page-header h1 {
  color: #333;
  margin-bottom: 0.5rem;
  font-size: 2.5rem;
}

.page-header p {
  color: #666;
  font-size: 1.1rem;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 30px;
  justify-content: center;
}

.search-input, .status-filter {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.search-input {
  width: 300px;
}

.reclamation-card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  background: white;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: transform 0.2s ease;
}

.reclamation-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.reclamation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.reclamation-header h3 {
  margin: 0;
  color: #333;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8em;
  font-weight: 600;
}

.pending .status-badge { background: #fff3cd; color: #856404; }
.resolved .status-badge { background: #d4edda; color: #155724; }
.rejected .status-badge { background: #f8d7da; color: #721c24; }

.description {
  white-space: pre-wrap;
  margin: 10px 0;
  line-height: 1.6;
}

.responses-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.responses-section h4 {
  margin-bottom: 15px;
  color: #333;
}

.response-card {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
}

.response-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.9em;
  color: #666;
  margin-bottom: 10px;
}

.response-content {
  margin: 10px 0;
  line-height: 1.5;
}

.response-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.btn-validate, .btn-reject {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-validate { 
  background: #28a745; 
  color: white; 
}
.btn-validate:hover {
  background: #218838;
}

.btn-reject { 
  background: #dc3545; 
  color: white; 
}
.btn-reject:hover {
  background: #c82333;
}

.validation-status .validated { 
  color: #28a745; 
  font-weight: 600;
}
.validation-status .rejected { 
  color: #dc3545; 
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .reclamations-view {
    padding: 1rem;
  }
  
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-input {
    width: 100%;
  }
  
  .reclamation-header {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }
}
</style>