<template>
  <div class="gallery-wrapper">
    <div class="main-swiper-container" @mouseenter="showNav = true" @mouseleave="showNav = false">
      <swiper :modules="[Thumbs, Navigation]" :thumbs="{ swiper: thumbsSwiper }" :navigation="showNav"
              class="main-swiper" @swiper="onMainSwiper">
        <swiper-slide v-for="(img, i) in images" :key="i">
          <img :src="img" class="main-img" />
        </swiper-slide>
      </swiper>
    </div>

    <!-- THUMBNAILS -->
    <div class="thumb-container">
      <swiper :modules="[Thumbs, Navigation]" @swiper="setThumbsSwiper" slides-per-view="6" space-between="10"
              navigation class="thumb-swiper">
        <swiper-slide v-for="(img, i) in images" :key="i" :class="{ active: i === activeIndex }">
          <img :src="img" class="thumb-img" @click="setActive(i)" />
        </swiper-slide>
      </swiper>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Thumbs } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';

const props = defineProps({
  product: Object,
  variantImage: String
});

const thumbsSwiper = ref(null);
const mainSwiper = ref(null);
const activeIndex = ref(0);
const showNav = ref(false);

function setThumbsSwiper(swiper) { thumbsSwiper.value = swiper; }
function onMainSwiper(swiper) { mainSwiper.value = swiper; }
function setActive(i) { 
  activeIndex.value = i; 
  if (mainSwiper.value) mainSwiper.value.slideTo(i); 
}

// Gộp ảnh chính + gallery
const images = computed(() => {
  const gallery = Array.isArray(props.product?.gallery) ? props.product.gallery : [];

  if (props.variantImage) return [props.variantImage, ...gallery];

  const main = props.product?.thumbnail ? [props.product.thumbnail] : [];
  return [...main, ...gallery];
});

// Watch biến thể mới → đổi slide
watch(() => props.variantImage, (newImg) => {
  if (newImg && mainSwiper.value) mainSwiper.value.slideTo(0);
});
</script>


<style scoped>
.gallery-wrapper {

    margin: auto;
}

/* ẢNH LỚN */
.main-swiper {

    border: 1px solid rgb(178, 167, 167);
    border-radius: 12px;
    display: flex;
    /* Căn giữa slide */
    justify-content: center;
    align-items: center;
}

.main-swiper img {
    border-radius: 12px;
    display: block;
    margin: 0 auto;
    /* Căn giữa ảnh */
    max-width: 100%;
    height: auto;
    object-fit: contain;
    /* Giữ đúng tỷ lệ hình */
}

/* THUMBNAILS */
.thumb-swiper {
    margin-top: 15px;
}

.swiper-wrapper {
    gap: 2px;
}

.thumb-swiper .swiper-slide.active img {
    border: 2px solid red;
    padding: 2px;
    border-radius: 10px;
}

.thumb-swiper .swiper-slide {
    width: auto !important;
    margin: 5px !important;
    /* Xóa luôn margin dư */
    padding: 0 !important;
}

.thumb-swiper img {
    width: 100%;
    height: 75px;
    object-fit: cover;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid rgb(210, 201, 201);
}
</style>
