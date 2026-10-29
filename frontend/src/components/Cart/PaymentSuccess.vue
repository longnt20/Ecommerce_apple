<template>
  <main class="container py-5 text-center">
    <h2>{{ title }}</h2>
    <p v-if="error" role="alert">{{ error }}</p>
    <div v-if="order">
      <p>Đơn hàng #{{ order.id }}</p>
      <p>Tổng tiền: {{ Number(order.final_amount).toLocaleString('vi-VN') }}₫</p>
      <p>Phương thức: {{ order.payment_method }}</p>
      <p v-if="order.payment_status !== 'paid'">Đơn chưa được xác nhận đã thanh toán.</p>
      <button class="btn btn-primary m-2" @click="loadOrder">Cập nhật trạng thái</button>
      <button v-if="canRetry" class="btn btn-outline-primary m-2" :disabled="retrying" @click="retryPayment">Tiếp tục thanh toán</button>
    </div>
    <router-link class="btn btn-outline-secondary m-2" to="/profile">Xem đơn hàng của tôi</router-link>
    <router-link class="btn btn-outline-secondary m-2" to="/">Về trang chủ</router-link>
  </main>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { fetchPaymentOrder, paymentTitle } from '@/services/paymentResult'

const route = useRoute()
const order = ref(null)
const error = ref('')
const retrying = ref(false)
const title = computed(() => paymentTitle(order.value))
const canRetry = computed(() => order.value?.payment_method.toLowerCase() === 'vnpay'
  && order.value.payment_status === 'unpaid' && order.value.status !== 'cancelled')
async function loadOrder() {
  error.value = ''
  if (!/^\d+$/.test(String(route.query.order_id || ''))) {
    error.value = 'Không tìm thấy đơn để xác minh. Vui lòng xem lịch sử đơn hàng.'
    return
  }
  try {
    order.value = await fetchPaymentOrder(route.query, api)
  } catch {
    error.value = 'Không thể đọc đơn hàng. Vui lòng đăng nhập đúng tài khoản và thử lại.'
  }
}
async function retryPayment() {
  retrying.value = true
  try {
    const { data } = await api.post('/orders/' + order.value.id + '/payment')
    window.location.assign(data.payment_url)
  } catch {
    error.value = 'Lượt thanh toán không còn khả dụng. Vui lòng liên hệ cửa hàng.'
  } finally {
    retrying.value = false
  }
}
onMounted(loadOrder)
</script>
