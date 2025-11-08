import { createRouter, createWebHistory } from "vue-router";
import CommandeView from '../views/CommandeView.vue';
import CommandemodifView from '../views/CommandemodifView.vue';
import ComptegerantView from '../views/ComptegerantView.vue';
import MenuprofilView from '../views/MenuprofilView.vue';
import GerantView from '../views/GerantView.vue';
import EmployeView from '../views/EmployeView.vue';
import ReclamationsView from '../views/ReclamationsView.vue';
import StatistiquesView from '../views/StatistiquesView.vue'; // Import du composant Statistiques

const routes = [
  {
    path: '/',
    redirect: '/gerant'
  },
  {
    path: '/menu',
    redirect: '/gerant'
  },
  {
    path: '/gerant',
    name: 'gerant',
    component: GerantView,
    children: [
      {
        path: '',
        name: 'gerant-accueil',
        component: StatistiquesView // Page d'accueil = Statistiques
      },
      {
        path: 'statistiques', // Nouvelle route pour les statistiques
        name: 'statistiques-gerant',
        component: StatistiquesView
      },
      {
        path: 'commandes',
        name: 'commandes-gerant',
        component: CommandeView
      },
      {
        path: 'comptes',
        name: 'comptes-gerant',
        component: ComptegerantView
      },
      {
        path: 'profil',
        name: 'profil-gerant',
        component: MenuprofilView
      },
      {
        path: 'reclamations', // Correction en minuscules
        name: 'reclamations-gerant',
        component: ReclamationsView
      }
    ]
  },
  {
    path: '/employe',
    name: 'employe',
    component: EmployeView,
    children: [
      {
        path: 'commandes',
        name: 'commandes-employe',
        component: CommandemodifView
      },
      {
        path: 'profil',
        name: 'profil-employe',
        component: MenuprofilView
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/gerant'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Intercepteur pour rediriger /menu
router.beforeEach((to, from, next) => {
  if (to.path === '/menu') {
    next('/gerant');
  } else {
    next();
  }
});

export default router;