// Simple in-memory mock service for admin features (complaints, users, permissions, rules)
const users = [
  { id: 1, nom: 'Admin One', email: 'admin@example.com', role: 'admin' },
  { id: 2, nom: 'Alice Employe', email: 'alice@example.com', role: 'employe' },
  { id: 3, nom: 'Marc Gerant', email: 'marc@example.com', role: 'gestionnaire' }
]

let complaints = [
  { id: 101, client: 'Jean Dupont', subject: 'Plate froid', message: 'Le plat était froid à la livraison', date: '2025-11-01', status: 'open', assignedTo: null, history: [] },
  { id: 102, client: 'Aline Moke', subject: 'Livraison tardive', message: 'La commande est arrivée 50min en retard', date: '2025-11-02', status: 'assigned', assignedTo: 2, history: [{ by: 2, action: 'assigned', at: '2025-11-02' }] },
  { id: 103, client: 'Kevin Samba', subject: 'Produit manquant', message: 'Il manquait une boisson', date: '2025-11-03', status: 'resolved', assignedTo: 3, history: [{ by: 3, action: 'resolved', at: '2025-11-03' }] }
]

let rules = [
  { name: 'require2FA', label: 'Exiger 2FA', description: "Exiger une authentification à deux facteurs pour les comptes administrateurs.", enabled: false },
  { name: 'blockDeletion', label: 'Bloquer suppression', description: "Empêche la suppression définitive des données sans audit.", enabled: false },
  { name: 'requireStrongPasswords', label: 'Mots de passe forts', description: "Imposer une complexité minimale pour les mots de passe.", enabled: true },
  { name: 'auditLogging', label: 'Journalisation audit', description: "Enregistrer les actions critiques pour l'audit (assignations, suppressions...).", enabled: true }
]

export function getUsers() { return Promise.resolve(users.slice()) }
export function getUserById(id){ return Promise.resolve(users.find(u=>u.id===id)) }
export function getCurrentUser(){
  // for now return the first admin user as the logged-in user
  const u = users.find(x=>x.role==='admin') || users[0]
  return Promise.resolve(u)
}

export function getComplaints(){ return Promise.resolve(complaints.slice()) }
export function getComplaint(id){ return Promise.resolve(complaints.find(c=>c.id===id)) }

export function assignComplaint(id, userId){
  const c = complaints.find(x=>x.id===id)
  if(!c) return Promise.reject(new Error('Not found'))
  c.assignedTo = userId
  c.status = 'assigned'
  c.history.push({ by: userId, action: 'assigned', at: new Date().toISOString().slice(0,10) })
  return Promise.resolve(c)
}

export function markResolved(id, resolverId){
  const c = complaints.find(x=>x.id===id)
  if(!c) return Promise.reject(new Error('Not found'))
  c.status = 'resolved'
  c.history.push({ by: resolverId, action: 'resolved', at: new Date().toISOString().slice(0,10) })
  c.assignedTo = resolverId
  return Promise.resolve(c)
}

export function deleteComplaint(id){
  complaints = complaints.filter(x=>x.id!==id)
  return Promise.resolve()
}

export function getRules(){ return Promise.resolve(rules.map(r=>Object.assign({}, r))) }
export function toggleRule(name, value){ const r = rules.find(x=>x.name===name); if(r) r.enabled = !!value; return Promise.resolve(rules.map(r=>Object.assign({}, r))) }

export function setUserRole(userId, role){ const u = users.find(x=>x.id===userId); if(u) u.role=role; return Promise.resolve(u) }
export default { getUsers, getComplaints, getComplaint, assignComplaint, markResolved, deleteComplaint, getRules, toggleRule, setUserRole, getCurrentUser }
