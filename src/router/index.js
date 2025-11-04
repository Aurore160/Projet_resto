import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/HomeView.vue')
  },
  {
    path: '/game',
    name: 'Game',
    component: () => import('../views/game.vue')
  },
  {
    path: '/panier',
    name: 'Panier',
    component: () => import('../views/panier.vue')
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'), 
    children: [
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('../views/DashboardView.vue')
      }
      ,
      {
        path: 'utilisateurs',
        name: 'AdminUtilisateurs',
        component: () => import('../views/UtilisateursView.vue')
      },
      {
        path: 'employes',
        name: 'AdminEmployes',
        component: () => import('../views/EmployesView.vue')
      },
      {
        path: 'commandes',
        name: 'AdminCommandes',
        component: () => import('../views/CommandesView.vue')
      },
      {
        path: 'menu',
        name: 'AdminMenu',
        component: () => import('../views/MenuView.vue')
      },
      
      {
        path: 'statistiques',
        name: 'AdminStatistiques',
        component: () => import('../views/StatistiquesView.vue')
      }
      ,
      {
        path: 'parametres',
        name: 'AdminParametres',
        component: () => import('../views/ParametresView.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router