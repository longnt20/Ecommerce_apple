<template>
  
</template>



<script setup>
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import { useLoadingStore } from '@/stores/loading';
import { useCartStore } from '@/stores/cart';
const loading = useLoadingStore()
const route = useRoute()
const router = useRouter()

onMounted(async () => {
  loading.show()
  const token = route.query.token

  if (token) {
    localStorage.setItem('token', token)
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
