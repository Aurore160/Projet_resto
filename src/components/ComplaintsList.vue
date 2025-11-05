<template>
  <div class="complaints-list card p-3">
    <Toasts />
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 class="mb-0">Réclamations clients</h3>
      <div class="d-flex gap-2">
        <input v-model="query" class="form-control form-control-sm" placeholder="Rechercher..." />
        <button class="btn btn-sm btn-outline-secondary" @click="exportCSV">Export CSV</button>
      </div>
    </div>

    <div class="filters row g-2 mb-3">
      <div class="col-auto">
        <select v-model="filterStatus" class="form-select form-select-sm">
          <option value="">Tous statuts</option>
          <option value="open">Ouvert</option>
          <option value="assigned">Assigné</option>
          <option value="resolved">Résolu</option>
        </select>
      </div>
      <div class="col-auto">
        <input type="date" v-model="dateFrom" class="form-control form-control-sm" />
      </div>
      <div class="col-auto">
        <input type="date" v-model="dateTo" class="form-control form-control-sm" />
      </div>
      <div class="col-auto">
        <select v-model.number="filterAssigned" class="form-select form-select-sm">
          <option :value="0">Tous les employés</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.nom }} ({{ u.role }})</option>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary" @click="resetFilters">Réinitialiser</button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Client</th>
            <th>Sujet</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Assigné à</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtered" :key="c.id">
            <td>{{ c.id }}</td>
            <td>{{ c.client }}</td>
            <td>{{ c.subject }}</td>
            <td>{{ c.date }}</td>
            <td><span :class="['badge', c.status==='resolved' ? 'bg-success' : c.status==='assigned' ? 'bg-warning' : 'bg-secondary']">{{ c.status }}</span></td>
            <td>{{ findUserName(c.assignedTo) }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-1" @click="openDetail(c)">Voir</button>
              <button class="btn btn-sm btn-outline-success me-1" @click="quickAssign(c)">Assigner</button>
              <button class="btn btn-sm btn-outline-danger" @click="resolve(c)">Marquer résolu</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- detail modal -->
    <div v-if="selected" class="modal-backdrop">
      <div class="modal-card">
        <h4>Détails réclamation #{{ selected.id }}</h4>
        <p><strong>Client:</strong> {{ selected.client }}</p>
        <p><strong>Sujet:</strong> {{ selected.subject }}</p>
        <p><strong>Message:</strong> {{ selected.message }}</p>
        <p><strong>Statut:</strong> {{ selected.status }}</p>
        <p><strong>Assigné:</strong> {{ findUserName(selected.assignedTo) }}</p>
        <div class="mb-2">
          <label class="form-label">Assigner à</label>
          <select v-model.number="assignTo" class="form-select">
            <option :value="0">-- choisir --</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.nom }} ({{ u.role }})</option>
          </select>
        </div>
        <div class="d-flex gap-2 justify-content-end">
          <button class="btn btn-sm btn-secondary" @click="closeDetail">Fermer</button>
          <button class="btn btn-sm btn-primary" @click="assign(selected.id)">Assigner</button>
          <button class="btn btn-sm btn-success" @click="resolve(selected)">Résoudre</button>
        </div>
        <div class="mt-3">
          <h5>Historique</h5>
          <ul>
            <li v-for="(h, i) in selected.history" :key="i">{{ h.at }} - {{ findUserName(h.by) }} - {{ h.action }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import * as svc from '../services/mockAdminService'
import { addToast } from '../services/toastService'
import Toasts from './Toasts.vue'

const query = ref('')
const complaints = ref([])
const users = ref([])
const selected = ref(null)
const assignTo = ref(0)

// filter controls
const filterStatus = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const filterAssigned = ref(0)

const load = async () => {
  complaints.value = await svc.getComplaints()
  users.value = await svc.getUsers()
}

onMounted(load)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  let list = complaints.value.slice()
  if(q){ list = list.filter(c => (c.client + ' ' + c.subject + ' ' + c.message).toLowerCase().includes(q)) }
  if(filterStatus.value){ list = list.filter(c => c.status === filterStatus.value) }
  if(filterAssigned.value){ list = list.filter(c => c.assignedTo === filterAssigned.value) }
  if(dateFrom.value){ list = list.filter(c => c.date >= dateFrom.value) }
  if(dateTo.value){ list = list.filter(c => c.date <= dateTo.value) }
  return list
})

function findUserName(id){ if(!id) return '-' ; const u = users.value.find(x=>x.id===id); return u ? u.nom : 'N/A' }

function openDetail(c){ selected.value = JSON.parse(JSON.stringify(c)); assignTo.value = c.assignedTo || 0 }
function closeDetail(){ selected.value = null }

async function assign(id){ const uid = assignTo.value; if(!uid) return alert('Choisir un employé'); await svc.assignComplaint(id, uid); await load(); closeDetail(); addToast({ title:'Assignation', message:`Réclamation #${id} assignée.`, type:'success' }) }

async function quickAssign(c){ const to = users.value.find(u=>u.role==='employe')?.id || users.value[0]?.id; if(!to) return alert('No users'); await svc.assignComplaint(c.id, to); await load(); addToast({ title:'Assignation', message:`Réclamation #${c.id} assignée à ${findUserName(to)}`, type:'success' }) }

async function resolve(c){ if(!confirm('Marquer comme résolu?')) return; await svc.markResolved(c.id, 1); await load(); if(selected.value){ selected.value.status='resolved' } ; addToast({ title:'Résolu', message:`Réclamation #${c.id} marquée comme résolue`, type:'success' }) }

function resetFilters(){ filterStatus.value=''; dateFrom.value=''; dateTo.value=''; filterAssigned.value=0; query.value='' }

function exportCSV(){
  const rows = filtered.value
  if(!rows.length){ addToast({ title:'Export', message:'Aucune réclamation à exporter', type:'warning' }); return }
  const headers = ['id','client','subject','message','date','status','assignedTo']
  const csv = [headers.join(',')].concat(rows.map(r => headers.map(h => `"${(r[h]||'').toString().replace(/"/g,'""')}"`).join(','))).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `reclamations_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  addToast({ title:'Export', message:'CSV téléchargé', type:'info' })
}

</script>

<style scoped>
.modal-backdrop{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); z-index:60 }
.modal-card{ background:white; padding:16px; border-radius:8px; width:600px; max-width:95% }
.complaints-list .table td, .complaints-list .table th{ vertical-align:middle }
.complaints-list .filters .form-control, .complaints-list .filters .form-select{ min-width:150px }
.complaints-list .card{ border-radius:10px }
</style>
