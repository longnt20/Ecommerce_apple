import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import "@fortawesome/fontawesome-free/css/all.min.css";
import 'bootstrap/dist/css/bootstrap.min.css'

import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'  
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
const app = createApp(App)

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)   

app.use(pinia)
app.use(router)
app.use(Toast, {
  position: 'top-right',
  timeout: 2000
})
app.mount('#app')

