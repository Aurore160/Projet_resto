<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Assigner la réclamation</h3>
        <button class="btn-close" @click="$emit('close')">×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label>Employé :</label>
          <select v-model="selectedEmploye" class="form-select">
            <option value="">Sélectionner un employé</option>
            <option v-for="employe in employes" :key="employe.id" :value="employe">
              {{ employe.nom }} - {{ employe.role }}
            </option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Commentaire :</label>
          <textarea v-model="commentaire" class="form-textarea" placeholder="Commentaire optionnel..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('close')">Annuler</button>
        <button class="btn btn-primary" @click="assign" :disabled="!selectedEmploye">
          Assigner
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  reclamation: Object
})

const emit = defineEmits(['close', 'assign'])

const selectedEmploye = ref(null)
const commentaire = ref('')

const employes = ref([
  { id: 1, nom: 'Kevin Samba', role: 'Gestionnaire' },
  { id: 2, nom: 'Sarah Kanza', role: 'Administrateur' },
  { id: 3, nom: 'David Mbala', role: 'Superviseur' },
  { id: 4, nom: 'Lisa Ngoie', role: 'Support client' }
])

const assign = () => {
  emit('assign', {
    reclamationId: props.reclamation.id,
    employeNom: selectedEmploye.value.nom,
    employeRole: selectedEmploye.value.role,
    commentaire: commentaire.value
  })
  emit('close')
}
</script>

<style scoped>
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #3a352f;
}

.form-select, .form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #eae7e2;
  border-radius: 8px;
  font-size: 0.95rem;
}

.form-textarea {
  min-height: 80px;
  resize: vertical;
}
</style>