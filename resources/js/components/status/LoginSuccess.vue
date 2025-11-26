<template>
  
</template>



<script setup>
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import { useLoadingStore } from '../../effects/loading'
const loading = useLoadingStore()
const route = useRoute()
const router = useRouter()

onMounted(() => {
  loading.show()
  const token = route.query.token

  if (token) {
    // 🔹 Lưu token
    localStorage.setItem('token', token)

    // 🔹 Hiển thị thông báo toast
    toast.success('Đăng nhập Google thành công!')

    // 🔹 Chuyển về trang chủ sau 1.5 giây
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
