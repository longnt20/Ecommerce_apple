<template>
  <header class="app-header">
    <!-- Hàng trên (màu đỏ) -->
    <div class="top-bar">
      <div class="container">
        <!-- Logo -->
        <a href="/" class="logo">
          <img :src="logoUrl" alt="CellphoneS Logo">
        </a>

        <!-- Nút danh mục -->
        <button class="header-button category-button">
          <LayoutGrid :size="18" />
          <span>Danh mục</span>
        </button>

        <!-- Component chọn địa điểm -->
        <LocationSelector />

        <!-- Component thanh tìm kiếm -->
        <TheSearchBar />

        <!-- Component giỏ hàng -->
        <CartButton />

        <!-- Component người dùng -->
        <UserButton />
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { LayoutGrid } from 'lucide-vue-next';
import logoUrl from '../../../images/logo-white.png';
// Import các component con đã tạo
import TheSearchBar from './TheSearchBar.vue';
import LocationSelector from './LocationSelector.vue';
import CartButton from './CartButton.vue';
import UserButton from './UserButton.vue';

</script>

<style scoped>

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
  display: flex;
  align-items: center;
  gap: 15px; /* Tạo khoảng cách giữa các phần tử */
}

/* --- Top Bar --- */
.top-bar {
  background-color: var(--primary-color);
  padding: 10px 0;
}

.logo img {
  height: 50px; /* Chỉnh chiều cao logo */
  display: block; /* Bỏ khoảng trắng thừa dưới ảnh */
}

/* Style chung cho các nút trên header */
.header-button {
  background-color: rgba(255, 255, 255, 0.2);
  border: none;
  color: var(--text-color-white);
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  white-space: nowrap; /* Ngăn chữ xuống dòng */
}

.header-button:hover {
  background-color: rgba(255, 255, 255, 0.3);
}

.category-button, .location-selector, .user-button, .cart-button {
  flex-shrink: 0; /* Ngăn các nút bị co lại */
}

.cart-button, .user-button {
  background: none;
}

/* --- Bottom Bar --- */
.bottom-bar {
  background-color: white;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.bottom-bar .container {
    justify-content: flex-start; /* Căn các link từ bên trái */
    gap: 25px;
}

.quick-link {
  text-decoration: none;
  color: var(--text-color-dark);
  font-size: 12px;
  display: flex;
  flex-direction: column;
}

.quick-link span {
  font-size: 11px;
  color: #666;
}

.quick-link.highlight {
  color: var(--primary-color);
  font-weight: bold;
}
</style>