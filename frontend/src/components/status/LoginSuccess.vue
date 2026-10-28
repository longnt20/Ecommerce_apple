<template>
  
</template>



<script setup>
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import 'vue-toastification/dist/index.css'
import { useLoadingStore } from '@/stores/loading';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
const loading = useLoadingStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

onMounted(async () => {
  loading.show()
  const token = route.query.token

  if (token) {
    localStorage.setItem('token', token)
    auth.token = token
    auth.isLoggedIn = true
    
    // Load user data
    await auth.fetchUser()
    
    toast.success('Đăng nhập Google thành công!')

    const cart = useCartStore()
    await cart.syncAfterLogin()   // OK

    setTimeout(() => {
      loading.hide()
      router.push('/')
    }, 1500)
  } else {
    toast.error('Đăng nhập thất bại!')
    loading.hide()
    router.push('/')
  }
})

</script>
