<template>
  <div class="main-content-wrapper">
    <CategoryMenu :categories="categories"/>
    <BannerSection :banners="banners"/>
    <RightSidebar />
  </div>
</template>

<script setup>
import CategoryMenu from './CategoryMenu.vue';
import BannerSection from './BannerSection.vue';
import RightSidebar from './RightSidebar.vue';
import { ref, onMounted } from 'vue'
import axios from 'axios'

const banners = ref([])
const categories = ref([])

onMounted(async () => {
  const { data } = await axios.get('http://127.0.0.1:8000/api/home-main')
  console.log(data);
  categories.value = data.categories ?? []
  banners.value = (data.banners ?? []).map((item, index) => {
  const product = item.product || null

  return {
    id: item.id ?? index,
    image: item.image,
    alt: item.title,

    title: product?.name || '',
    subtitle: item.title,

    shortTitle: product?.name || '',
    shortSubtitle: item.title,

    href: product ? `${product.slug}` : '#'
  }
})
})
</script>

<style scoped>
.main-content-wrapper {
  max-width: 1200px;
  display: grid;
  /* Định nghĩa layout 3 cột với kích thước và khoảng cách */
  grid-template-columns: 225px 700px 230px;
  gap: 10px;
  box-sizing: border-box;
}
</style>