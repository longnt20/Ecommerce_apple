<template><p role="status">{{ message }}</p></template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const message = ref('Đang xác minh đăng nhập…')
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

onMounted(async () => {
  const code = route.query.code
  const verifier = sessionStorage.getItem('google_code_verifier')
  sessionStorage.removeItem('google_code_verifier')
  window.history.replaceState({}, '', window.location.pathname)
  try {
    if (typeof code !== 'string' || !verifier) throw new Error('Invalid handoff')
    const { data } = await api.post('/auth/google/exchange', { code, code_verifier: verifier })
    localStorage.setItem('token', data.token)
    auth.token = data.token
    auth.user = data.user
    auth.isLoggedIn = true
    await useCartStore().syncAfterLogin()
    router.replace('/')
  } catch {
    message.value = 'Đăng nhập không thành công hoặc phiên đã hết hạn. Vui lòng thử lại.'
  }
})
</script>
