<template>
  <header class="app-header">
    <!-- Hàng trên (màu đỏ) -->
    <div class="top-bar">
      <div class="topbar">
        <div class="topbar-marquee">
          <div class="marquee-content">
            <AlertTriangle :size="15" />
            <span>Website này là bản demo cho dự án cá nhân, được phát triển để nghiên cứu, thực hành và trình bày kỹ năng. Tất cả dữ liệu hiển thị chỉ mang tính tham khảo và không liên quan đến bất kỳ hoạt động kinh doanh hay thương mại nào. </span>
          </div>
        </div>


        <div class="topbar-item">
          <hr style="height: 13px;">
          <MapPin :size="15" />
          Cửa hàng gần bạn
          <hr style="height: 13px;">
          <Receipt :size="15" />
          Tra cứu đơn hàng
          <hr style="height: 13px;">
          <PhoneCall :size="15" />
          1800 2097
        </div>

      </div>
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
import { MapPin, Receipt, PhoneCall, AlertTriangle } from "lucide-vue-next";
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
  padding: 0 0 0 15px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  /* Tạo khoảng cách giữa các phần tử */
  box-sizing: border-box;
}

/* --- Top Bar --- */
.top-bar {
  background-color: var(--primary-color);
  padding: 10px 0;
}

.logo img {
  height: 50px;
  /* Chỉnh chiều cao logo */
  display: block;
  /* Bỏ khoảng trắng thừa dưới ảnh */
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
  white-space: nowrap;
  /* Ngăn chữ xuống dòng */
}

.header-button:hover {
  background-color: rgba(255, 255, 255, 0.3);
}

.category-button,
.location-selector,
.user-button,
.cart-button {
  flex-shrink: 0;
  /* Ngăn các nút bị co lại */
}

.cart-button,
.user-button {
  background: none;
}

/* --- Bottom Bar --- */
.bottom-bar {
  background-color: white;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.bottom-bar .container {
  justify-content: flex-start;
  /* Căn các link từ bên trái */
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

.topbar {
  width: 1200px;
  background: var(--primary-color);
  /* tông đỏ giống hình */
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  flex-wrap: wrap;
  /* ⭐ Cho xuống dòng khi thiếu chỗ */
  padding: 6px 10px;
  font-size: 14px;
  margin: 0 auto;
}

.topbar-item {
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  /* giữ nội dung mỗi item trên 1 dòng */
}

.topbar-marquee {
  width: 770px;              /* chiều rộng vùng hiển thị */
  overflow: hidden;          /* ẩn phần tràn ra */
  white-space: nowrap;
  top: 5px;
  position: relative;        /* rất quan trọng */
  height: 24px;              /* chỉnh tùy bạn */
}

.marquee-content {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  position: absolute;         
  left: 100%;                 /* bắt đầu từ ngoài mép phải */
  animation: marquee 20s linear infinite;
}

@keyframes marquee {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-120%); 
  }
}


</style>