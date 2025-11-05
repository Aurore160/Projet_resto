import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './assets/style.css';
import 'animate.css';
import { createPinia } from 'pinia';


const app = createApp(App)
app.use(router)
app.use(createPinia())
app.mount('#app')

// createApp(App).mount('#app')
