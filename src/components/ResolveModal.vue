<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Résoudre la réclamation</h3>
        <button class="btn-close" @click="$emit('close')">×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label>Solution apportée :</label>
          <textarea v-model="resolution" class="form-textarea" placeholder="Décrivez la solution apportée..." required></textarea>
        </div>
        
        <div class="form-group">
          <label>Satisfaction client :</label>
          <div class="satisfaction-stars">
            <span 
              v-for="star in 5" 
              :key="star" 
              class="star"
              :class="{ active: star <= satisfaction }"
              @click="satisfaction = star"
            >
              ★
            </span>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('close')">Annuler</button>
        <button class="btn btn-success" @click="resolve" :disabled="!resolution">
          Marquer comme résolu
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

const emit = defineEmits(['close', 'resolve'])

const resolution = ref('')
const satisfaction = ref(0)

const resolve = () => {
  emit('resolve', {
    reclamationId: props.reclamation.id,
    resolution: resolution.value,
    satisfaction: satisfaction.value
  })
  emit('close')
}
</script>

<style scoped>
.satisfaction-stars {
  display: flex;
  gap: 8px;
  font-size: 24px;
}

.star {
  color: #ddd;
  cursor: pointer;
  transition: color 0.2s;
}

.star.active {
  color: #ffc107;
}

.star:hover {
  color: #ffc107;
}
</style>