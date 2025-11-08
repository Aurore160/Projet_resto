import { createApp } from 'vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import App from './App.vue'
import './assets/css/variable.css'
import router from './router'


// Supprimer la ligne: import router from './router'

const app = createApp(App)  // Supprimer .use(router)

app.use(router)
app.mount('#app')

