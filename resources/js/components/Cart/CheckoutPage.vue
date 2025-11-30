<template>
  <div>
    <h2>Thanh toán</h2>

    <div>
      <label>Địa chỉ nhận hàng</label>
      <input v-model="address" />
    </div>

    <button @click="checkout">Đặt hàng</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'
import { useCartStore } from '../../effects/cart'


const address = ref("")
const cart = useCartStore()

const checkout = async () => {
  const res = await api.post('/checkout', {
    address: address.value
  })
  alert("Đặt hàng thành công!")
  cart.clear()
}
</script>
