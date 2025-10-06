<template>
  <div class="flex flex-col items-center justify-center h-screen">
    <h2 class="text-lg font-semibold">Đang xử lý đăng nhập...</h2>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const route = useRoute()
const router = useRouter()

onMounted(() => {
  const token = route.query.token

  if (token) {
    // 🔹 Lưu token
    localStorage.setItem('token', token)

    // 🔹 Hiển thị thông báo toast
    toast.success('Đăng nhập Google thành công!')

    // 🔹 Chuyển về trang chủ sau 1.5 giây
    setTimeout(() => {
      router.push('/')
    }, 1500)
  } else {
    toast.error('Đăng nhập thất bại!')
    router.push('/login')
  }
})
</script>
