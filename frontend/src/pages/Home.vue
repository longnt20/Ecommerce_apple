<template>
  <div class="container">
    <MainContent />
    <HomePage />
  </div>
</template>
<style scoped>
  .container {
      width: 1200px;
  margin: 0 auto;
  background-color: #ffffff;
  }
</style>
<script setup>
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import 'vue-toastification/dist/index.css'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

import HomePage from '@/components/HomePage/HomPage.vue'
import MainContent from '@/components/navbar/MainContent.vue';

const auth = useAuthStore()
const cart = useCartStore()
const route = useRoute()

onMounted(async () => {
  // Handle Google login callback
  const token = route.query.token
  const loginSuccess = route.query.login
  
  if (token && loginSuccess === 'success') {
    // Clear URL params
    window.history.replaceState({}, document.title, window.location.pathname)
    
    // Set auth state
    localStorage.setItem('token', token)
    auth.token = token
    auth.isLoggedIn = true
    
    // Load user data
    await auth.fetchUser()
    
    // Sync cart
    await cart.syncAfterLogin()
    
    toast.success('Đăng nhập Google thành công!')
  }
})
</script>
