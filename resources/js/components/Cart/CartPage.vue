<template>
  <div class="cart-container">
    <h2 class="cart-title">Giỏ hàng của bạn</h2>

    <div v-if="cart.items.length === 0" class="cart-empty">
      <p>Giỏ hàng trống.</p>
    </div>

    <div v-else class="cart-content">

      <!-- CART LIST -->
      <div class="cart-items">
        <div
          v-for="item in cart.items"
          :key="item.id"
          class="cart-item"
        >
          <img :src="item.variant?.thumbnail_url" class="item-thumbnail" />

          <div class="item-info">
            <h3 class="item-name">{{ item.product.name }}</h3>

            <div class="item-variant">
              <span v-if="item.variant?.color_label">
                Màu: <strong>{{ item.variant.color_label }}</strong>
              </span>

              <span v-if="item.variant?.storage">
                | Dung lượng: <strong>{{ item.variant.storage }}</strong>
              </span>
            </div>

            <div class="item-price">
              {{ formatPrice(item.price_at_add) }}
            </div>
          </div>

          <div class="item-qty">
            <button @click="update(item, item.quantity - 1)" :disabled="item.quantity <= 1">-</button>
            <span>{{ item.quantity }}</span>
            <button @click="update(item, item.quantity + 1)">+</button>
          </div>

          <div class="item-total">
            {{ formatPrice(item.quantity * item.price_at_add) }}
          </div>

          <button class="item-remove" @click="cart.removeItem(item.id)"><Trash2 /></button>
        </div>
      </div>

      <!-- CART SUMMARY -->
      <div class="cart-summary">
        <h3>Tóm tắt đơn hàng</h3>

        <div class="summary-row">
          <span>Tạm tính</span>
          <span>{{ formatPrice(cart.totalPrice) }}</span>
        </div>

        <div class="summary-row total">
          <span>Tổng cộng</span>
          <span>{{ formatPrice(cart.totalPrice) }}</span>
        </div>

        <router-link to="/checkout?mode=cart">
          <button class="checkout-btn">Tiến hành thanh toán</button>
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue"
import { useCartStore } from "../../effects/cart"
import { Trash2 } from "lucide-vue-next"

const cart = useCartStore()

onMounted(() => {
  cart.loadCart()
})

const update = (item, qty) => {
  cart.updateQty(item.id, qty)
}
const formatPrice = (value) => {
  return Number(value).toLocaleString("vi-VN") + " đ"
}
</script>

<style>
.cart-container {
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}

.cart-title {
  font-size: 26px;
  margin-bottom: 20px;
  font-weight: bold;
}

.cart-empty {
  text-align: center;
  padding: 30px;
  font-size: 18px;
  color: #555;
}

.cart-content {
  display: flex;
  gap: 25px;
}

.cart-items {
  flex: 3;
}

.cart-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px 0;
  border-bottom: 1px solid #ddd;
}

.item-thumbnail {
  width: 100px;
  height: 100px;
  object-fit: contain;
}

.item-info {
  flex: 2;
}

.item-name {
  font-size: 18px;
  margin-bottom: 5px;
}

.item-variant {
  font-size: 14px;
  color: #555;
}

.item-price {
  font-size: 16px;
  font-weight: bold;
  margin-top: 8px;
}

.item-qty {
  display: flex;
  align-items: center;
  gap: 8px;
}

.item-qty button {
  width: 28px;
  height: 28px;
  border: 1px solid #ccc;
  background: white;
  cursor: pointer;
}

.item-total {
  font-size: 16px;
  font-weight: bold;
  width: 120px;
  text-align: right;
}

.item-remove {
  background: none;
  border: none;
  cursor: pointer;
  color: red;
}

.cart-summary {
  flex: 1;
  border: 1px solid #ddd;
  padding: 20px;
  border-radius: 8px;
  background: #fafafa;
  height: fit-content;
}

.cart-summary h3 {
  font-size: 20px;
  margin-bottom: 20px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 16px;
}

.summary-row.total {
  font-weight: bold;
  font-size: 18px;
  border-top: 1px solid #ddd;
  padding-top: 12px;
}

.checkout-btn {
  width: 100%;
  padding: 12px;
  background: #ff424e;
  border: none;
  color: white;
  font-size: 18px;
  margin-top: 20px;
  cursor: pointer;
  border-radius: 5px;
}

.checkout-btn:hover {
  background: #e63946;
}

@media (max-width: 768px) {
  .cart-content {
    flex-direction: column;
  }

  .item-total {
    display: none;
  }
}
</style>
