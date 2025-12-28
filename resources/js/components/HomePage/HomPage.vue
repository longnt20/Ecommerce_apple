<template>
  <div class="main-product-wrapper">
    <HotSaleSlider />
    <ListProductPhone/>
    <AccessoryGrid />
  </div>
</template>

<script setup>
import AccessoryGrid from './AccessoryGrid.vue';
import HotSaleSlider from './HotSaleSlider.vue';
import ListProductPhone from './ListProductPhone.vue';
</script>

<style scoped>
.main-product-wrapper {
  max-width: 1200px;
  margin: 10px auto; /* Tạo khoảng cách trên dưới và căn giữa */
  padding: 0 10px;
}
/* .main-product-wrapper,
.main-product-wrapper * {
  box-sizing: content-box !important;
} */
</style>