<script setup>
import { ChevronRight } from "lucide-vue-next";
</script>

<template>
  <nav class="container breadcrumb" style="padding-left: 20px; padding-top: 15px; margin-bottom: 15px;">
    <a href="#">Trang chủ</a>
    <ChevronRight class="divider" />

    <a href="#">Điện thoại</a>
    <ChevronRight class="divider" />

    <a href="#">Apple</a>
    <ChevronRight class="divider" />

    <span class="current">iPhone 16 Pro Max 256GB</span>
  </nav>
</template>

<style scoped>
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: #555;
}

.breadcrumb a {
  text-decoration: none;
  color: #007bff;
}

.breadcrumb a:hover {
  text-decoration: underline;
}

.current {
  color: #333;
  font-weight: 600;
}

.divider {
  width: 14px;
  height: 14px;
  color: #888;
}
</style>
