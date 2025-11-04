import { ref } from 'vue'

const toasts = ref([])
let id = 1

export function addToast({ title = '', message = '', type = 'info', timeout = 4000 } = {}){
  const t = { id: id++, title, message, type }
  toasts.value.push(t)
  if(timeout>0){ setTimeout(()=>{ removeToast(t.id) }, timeout) }
  return t.id
}

export function removeToast(id){ toasts.value = toasts.value.filter(t=>t.id!==id) }

export function useToasts(){ return { toasts } }

export default { addToast, removeToast, useToasts }
