<template>
  <div class="security-rules">
    <h3>Règles de sécurité</h3>
    <div v-for="r in rules" :key="r.name" class="rule-item">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <strong>{{ r.label }}</strong>
          <p class="small text-muted mb-0">{{ r.description }}</p>
        </div>
        <div>
          <label class="form-check form-switch">
            <!-- avoid v-model directly on iterated item in case of non-object; use checked + explicit handler -->
            <input class="form-check-input" type="checkbox" :checked="!!r.enabled" @change="onToggle(r, $event)" />
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import * as svc from '../services/mockAdminService'

const rules = ref([])
onMounted(async ()=>{ rules.value = await svc.getRules() })

async function onToggle(r, ev){
  try{
    const val = !!(ev && ev.target && ev.target.checked)
    await svc.toggleRule(r.name, val)
  }catch(e){ console.error('toggle rule error', e) }
  rules.value = await svc.getRules()
}
</script>

<style scoped>
.rule-item{ padding: .75rem 0; border-bottom: 1px solid rgba(0,0,0,.06) }
</style>
