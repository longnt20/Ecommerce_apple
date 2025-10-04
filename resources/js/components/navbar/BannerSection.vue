<!-- src/components/home/BannerSection.vue -->
<template>
  <div class="banner-section">
    <!-- 1. CÁC TITLE BANNER (BỘ ĐIỀU KHIỂN) -->
    <div class="banner-titles">
      <div
        v-for="(slide, index) in slides"
        :key="slide.id"
        class="title-item"
        :class="{ active: index === currentIndex }"
        @click="goToSlide(index)"
      >
        <strong>{{ slide.title }}</strong>
        <span>{{ slide.subtitle }}</span>
      </div>
    </div>

    <!-- 2. SLIDESHOW CHÍNH -->
    <div 
      class="slider-container"
      @mouseover="stopAutoSlide"
      @mouseleave="startAutoSlide"
    >
      <div class="slides-inner" :style="sliderStyle">
        <div v-for="slide in slides" :key="slide.id" class="slide">
          <a :href="slide.href">
            <img :src="slide.image" :alt="slide.alt">
          </a>
        </div>
      </div>
    </div>

    <!-- 3. CÁC BANNER PHỤ BÊN DƯỚI (giữ nguyên) -->
    <div class="sub-banners">
      <a href="#"><img :src="SubBanner1" alt="Sub Banner 1"></a>
      <a href="#"><img :src="SubBanner2" alt="Sub Banner 2"></a>
      <a href="#"><img :src="SubBanner3" alt="Sub Banner 3"></a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Import ảnh của bạn ở đây
// Giả sử bạn đã đặt ảnh trong src/assets/images/banners
import imgIphone from '../../../images/690x300_iPhone_17_Pro_Opensale_v3.webp';
import imgGalaxy from '../../../images/s25-home-1025.webp';
import imgXiaomi from '../../../images/xiaomi-15t-5g-home-0925.webp';
import imgAirpods from '../../../images/home-app3-opensale.webp';
import imgHonor from '../../../images/honor-magic-v5-home.webp';
import SubBanner1 from '../../../images/Right-S25-FE.webp'
import SubBanner2 from '../../../images/AW11-right-banner.webp'
import SubBanner3 from '../../../images/Camp-laptop-T9_Right-banner-1.webp'


// 4. DỮ LIỆU CHO SLIDESHOW
const slides = ref([
  { id: 1, title: 'IPHONE 17 SERIES', subtitle: 'Mua ngay', image: imgIphone, alt: 'iPhone 17 Banner', href: '#', active: true },
  { id: 2, title: 'GALAXY S25 ULTRA', subtitle: 'Giá tốt chốt ngay', image: imgGalaxy, alt: 'Galaxy S25 Banner', href: '#' },
  { id: 3, title: 'XIAOMI 15T SERIES', subtitle: 'Ưu đãi đến 5 triệu++', image: imgXiaomi, alt: 'Xiaomi 15T Banner', href: '#' },
  { id: 4, title: 'AIRPODS PRO 3', subtitle: 'Nhanh tay sở hữu', image: imgAirpods, alt: 'Airpods Pro 3 Banner', href: '#' },
  { id: 5, title: 'HONOR MAGIC', subtitle: 'Ưu đãi quà 12 triệu', image: imgHonor, alt: 'Honor Magic Banner', href: '#' },
]);

// 5. BIẾN TRẠNG THÁI (STATE)
const currentIndex = ref(slides.value.findIndex(s => s.active) || 0); // Vị trí slide hiện tại, bắt đầu từ slide active
let sliderInterval = null; // Biến để lưu trữ interval

// 6. LOGIC CHUYỂN SLIDE
const goToSlide = (index) => {
  currentIndex.value = index;
  // Reset interval khi người dùng tự chuyển slide
  stopAutoSlide();
  startAutoSlide();
};

const nextSlide = () => {
  // Dùng toán tử modulo (%) để quay vòng lại slide đầu tiên
  currentIndex.value = (currentIndex.value + 1) % slides.value.length;
};

// 7. LOGIC TỰ ĐỘNG CHUYỂN SLIDE
const startAutoSlide = () => {
  // Chạy hàm nextSlide mỗi 3 giây (3000ms)
  sliderInterval = setInterval(nextSlide, 3000);
};

const stopAutoSlide = () => {
  clearInterval(sliderInterval);
};

// 8. TÍNH TOÁN STYLE CHO HIỆU ỨNG TRƯỢT (computed property)
const sliderStyle = computed(() => {
  return {
    transform: `translateX(-${currentIndex.value * 100}%)`
  };
});

// 9. VÒNG ĐỜI COMPONENT (Lifecycle Hooks)
onMounted(() => {
  startAutoSlide(); // Bắt đầu tự chạy khi component được render
});

onUnmounted(() => {
  stopAutoSlide(); // Dừng interval khi component bị hủy để tránh rò rỉ bộ nhớ
});

</script>

<style scoped>
.banner-section {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

/* --- Style cho Title Banners --- */
.banner-titles {
  display: flex;
  justify-content: space-between;
  background-color: white;
  border-radius: 12px;
  padding: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.title-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 5px 10px;
  border-radius: 8px;
  cursor: pointer;
  flex-grow: 1;
  transition: background-color 0.3s;
}
.title-item strong {
  font-size: 13px; /* Có thể giữ nguyên */
  color: #333;
  font-weight: 600; /* <<-- Tăng độ đậm lên Semi-bold */
  text-transform: uppercase; /* <<-- VIẾT HOA HẾT */
}
.title-item span {
  font-size: 11px;
  color: #777;
  font-weight: 500; /* <<-- Dùng độ đậm Medium */
}
.title-item.active strong {
  color: var(--primary-color); /* Sử dụng biến màu đã định nghĩa */
}

/* --- Style cho Slideshow --- */
.slider-container {
  border-radius: 12px;
  overflow: hidden; /* Đây là key để tạo hiệu ứng trượt */
  position: relative;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.slides-inner {
  display: flex;
  /* Hiệu ứng trượt mượt mà */
  transition: transform 0.5s ease-in-out; 
}
.slide {
  flex-shrink: 0; /* Ngăn các slide bị co lại */
  width: 100%;   /* Mỗi slide chiếm toàn bộ chiều rộng container */
}
.slide img {
  width: 100%;
  display: block;
}

/* --- Style cho Sub-banners (giữ nguyên) --- */
.sub-banners {
  display: grid;
  grid-template-columns: repeat(3, 1fr); 
  gap: 15px;
}
.sub-banners a {
  display: block;
  border-radius: 12px;
  overflow: hidden;
}
.sub-banners img {
  width: 100%;
  display: block;
}
</style>