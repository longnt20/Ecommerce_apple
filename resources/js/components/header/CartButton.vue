<template>
  <button class="cart-button">
    <div class="cart-content">
      <div class="cart-icon-wrapper">
        <ShoppingCart :size="20" />
        <span v-if="cartCount > 0" class="cart-badge">
          {{ cartCount > 99 ? '99+' : cartCount }}
        </span>
      </div>
      <span class="cart-label">Giỏ hàng</span>
    </div>
  </button>
</template>

<script setup>
import { ShoppingCart } from 'lucide-vue-next';
import { ref } from 'vue';

// Props hoặc có thể lấy từ store
const cartCount = ref(5);
</script>

<style scoped>
.cart-button {
  background: transparent;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 8px;
  position: relative;
}

.cart-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
  transform: translateY(-2px);
}

.cart-button:active {
  transform: translateY(0);
}

.cart-content {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #333;
}

.cart-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.cart-badge {
  position: absolute;
  top: -10px;
  right: -10px;
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a55 100%);
  color: white;
  border-radius: 10px;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  font-size: 11px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(238, 90, 85, 0.3);
  animation: pulse 2s infinite;
  border: 2px solid white;
}

.cart-label {
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
}

/* Animation for badge */
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(238, 90, 85, 0.7);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(238, 90, 85, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(238, 90, 85, 0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .cart-label {
    display: none;
  }
  
  .cart-button {
    padding: 8px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .cart-content {
    color: #f0f0f0;
  }
  
  .cart-button:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
}
</style>