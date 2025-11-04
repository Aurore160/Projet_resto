<template>
  <div v-if="show" class="userform-backdrop">
    <div class="userform-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ isEdit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}</h4>
        <button class="btn-close" @click="close" aria-label="Fermer"></button>
      </div>

      <form @submit.prevent="submit">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nom</label>
            <input v-model="local.nom" required class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input v-model="local.email" type="email" required class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input v-model="local.telephone" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Rôle</label>
            <select v-model="local.role" class="form-select">
              <option value="client">Client</option>
              <option value="employe">Employé</option>
              <option value="gestionnaire">Gestionnaire</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Statut</label>
            <select v-model="local.statut" class="form-select">
              <option value="actif">Actif</option>
              <option value="inactif">Inactif</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Notes (optionnel)</label>
            <textarea v-model="local.notes" rows="2" class="form-control"></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-secondary" @click="close">Annuler</button>
          <button type="submit" class="btn btn-primary">{{ isEdit ? 'Enregistrer' : 'Ajouter' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
const props = defineProps({ modelValue: { type: Boolean, default: false }, user: { type: Object, default: null } })
const emits = defineEmits(['update:modelValue','save'])

const show = ref(props.modelValue)
watch(()=>props.modelValue, v => show.value = v)

const local = ref({ id: null, nom:'', email:'', telephone:'', role:'client', statut:'actif', notes:'' })

watch(()=>props.user, u => {
  if(u){ local.value = Object.assign({}, u) } else { local.value = { id:null, nom:'', email:'', telephone:'', role:'client', statut:'actif', notes:'' } }
}, { immediate: true })

const isEdit = computed(()=>!!(local.value && local.value.id))

function close(){ emits('update:modelValue', false) }

function submit(){
  const payload = Object.assign({}, local.value)
  if(!payload.id) payload.id = Date.now()
  emits('save', payload)
  emits('update:modelValue', false)
}
</script>

<style scoped>
.userform-backdrop{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(2,6,23,0.4); z-index:1200 }
.userform-card{ width:720px; max-width:95%; background:white; border-radius:12px; padding:18px; box-shadow:0 12px 40px rgba(2,6,23,0.2) }
.userform-card .form-label{ font-size:0.85rem; color:#374151 }
.userform-card .btn-close{ background:transparent; border:0; width:28px; height:28px }
</style>
