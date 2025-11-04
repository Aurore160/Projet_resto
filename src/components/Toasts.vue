<template>
  <div class="toasts-root" aria-live="polite">
    <div v-for="t in toasts" :key="t.id" :class="['toast-item', 'toast-'+t.type]">
      <div class="toast-header">
        <strong class="me-auto">{{ t.title }}</strong>
        <button class="btn-close" @click="remove(t.id)"></button>
      </div>
      <div class="toast-body">{{ t.message }}</div>
    </div>
  </div>
</template>

<script setup>
import { useToasts, removeToast } from '../services/toastService'
const { toasts } = useToasts()
function remove(id){ removeToast(id) }
</script>

<style scoped>
.toasts-root{ position: fixed; top: 1rem; right: 1rem; z-index: 2000; display:flex; flex-direction:column; gap:.5rem }
.toast-item{ min-width: 220px; background: #fff; border-radius:6px; box-shadow:0 6px 18px rgba(0,0,0,.08); overflow:hidden; border:1px solid rgba(0,0,0,.06) }
.toast-header{ display:flex; align-items:center; padding:.4rem .6rem; border-bottom:1px solid rgba(0,0,0,.04) }
.toast-body{ padding:.6rem }
.toast-info{ border-left:4px solid #0d6efd }
.toast-success{ border-left:4px solid #198754 }
.toast-warning{ border-left:4px solid #ffc107 }
.toast-danger{ border-left:4px solid #dc3545 }
.btn-close{ background:transparent; border:0; width:26px; height:26px }
</style>
