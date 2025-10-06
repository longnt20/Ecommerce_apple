import { createRouter, createWebHistory } from 'vue-router'
import LoginSuccess from '../components/status/LoginSuccess.vue'
import App from '../App.vue'

const routes = [
  { path: '/login-success', name: 'login-success', component: LoginSuccess },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
