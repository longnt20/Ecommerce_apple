import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import "@fortawesome/fontawesome-free/css/all.min.css";
import 'bootstrap/dist/css/bootstrap.min.css'

import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'  

const app = createApp(App)

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)   

app.use(pinia)
app.use(router)

app.mount('#app')

