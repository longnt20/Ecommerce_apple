<template>
  <div class="product-page">
    
    <ProductBreadcrumb :product="store.product"/>
    <ProductInfor :product="store.product"/>
    <div class="container main-layout">
      <!-- LEFT COLUMN -->
      <div class="left-column">
        
        <!-- Gallery -->
        <ProductGallery :product="store.product" :variantImage="variantImage"/>

        <!-- Policies -->
        <PoliciesProduct :product="store.product"/>

        <!-- Specs -->
        <TechnicalSpecs :product="store.product"/>

      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-column">
        <ProductAttribute 
        :product="store.product" 
        :selectedVariant="store.product?.selected_variant" 
        @variant-selected="onVariantSelected"/>
        
        <!-- Buttons Mua hàng -->
         <BuyButton :product="store.product" :selectedVariant="store.product?.selected_variant" />

         <!-- Cửa hàng chi nhánh -->
        <StoreBranch :product="store.product"/>

        <!-- Promotion -->
        <PromotionBox :product="store.product"/>

        <!-- Extra Offers -->
        <PaymentOffers :product="store.product"/>

      </div>
    </div>
    <div class="container">
       <DescriptionAndBlog :product="store.product"/>
       <ProductReview />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ProductBreadcrumb from '@/components/Product/ProductBreadcrumb.vue';
import ProductInfor from '@/components/Product/ProductInfor.vue';
import ProductGallery from '@/components/Product/ProductGallery.vue';
import ProductAttribute from '@/components/Product/ProductAttribute.vue';
import PoliciesProduct from '@/components/Product/PoliciesProduct.vue';
import StoreBranch from '@/components/Product/StoreBranch.vue';
import PromotionBox from '@/components/Product/PromotionBox.vue';
import BuyButton from '@/components/Product/BuyButton.vue';
import PaymentOffers from '@/components/Product/PaymentOffers.vue';
import TechnicalSpecs from '@/components/Product/TechnicalSpecs.vue';
import DescriptionAndBlog from '@/components/Product/DescriptionAndBlog.vue';
import { onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { useProductStore } from '@/stores/productStore';
import { log } from 'three';
import ProductReview from '@/components/Product/ProductReview.vue';


const route = useRoute();
const store = useProductStore();
const variantImage = ref(null);
const onVariantSelected = (variant) => {
    // if (!variant) return;       // tránh undefined
    console.log('variant received:', variant);
    store.product.selected_variant = variant;
    variantImage.value = variant.thumbnail;
};

// mỗi khi slug thay đổi → gọi lại API
watch(
  () => route.params.slug,
  (slug) => {
    if (slug) store.fetchProductBySlug(slug);
  },
  { immediate: true }
);
</script>

<style scoped>
/* --- VARIABLES --- */
:root {
  --primary-red: #d70018;
  --primary-blue: #00483d;
  --bg-gray: #f9fafb;
  --border-color: #e5e7eb;
  --text-main: #333;
  --text-sub: #707070;
}

/* --- GLOBAL RESET & LAYOUT --- */
.product-page {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: #333;
  padding-bottom: 50px;
  font-size: 14px;
  width: 1200px;
  margin: 0 auto;
  background-color: #ffffff;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

a { text-decoration: none; color: inherit; }
ul { list-style: none; padding: 0; margin: 0; }



/* --- MAIN GRID LAYOUT --- */
.main-layout {
  display: grid;
  grid-template-columns: 50% 50%;
  gap: 20px;
}
.main-layout > * {
  min-width: 0;      /* bắt buộc để grid không tràn */
}
/* Responsive Mobile */
@media (max-width: 768px) {
  .main-layout { grid-template-columns: 1fr; }
}

/* --- LEFT COLUMN STYLES --- */
.gallery-box {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}

.main-image {
  position: relative;
  height: 400px;
  background: linear-gradient(135deg, #ffd1dc 0%, #fff3e0 100%);
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 15px;
  overflow: hidden;
}

.main-image img {
  max-height: 90%;
  mix-blend-mode: multiply;
}

.overlay-text {
  position: absolute;
  top: 20px;
  left: 20px; /* Đổi sang trái cho dễ nhìn */
  z-index: 2;
  color: #4a4a4a;
}
.overlay-text h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: bold; color: #d70018; }
.overlay-text ul { font-size: 12px; list-style-type: disc; padding-left: 15px; }

.thumbnails {
  display: flex;
  gap: 10px;
  overflow-x: auto;
}
.thumb-item {
  width: 60px;
  height: 60px;
  border: 1px solid #ddd;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 10px;
  color: #999;
}
.thumb-item:hover { border-color: #d70018; }

</style>