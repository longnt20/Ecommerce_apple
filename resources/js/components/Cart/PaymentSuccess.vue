<template>
  <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light py-5">
    <div class="card shadow-lg border-0" style="max-width: 520px; width: 100%; border-radius: 1rem;">
      <div class="card-body text-center p-5">

        <!-- Icon -->
        <div class="d-flex justify-content-center mb-4">
  <div :class="['p-4 rounded-circle', paymentMethodClass]">
    <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 13l4 4L19 7"></path>
    </svg>
  </div>
</div>


        <!-- Title -->
        <h2 class="fw-bold mb-2 text-dark">Thanh toán thành công!</h2>
        <p class="text-muted mb-4">Cảm ơn bạn đã mua hàng tại cửa hàng.</p>

        <!-- Order info -->
        <div class="bg-light rounded p-4 text-start mb-4">
          <div class="d-flex justify-content-between border-bottom py-2">
            <span class="text-secondary">Mã đơn hàng:</span>
            <span class="fw-semibold">#{{ order.id }}</span>
          </div>

          <div class="d-flex justify-content-between border-bottom py-2">
            <span class="text-secondary">Tổng tiền:</span>
            <span class="fw-semibold">{{ formatPrice(order.final_amount) }}₫</span>
          </div>

            <div class="d-flex justify-content-between border-bottom py-2">
            <span class="text-secondary">Phương thức:</span>
            <span class="fw-semibold">{{ paymentMethodDisplay }}</span>
            </div>


          <div class="d-flex justify-content-between py-2">
            <span class="text-secondary">Thời gian:</span>
            <span class="fw-semibold">{{ orderTime }}</span>
          </div>
        </div>

        <!-- Buttons -->
        <div class="d-grid gap-3">
          <button @click="goToOrders" class="btn btn-primary btn-lg">
            Xem đơn hàng của tôi
          </button>

          <button @click="goHome" class="btn btn-outline-secondary btn-lg">
            Quay về trang chủ
          </button>
        </div>

      </div>
    </div>
  </div>
</template>



<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// Lấy router
const route = useRoute()
const router = useRouter()

// Order data
const order = ref({
  id: route.query.order_id || null,
  final_amount: route.query.amount || 0,
})

// Phương thức thanh toán
const paymentMethod = ref(route.query.method || 'VNPay') // lấy từ query hoặc default VNPay

// Hiển thị màu icon dựa trên phương thức
const paymentMethodClass = computed(() => {
  return paymentMethod.value.toLowerCase() === 'cod'
    ? 'bg-warning bg-opacity-10 text-warning'
    : 'bg-success bg-opacity-10 text-success'
})

// Format hiển thị phương thức
const paymentMethodDisplay = computed(() => {
  return paymentMethod.value.toUpperCase() === 'COD' ? 'COD' : 'VNPay'
})

// Format thời gian
const orderTime = computed(() => {
  const d = new Date()
  return Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(d)
})

// Format tiền
const formatPrice = (n) => Number(n).toLocaleString('vi-VN')

// Navigation
const goHome = () => router.push('/')
const goToOrders = () => router.push('/orders')
</script>


<style scoped>
</style>
