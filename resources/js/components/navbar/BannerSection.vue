<template>
  <!-- Giữ nguyên hoàn toàn template như cũ -->
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
        <div class="title-content">
          <strong>{{ slide.shortTitle }}</strong>
          <span>{{ slide.shortSubtitle }}</span>
        </div>
        <div class="progress-bar" v-if="index === currentIndex"></div>
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
          <a :href="slide.href" class="slide-link">
            <img :src="slide.image" :alt="slide.alt">
            <div class="slide-overlay">
              <div class="overlay-content">
                <h3>{{ slide.title }}</h3>
                <p>{{ slide.subtitle }}</p>
                <span class="cta-button">Xem ngay <i class="arrow-icon">→</i></span>
              </div>
            </div>
          </a>
        </div>
      </div>
      
      <!-- Navigation Arrows -->
      <button class="nav-arrow nav-prev" @click="prevSlide" aria-label="Previous slide">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <button class="nav-arrow nav-next" @click="nextSlide" aria-label="Next slide">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      
      <!-- Dots Indicator -->
      <div class="dots-container">
        <span 
          v-for="(slide, index) in slides"
          :key="slide.id"
          class="dot"
          :class="{ active: index === currentIndex }"
          @click="goToSlide(index)"
        ></span>
      </div>
    </div>

    <!-- 3. CÁC BANNER PHỤ BÊN DƯỚI -->
    <div class="sub-banners">
      <a href="#" class="sub-banner-item">
        <img :src="SubBanner1" alt="Sub Banner 1">
        <div class="banner-hover-effect"></div>
      </a>
      <a href="#" class="sub-banner-item">
        <img :src="SubBanner2" alt="Sub Banner 2">
        <div class="banner-hover-effect"></div>
      </a>
      <a href="#" class="sub-banner-item">
        <img :src="SubBanner3" alt="Sub Banner 3">
        <div class="banner-hover-effect"></div>
      </a>
    </div>
  </div>
</template>

<script setup>
// Giữ nguyên hoàn toàn phần script
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Import ảnh
import imgIphone from '../../../images/690x300_iPhone_17_Pro_Opensale_v3.webp';
import imgGalaxy from '../../../images/s25-home-1025.webp';
import imgXiaomi from '../../../images/xiaomi-15t-5g-home-0925.webp';
import imgAirpods from '../../../images/home-app3-opensale.webp';
import imgHonor from '../../../images/honor-magic-v5-home.webp';
import SubBanner1 from '../../../images/Right-S25-FE.webp'
import SubBanner2 from '../../../images/AW11-right-banner.webp'
import SubBanner3 from '../../../images/Camp-laptop-T9_Right-banner-1.webp'

// DỮ LIỆU CHO SLIDESHOW - Thêm shortTitle và shortSubtitle
const slides = ref([
  { 
    id: 1, 
    title: 'IPHONE 17 SERIES', 
    subtitle: 'Mua ngay - Ưu đãi cực khủng',
    shortTitle: 'IPHONE 17',
    shortSubtitle: 'Mua ngay',
    image: imgIphone, 
    alt: 'iPhone 17 Banner', 
    href: '#'
  },
  { 
    id: 2, 
    title: 'GALAXY S25 ULTRA', 
    subtitle: 'Giá tốt chốt ngay',
    shortTitle: 'GALAXY S25',
    shortSubtitle: 'Giá tốt nhất',
    image: imgGalaxy, 
    alt: 'Galaxy S25 Banner', 
    href: '#' 
  },
  { 
    id: 3, 
    title: 'XIAOMI 15T SERIES', 
    subtitle: 'Ưu đãi đến 5 triệu++',
    shortTitle: 'XIAOMI 15T',
    shortSubtitle: 'Giảm 5 triệu',
    image: imgXiaomi, 
    alt: 'Xiaomi 15T Banner', 
    href: '#' 
  },
  { 
    id: 4, 
    title: 'AIRPODS PRO 3', 
    subtitle: 'Nhanh tay sở hữu',
    shortTitle: 'AIRPODS 3',
    shortSubtitle: 'Sở hữu ngay',
    image: imgAirpods, 
    alt: 'Airpods Pro 3 Banner', 
    href: '#' 
  },
  { 
    id: 5, 
    title: 'HONOR MAGIC', 
    subtitle: 'Ưu đãi quà 12 triệu',
    shortTitle: 'HONOR',
    shortSubtitle: 'Quà 12 triệu',
    image: imgHonor, 
    alt: 'Honor Magic Banner', 
    href: '#' 
  },
]);

// BIẾN TRẠNG THÁI
const currentIndex = ref(0);
let sliderInterval = null;

// LOGIC CHUYỂN SLIDE
const goToSlide = (index) => {
  currentIndex.value = index;
  stopAutoSlide();
  startAutoSlide();
};

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % slides.value.length;
};

const prevSlide = () => {
  currentIndex.value = currentIndex.value === 0 
    ? slides.value.length - 1 
    : currentIndex.value - 1;
};

// LOGIC TỰ ĐỘNG CHUYỂN SLIDE
const startAutoSlide = () => {
  sliderInterval = setInterval(nextSlide, 4000);
};

const stopAutoSlide = () => {
  clearInterval(sliderInterval);
};

// TÍNH TOÁN STYLE
const sliderStyle = computed(() => {
  return {
    transform: `translateX(-${currentIndex.value * 100}%)`
  };
});

// VÒNG ĐỜI COMPONENT
onMounted(() => {
  startAutoSlide();
});

onUnmounted(() => {
  stopAutoSlide();
});
</script>

<style scoped>
/* Giữ nguyên tất cả styles cũ, chỉ điều chỉnh height và spacing */
.banner-section {
  display: flex;
  flex-direction: column;
  gap: 10px; /* Giảm gap để compact hơn */
  height: 100%; /* Để tự điều chỉnh với CategoryMenu */
}

/* --- Style cho Title Banners --- */
.banner-titles {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  background: white;
  border-radius: 12px;
  padding: 10px; /* Giảm padding */
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.title-item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px; /* Giảm height */
  padding: 8px 10px; /* Giảm padding */
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.title-item:hover {
  background: white;
  border-color: #d41e1e;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(212, 30, 30, 0.1);
}

.title-item.active {
  background: white;
  border-color: #d41e1e;
  box-shadow: 0 3px 15px rgba(212, 30, 30, 0.15);
}

.title-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  width: 100%;
  z-index: 1;
}

.title-item strong {
  font-size: 12px; /* Giảm size */
  color: #2c3e50;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  line-height: 1.2;
  margin-bottom: 3px;
  transition: color 0.3s;
  display: block;
}

.title-item span {
  font-size: 10px; /* Giảm size */
  color: #6c757d;
  font-weight: 500;
  line-height: 1.2;
  transition: color 0.3s;
  display: block;
}

.title-item:hover strong,
.title-item.active strong {
  color: #d41e1e;
}

.title-item.active span {
  color: #495057;
}

/* Progress bar animation */
.progress-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: #d41e1e;
  animation: progressAnimation 4s linear;
  transform-origin: left;
}

@keyframes progressAnimation {
  from {
    transform: scaleX(0);
  }
  to {
    transform: scaleX(1);
  }
}

/* --- Style cho Slideshow --- */
.slider-container {
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  background: #f5f5f5;
  aspect-ratio: 16 / 9; /* Giữ tỷ lệ khung đúng với ảnh */
  width: 100%;
  height: 308px;
}

.slides-inner {
  display: flex;
  transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  height: 100%;
}

.slide {
  flex-shrink: 0;
  width: 100%;
  position: relative;
  height: 100%;
}

.slide-link {
  display: block;
  position: relative;
  height: 100%;
}

.slide img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

/* Slide Overlay */
.slide-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
  padding: 30px 25px 20px; /* Giảm padding */
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slider-container:hover .slide-overlay {
  opacity: 1;
  transform: translateY(0);
}

.overlay-content h3 {
  color: white;
  font-size: 24px; /* Giảm size */
  font-weight: 700;
  margin-bottom: 8px;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.overlay-content p {
  color: rgba(255, 255, 255, 0.95);
  font-size: 14px; /* Giảm size */
  margin-bottom: 16px;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.cta-button {
  display: inline-flex;
  align-items: center;
  color: white;
  background: linear-gradient(135deg, #d41e1e 0%, #ff6b6b 100%);
  padding: 10px 20px; /* Giảm padding */
  border-radius: 25px;
  font-weight: 600;
  font-size: 13px; /* Giảm size */
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(212, 30, 30, 0.3);
}

.cta-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(212, 30, 30, 0.4);
}

.arrow-icon {
  margin-left: 8px;
  font-style: normal;
  transition: transform 0.3s;
}

.cta-button:hover .arrow-icon {
  transform: translateX(4px);
}

/* Navigation Arrows */
.nav-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.95);
  border: none;
  width: 36px; /* Giảm size */
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  opacity: 0;
  z-index: 10;
  box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}

.slider-container:hover .nav-arrow {
  opacity: 1;
}

.nav-arrow:hover {
  background: white;
  transform: translateY(-50%) scale(1.1);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-prev {
  left: 12px;
}

.nav-next {
  right: 12px;
}

/* Dots Indicator */
.dots-container {
  position: absolute;
  bottom: 12px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 5px;
  z-index: 10;
  padding: 5px 10px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 20px;
  backdrop-filter: blur(10px);
}

.dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: all 0.3s;
}

.dot:hover {
  background: rgba(255, 255, 255, 0.8);
}

.dot.active {
  width: 18px;
  border-radius: 4px;
  background: white;
}

/* --- Style cho Sub-banners --- */
.sub-banners {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  height: 100px; /* Set chiều cao cố định cho sub-banners */
}

.sub-banner-item {
  display: block;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  background: white;
  height: 100%;
}

.sub-banner-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.sub-banner-item img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.sub-banner-item:hover img {
  transform: scale(1.08);
}

.banner-hover-effect {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(212, 30, 30, 0.15) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.3s;
  pointer-events: none;
}

.sub-banner-item:hover .banner-hover-effect {
  opacity: 1;
}

/* Responsive Design - Giữ nguyên */
@media (max-width: 992px) {
  .banner-titles {
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    padding: 8px;
  }
  
  .title-item:nth-child(n+4) {
    display: none;
  }
  
  .title-item {
    min-height: 44px;
    padding: 6px 8px;
  }
  
  .slider-container {
    height: 200px;
  }
  
  .sub-banners {
    height: 80px;
  }
}

@media (max-width: 768px) {
  .banner-section {
    gap: 10px;
  }
  
  .banner-titles {
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
    padding: 6px;
  }
  
  .title-item:nth-child(n+5) {
    display: none;
  }
  
  .title-item {
    min-height: 40px;
  }
  
  .slider-container {
    height: 180px;
  }
  
  .sub-banners {
    grid-template-columns: 1fr;
    gap: 10px;
    height: auto;
  }
  
  .sub-banner-item {
    height: 80px;
  }
  
  .overlay-content {
    padding: 15px;
  }
  
  .overlay-content h3 {
    font-size: 18px;
  }
  
  .overlay-content p {
    font-size: 12px;
  }
  
  .nav-arrow {
    width: 32px;
    height: 32px;
  }
  
  .nav-arrow svg {
    width: 16px;
    height: 16px;
  }
}

@media (max-width: 480px) {
  .banner-titles {
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    padding: 5px;
  }
  
  .title-item:nth-child(n+4) {
    display: none;
  }
  
  .title-item {
    min-height: 36px;
    padding: 5px;
  }
  
  .title-item strong {
    font-size: 10px;
    letter-spacing: 0;
  }
  
  .title-item span {
    font-size: 9px;
  }
  
  .slider-container {
    height: 150px;
  }
  
  .slider-container:hover .slide-overlay {
    opacity: 0;
  }
  
  .dots-container {
    bottom: 8px;
    padding: 3px 6px;
  }
  
  .dot {
    width: 5px;
    height: 5px;
  }
  
  .dot.active {
    width: 14px;
  }
}
</style>