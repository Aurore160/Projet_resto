import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Accueil',
    component: () => import('../views/Accueil.vue')
  },
  {
    path: '/menu',
    name: 'MenuSimple',
    component: () => import('../views/MenuSimple.vue')
  },
  {
    path: '/commander',
    name: 'MenuAchat',
    component: () => import('../views/MenuAchat.vue')
  },
  {
    path: '/paiement',
    name: 'Paiement',
    component: () => import('../views/Paiement.vue'),
    props: (route) => ({
      cartItems: JSON.parse(route.query.cart || '[]')
    })
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router