<template>
  <div class="main-content-wrapper">
    <CategoryMenu />
    <BannerSection />
    <RightSidebar />
  </div>
</template>

<script setup>
import CategoryMenu from './CategoryMenu.vue';
import BannerSection from './BannerSection.vue';
import RightSidebar from './RightSidebar.vue';
</script>

<style scoped>
.main-content-wrapper {
  max-width: 1200px;
  display: grid;
  /* Định nghĩa layout 3 cột với kích thước và khoảng cách */
  grid-template-columns: 240px 1fr 280px;
  gap: 10px;
  box-sizing: border-box;
}
</style>