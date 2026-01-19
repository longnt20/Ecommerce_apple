<template>
  <div class="main-product-wrapper">
    <HotSaleSlider 
    :promotion="promotion" 
    :promotion_categories="promotion_categories"
    :loading="!promotion"/>
    <ListProductPhone :products="products"/>
    <AccessoryGrid />
    <ListProductWatch :products="products"/>
    <PromotionSection />
    <BlogFeature :blogs="blogs" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AccessoryGrid from './AccessoryGrid.vue';
import BlogFeature from './BlogFeature.vue';
import HotSaleSlider from './HotSaleSlider.vue';
import ListProductPhone from './ListProductPhone.vue';
import ListProductWatch from './ListProductWatch.vue';
import PromotionSection from './PromotionSection.vue';
const products = ref([]);
const promotion = ref([]);
const promotion_categories = ref([]);
const blogs = ref([]);
onMounted(async () => {
  const { data } = await axios.get('http://127.0.0.1:8000/api/home')
  promotion.value = data.data.promotion || null
  promotion_categories.value = data.data.promotion_categories || []
  products.value = data.data.products || null
  blogs.value = data.data.blogs || null
})

</script>


<style scoped>
.main-product-wrapper {
  max-width: 1200px;
  margin: 10px auto; /* Tạo khoảng cách trên dưới và căn giữa */
}
/* .main-product-wrapper,
.main-product-wrapper * {
  box-sizing: content-box !important;
} */
</style>