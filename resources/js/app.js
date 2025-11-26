import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import "@fortawesome/fontawesome-free/css/all.min.css";
import 'bootstrap/dist/css/bootstrap.min.css'

import { createPinia } from 'pinia';

createApp(App).use(createPinia()).use(router).mount('#app')
