<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Détails de la réclamation</h3>
        <button class="btn-close" @click="$emit('close')">×</button>
      </div>
      
      <div class="modal-body" v-if="reclamation">
        <div class="section">
          <h4>Informations client</h4>
          <div class="info-grid">
            <div class="info-item">
              <label>Nom :</label>
              <span>{{ reclamation.clientNom }}</span>
            </div>
            <div class="info-item">
              <label>Email :</label>
              <span>{{ reclamation.clientEmail }}</span>
            </div>
            <div class="info-item">
              <label>Téléphone :</label>
              <span>{{ reclamation.clientTelephone }}</span>
            </div>
          </div>
        </div>

        <div class="section">
          <h4>Réclamation</h4>
          <div class="info-grid">
            <div class="info-item">
              <label>Sujet :</label>
              <span>{{ reclamation.sujet }}</span>
            </div>
            <div class="info-item">
              <label>Type :</label>
              <span :class="['type-badge', `type-${reclamation.type}`]">
                {{ formatType(reclamation.type) }}
              </span>
            </div>
            <div class="info-item">
              <label>Priorité :</label>
              <span :class="['priorite-badge', `priorite-${reclamation.priorite}`]">
                {{ formatPriorite(reclamation.priorite) }}
              </span>
            </div>
            <div class="info-item">
              <label>Statut :</label>
              <span :class="['statut-badge', `statut-${reclamation.statut}`]">
                {{ formatStatut(reclamation.statut) }}
              </span>
            </div>
          </div>
        </div>

        <div class="section">
          <h4>Description</h4>
          <div class="description-box">
            {{ reclamation.description }}
          </div>
        </div>

        <div class="section" v-if="reclamation.historique">
          <h4>Historique</h4>
          <div class="timeline">
            <div v-for="(event, index) in reclamation.historique" :key="index" class="timeline-item">
              <div class="timeline-date">{{ formatDate(event.date) }}</div>
              <div class="timeline-content">
                <strong>{{ event.action }}</strong>
                <p>{{ event.details }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="section" v-if="reclamation.resolution">
          <h4>Résolution</h4>
          <div class="resolution-box">
            {{ reclamation.resolution }}
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('close')">Fermer</button>
        <button 
          v-if="reclamation.statut !== 'résolu'" 
          class="btn btn-primary" 
          @click="$emit('assign', reclamation)"
        >
          Assigner
        </button>
        <button 
          v-if="reclamation.statut !== 'résolu'" 
          class="btn btn-success" 
          @click="$emit('resolve', reclamation)"
        >
          Marquer comme résolu
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  reclamation: Object
})

const emit = defineEmits(['close', 'assign', 'resolve'])

const formatType = (type) => {
  const types = { 'qualité': 'Qualité', 'service': 'Service', 'livraison': 'Livraison', 'technique': 'Technique', 'autre': 'Autre' }
  return types[type] || type
}

const formatPriorite = (priorite) => {
  const priorites = { 'faible': 'Faible', 'moyenne': 'Moyenne', 'élevée': 'Élevée', 'urgente': 'Urgente' }
  return priorites[priorite] || priorite
}

const formatStatut = (statut) => {
  const statuts = { 'ouvert': 'Ouvert', 'assigné': 'Assigné', 'résolu': 'Résolu' }
  return statuts[statut] || statut
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
</script>

<style scoped>
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
  max-width: 700px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
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

.description-box, .resolution-box {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #e0d6be;
}

.timeline {
  border-left: 2px solid #e0d6be;
  margin-left: 10px;
}

.timeline-item {
  padding: 12px 0 12px 20px;
  position: relative;
}

.timeline-item:before {
  content: '';
  position: absolute;
  left: -6px;
  top: 20px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #e0d6be;
}

.timeline-date {
  font-size: 0.85rem;
  color: #777;
  margin-bottom: 4px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #eae7e2;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
</style>