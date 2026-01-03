<template>
  <div class="my-container">
    <div class="left-box">
      <!-- BANNER ĐIỆN THOẠI -->
      <div v-if="activeTabId === 19" class="banner-wrapper">
        <a href="#" class="a1">
          <span class="cps-image-cdn"> <img loading="lazy" width="214" height="530" decoding="async" data-nimg="1"
              src="https://cdn2.cellphones.com.vn/insecure/rs:fill:321:960/q:90/plain/https://media-asset.cellphones.com.vn/page_configs/01K8WKEW3CH6FD5HGP201X8WAC.png"
              alt="Banner iphone 17 pro home desktop" class="my-image" :style="{ color: transparent }"> </span>
        </a>
      </div>

      <!-- BANNER LAPTOP -->
      <div v-else-if="activeTabId === 18" class="banner-wrapper">
        <a href="#" class="a1">
          <span class="cps-image-cdn"> <img loading="lazy" width="214" height="530" decoding="async" data-nimg="1"
              src="https://cdn2.cellphones.com.vn/insecure/rs:fill:321:960/q:90/plain/https://media-asset.cellphones.com.vn/page_configs/01KA0B56WR5FQC2QKJH8SQXAFR.jpg"
              alt="Banner laptop home desktop 1" class="my-image" :style="{ color: transparent }"> </span>
        </a>
      </div>

    </div>
    <div class="right-box">
      <div class="tabs">
        <button v-for="(tab, index) in tabs" :key="tab.id"
          :class="['tab-btn', index === activeTabIndex ? 'active' : '']" @click="changeTab(tab.id, index)">
          {{ tab.name }}
        </button>
      </div>
      <div class="category-brand">

        <div class="category-wrapper">

          <div class="category-row" ref="row">
            <div v-for="item in categories" :key="item.id" class="category-card">
              <img :src="item.icon" />
              <div class="category-name">{{ item.name }}</div>
            </div>
          </div>

        </div>

        <!-- BRAND ROW -->
        <div class="brand-row">
          <div class="brand-list">
            <button v-for="brand in brands" :key="brand" class="brand-pill">
              {{ brand }}
            </button>
          </div>

          <a href="#" class="view-all">
            Xem tất cả <span>›</span>
          </a>
        </div>

      </div>
      <div class="bottom-box">
        <div class="product-watch-swiper swiper rounded-2xl">
          <div class="swiper-wrapper">
            <div v-for="product in filteredProducts" :key="product.id" class="swiper-slide">
              <div class="branch-23">
                <div class="branch-24">
                  <RouterLink class="a1" :to="{ name: 'product-detail', params: { slug: product.slug } }">
                    <span class="sp-2">
                      <img :alt="product.name" loading="lazy" width="300" height="300" decoding="async" data-nimg="1"
                        class="img-4" :src="product.thumbnail" :style="{ color: 'transparent' }" />
                    </span>
                    <h3 class="title-ss">{{ product.name }} | Chính Hãng VN/A</h3>
                    <div class="branch-26">
                      <p class="price">{{ product.final_price }}đ</p>
                      <p class="cost-price">{{ product.original_price }}đ</p>
                    </div>
                    <div class="badge-box">
                      <div class="">Trả góp 0% - 0đ phụ thu - 0đ trả trước - kỳ hạn đến 6 tháng</div>
                    </div>
                    <div class="branch-27" :style="{ backgroundImage: `url(${backgrounds.discountBadge})` }">
                      Giảm <span class="sp-3">{{ product.discount_percent }}%</span>
                    </div>
                    <div class="branch-28" :style="{ background: `url(${backgrounds.zeroInsBadge})` }">
                      <span class="sp-4">
                        Trả góp <span class="sp-5">0%</span>
                      </span>
                    </div>
                    <div class="branch-29">
                      <div class="branch-30">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 576 512"
                          class="color" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                          <path
                            d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z">
                          </path>
                        </svg>
                        <span>{{ product.rating }}</span>
                      </div>
                      <button data-slot="button" class="cpsui-button" @click="toggleFavorite(product.id)">
                        <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"
                          stroke-linecap="round" stroke-linejoin="round" class="favourite" height="1em" width="1em"
                          xmlns="http://www.w3.org/2000/svg">
                          <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                          </path>
                        </svg>
                        <span class="var">Yêu thích</span>
                      </button>
                    </div>
                  </RouterLink>
                </div>

              </div>
            </div>
          </div>

          <div class="swiper-button-prev product-prev"></div>
          <div class="swiper-button-next product-next"></div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Swiper from 'swiper'
import { Grid, Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import "swiper/css/grid";
import { RouterLink } from 'vue-router'
const products = ref([])
const swiperRef = ref(null)
const filteredProducts = ref([])
const tabs = ref([
  { id: 19, name: 'Đồng Hồ' },
  { id: 18, name: 'Âm Thanh' }
])
const categories = [
  { id: 1, name: 'Tập luyện thể thao', icon: '/logos/iphone-16-pro-max.webp' },
  { id: 2, name: 'Nghe gọi', icon: '/logos/iphone-17-pro-256-gb.webp' },
  { id: 3, name: 'Vòng đeo tay thông minh', icon: '/logos/iphone-15-plus_1__1.webp' },
  { id: 4, name: 'Định vị trẻ em', icon: '/logos/iphone_17_256gb-3_2.webp' },
  { id: 5, name: 'Đo huyết áp', icon: '/logos/iphone_air-3_2.webp' },
]

const brands = [
  'Apple Watch', 'Samsung', 'Xiaomi', 'OPPO', 'TECNO',
  'HONOR', 'Nubia', 'Sony', 'Nokia', 'Infinix'
]
const backgrounds = {
  discountBadge: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/discount-badge-ui-2025.png',
  zeroInsBadge: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/zero-ins-badge-ui-2025.png'
}
const row = ref(null)

const scrollRight = () => {
  row.value.scrollLeft += 220
}

const scrollLeft = () => {
  row.value.scrollLeft -= 220
}

const activeTabId = ref(19)
const activeTabIndex = ref(0)
onMounted(async () => {
  try {
    const res = await fetch('http://127.0.0.1:8000/api/home/')
    const data = await res.json()

    products.value = data?.data || []
    filteredProducts.value = products.value.filter(p => p.category_id === activeTabId.value)
    // Đợi DOM render xong rồi khởi tạo Swiper
    await nextTick()
    const product_swiper = new Swiper('.product-watch-swiper', {
      modules: [Navigation],
      slidesPerView: 4,
      spaceBetween: 16,
      loop: false,
      navigation: {
        nextEl: '.product-next',
        prevEl: '.product-prev'
      },
    })
  } catch (e) {
    console.error('Lỗi dữ liệu:', e)
  }
})

const changeTab = (id, index) => {
  activeTabId.value = id
  activeTabIndex.value = index

  filteredProducts.value = products.value.filter(
    p => p.category_id === id
  )
}
const slideNext = () => swiperRef.value?.swiper.slideNext()
const slidePrev = () => swiperRef.value?.swiper.slidePrev()
</script>
<style scoped>
.my-container {
  display: flex;
  /* flex-direction: column; */
  gap: 1rem;
  /* center the content */
  margin-right: 16px;
}

.left-box {
  display: none;
  /* hidden -> ẩn mặc định */
  flex: 1;
  /* flex-1 -> chiếm toàn bộ không gian khả dụng trong flex container */
  flex-direction: column;
  /* flex-col -> sắp xếp theo cột */
  justify-content: space-between;
  /* justify-between -> dàn đều theo trục chính */
  gap: 1rem;
  /* gap-3 -> khoảng cách 12px giữa các phần tử con */
}

/* empty:hidden -> nếu phần tử rỗng thì ẩn */
.left-box:empty {
  display: none;
}

/* md:flex -> hiển thị lại khi màn hình >= 768px */
@media (min-width: 768px) {
  .left-box {
    display: flex;
  }
}

/* CSS */
.a1 {
  display: block;
  /* block */
  height: fit-content;
  /* h-fit -> chiều cao vừa đủ nội dung */
  width: 100%;
  /* w-full -> chiếm 100% chiều ngang */
  padding-top: 0;
  /* py-0 -> padding trên = 0 */
  padding-bottom: 0;
  /* py-0 -> padding dưới = 0 */
  line-height: 1;
  /* leading-none -> line-height = 1 */
  text-decoration: none;
}
.a2 {
  display: block;
  /* block */
  height: fit-content;
  /* h-fit -> chiều cao vừa đủ nội dung */
  width: 100%;
  /* w-full -> chiếm 100% chiều ngang */
  padding-top: 20px;
  padding-left: 10px;
  /* py-0 -> padding trên = 0 */
  padding-bottom: 0;
  /* py-0 -> padding dưới = 0 */
  line-height: 1;
  /* leading-none -> line-height = 1 */
  text-decoration: none;
}
.cps-image-cdn {
  position: relative;
  /* relative */
  /* inline-block */
  display: block;
  width: 214px;
  height: 530px;
  background: #ffffff;
  /* nền đệm nếu ảnh không đủ tỷ lệ */
  /* w-full */
  border-radius: 1rem;
  /* rounded-2xl -> 1rem (16px) */
}

.my-image {
  width: 100%;
  height: 118%;
  /* QUAN TRỌNG */
  object-position: center;
  opacity: 1;
  /* opacity-100 */
  border-radius: 1rem;
}

.right-box {
  width: 100%;
  /* w-full */
}

@media (min-width: 768px) {
  .right-box {
    width: 80%;
    /* w-4/5 */
  }
}

.topbar-box {
  display: flex;
  /* flex */
  align-items: stretch;
  /* items-stretch */
}

.phone {
  /* --- Cơ bản --- */
  flex: 1;
  /* flex-1: chiếm đều không gian trong flex container */
  cursor: pointer;
  /* cursor-pointer: trỏ chuột dạng tay */
  min-height: 3rem;
  /* min-h-12 = 3rem (48px) */
  padding-left: 0.5rem;
  /* px-2 = 0.5rem (8px) */
  padding-right: 0.5rem;
  border: none;
  border-bottom: 1px solid red;
  /* border-b + border-primary-500 */
  color: red;
  /* text-primary-500 */
  font-size: 1rem;
  /* text-base (16px) */
  font-weight: 600;
  /* font-semibold */
  line-height: 1.5;
  /* mặc định của text-base */
  transition: all 200ms ease-in-out;
  /* transition-all duration-200 ease-in-out */

  /* --- Nền gradient --- */
  background-image: linear-gradient(to top,
      rgba(240, 10, 10, 0.11) 5%,
      /* from-primary-500/11 from-5% */
      rgba(255, 255, 255, 0.05) 60%
      /* to-pure-white/5 to-60% */
    );

  /* Bo chữ cái đầu viết hoa */
  text-transform: none;
  /* mặc định */
}

.phone::first-letter {
  text-transform: uppercase;
  /* first-letter:uppercase */
}

/* --- Màn hình >= 768px (md) --- */
@media (min-width: 768px) {
  .phone {
    font-size: 1.125rem;
    /* md:text-md (giả định text-md ~ 18px) */
    min-height: 3.75rem;
    /* md:min-h-15 = 3.75rem (60px) */
    padding-left: 1rem;
    /* md:px-4 = 1rem (16px) */
    padding-right: 1rem;
    text-transform: uppercase;
    /* md:uppercase */
  }
}

.middle {
  margin-top: auto;
  /* my-auto → margin-y: auto */
  margin-bottom: auto;
  height: 1.25rem;
  /* h-5 = 1.25rem (20px) */
  border-left: 1px solid #e5e5e5;
  /* border-l + border-neutral-200 (#e5e5e5 là giá trị mặc định của Tailwind neutral-200) */
}

.ipad {
  /* --- Cơ bản --- */
  flex: 1;
  /* flex-1: chiếm đều không gian trong flex container */
  cursor: pointer;
  /* cursor-pointer: trỏ chuột dạng tay */
  min-height: 3rem;
  /* min-h-12 = 3rem (48px) */
  padding-left: 0.5rem;
  /* px-2 = 0.5rem (8px) */
  padding-right: 0.5rem;
  border: none;
  color: black;
  /* text-primary-500 */
  font-size: 1rem;
  /* text-base (16px) */
  font-weight: 600;
  /* font-semibold */
  line-height: 1.5;
  /* mặc định của text-base */
  transition: all 200ms ease-in-out;
  /* transition-all duration-200 ease-in-out */

  /* --- Nền gradient --- */
  background-image: linear-gradient(to top,
      rgba(240, 10, 10, 0.11) 5%,
      /* from-primary-500/11 from-5% */
      rgba(255, 255, 255, 0.05) 60%
      /* to-pure-white/5 to-60% */
    );

  /* Bo chữ cái đầu viết hoa */
  text-transform: none;
  /* mặc định */
}

.ipad::first-letter {
  text-transform: uppercase;
  /* first-letter:uppercase */
}

/* --- Màn hình >= 768px (md) --- */
@media (min-width: 768px) {
  .ipad {
    font-size: 1.125rem;
    /* md:text-md (giả định text-md ~ 18px) */
    min-height: 3.75rem;
    /* md:min-h-15 = 3.75rem (60px) */
    padding-left: 1rem;
    /* md:px-4 = 1rem (16px) */
    padding-right: 1rem;
    text-transform: uppercase;
    /* md:uppercase */
  }
}

.bottom-box {
  margin-top: 0.5rem;
  /* mt-2 = 0.5rem (8px) */
}

@media (min-width: 768px) {
  .bottom-box {
    margin-top: 1rem;
    /* md:mt-4 = 1rem (16px) khi màn hình ≥768px */
  }
}

.product-watch-swiper {
  margin-left: auto;
  margin-right: auto;
  position: relative;
  overflow: hidden;
  list-style: none;
  height: auto !important;
  padding: 0;
  z-index: 1;
  display: block;
}

.swiper-horizontal {
  touch-action: pan-y;
}

.swiper-wrapper {
  position: relative;
  width: 100%;
  height: auto !important;
  z-index: 1;
  display: flex;
  transition-property: transform;
  transition-timing-function: var(--swiper-wrapper-transition-timing-function, initial);
  box-sizing: content-box;
}

.swiper-slide {
  flex-shrink: 0;
  width: 100%;
  height: auto !important;
  position: relative;
  transition-property: transform;
  display: block;
}

.\!h-auto {
  height: auto !important;
}

.branch-23 {
  height: 100%;
  padding-top: 0.25rem;
  /* 4px */
  padding-right: 0.3125rem;
  /* 5px */
}

.branch-24 {
  position: relative;
  display: flex;
  flex-direction: column;
  min-height: 13rem;
  /* ~204px */
  border-radius: 1rem;
  /* 16px */
  border: 1px solid #e5e5e5;
  background-color: #fff;
  animation: fade-in 0.5s ease-in-out forwards;
  justify-content: center;
  /* căn giữa ngang */
  align-items: center;
  /* căn giữa dọc */
  /* overflow: hidden; */
}

/* Cho ảnh có animation mượt */
.branch-24 img {
  transition: transform 0.5s ease;
}

/* Khi hover toàn bộ card, ảnh phóng to */
.branch-24:hover img {
  transform: scale(1);
}

/* Keyframes cho hiệu ứng fade-in */
@keyframes fade-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

.a1 {
  flex: 1 1 0%;
  padding: 0.625rem;
  padding-bottom: 0;
}

.sp-2 {
  position: relative;
  display: inline-block;
  aspect-ratio: 1 / 1;
  height: auto;
  width: 100%;
  transform: scale(0.9);
  object-fit: contain;
  transition: transform 0.3s;
}

/* Khi hover parent group */
.group:hover .sp-2 {
  transform: scale(0.95);
}

.img-4 {
  opacity: 1;
  object-fit: contain;
  transform: scale(0.85);
  /* thu nhỏ ảnh */
  transform-origin: center;
  aspect-ratio: 1 / 1;
  height: auto;
  width: 100%;
  transition: 0.3s ease;
}

.title-ss {
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 2.25rem;
  width: 200px;
  font-size: 0.75rem;
  line-height: 1.3rem;
  font-weight: 600;
  color: #111827;
  text-decoration: none;
}

/* Responsive trên màn hình ≥ 640px */
@media (min-width: 640px) {
  .title-ss {
    height: 3rem;
    font-size: 1rem;
    white-space: normal;
    word-break: break-word;
  }
}

.branch-25 {
  margin-bottom: 0.25rem;
  width: fit-content;
  border-radius: 0.125rem;
  /* 2px */
  padding: 0.125rem 0.25rem;
  /* py-0.5 px-1 */
  font-size: 10px;
  background-color: #FFE5E5;
  color: #990000;
}

/* Ẩn nếu rỗng */
.branch-25:empty {
  display: none;
}

/* Responsive màn ≥ 768px */
@media (min-width: 768px) {
  .branch-25 {
    padding: 0.25rem 0.5rem;
    /* py-1 px-2 */
    font-size: 0.75rem;
    /* text-xs */
  }
}

.branch-26 {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  /* 6px */
}

.branch-26 .price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #e91030;
  /* primary-600 */
  text-decoration: none;
}

/* Responsive màn ≥ 640px */
@media (min-width: 640px) {
  .price {
    font-size: 1rem;
    /* text-medium */
  }
}

.branch-26 .cost-price {
  font-size: 0.875rem;
  color: #D1D5DB;
  text-decoration: line-through;
}

/* Responsive màn ≥ 640px */
@media (min-width: 640px) {
  .cost_price {
    font-size: 0.75rem;
  }
}

.branch-27 {
  position: absolute;
  top: -0.375rem;
  /* -6px */
  left: 0.625rem;
  /* 10px */
  display: flex;
  align-items: center;
  justify-content: center;
  height: 1.375rem;
  /* 22px */
  width: 4.875rem;
  /* 78px */
  font-size: 10px;
  font-weight: 500;
  color: #ffffff;
}

.sp-3 {
  margin-left: 0.125rem;
  /* 2px */
  font-size: 0.75rem;
  /* 12px */
  font-weight: 500;
}

.branch-28 {
  position: absolute;
  top: 0;
  right: -0.3125rem;
  /* -5px */
  display: flex;
  align-items: center;
  justify-content: center;
  height: 1.8125rem;
  /* ~29px */
  width: 4.6875rem;
  /* ~75px */
  overflow: hidden;
  font-size: 10px;
  font-weight: 500;
  color: #3B82F6;
  /* blue-500 */
}

.sp-4 {
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 10px;
  font-weight: 400;
}

/* Responsive màn ≥ 768px */
@media (min-width: 768px) {
  .selector {
    margin-bottom: 0.375rem;
  }
}

.sp-5 {
  font-size: 0.75rem;
  /* ~12px */
  font-weight: 500;
}

.branch-29 {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  /* pt-1.5 px-2.5 pb-2.5 */
}

/* Ẩn nếu rỗng */
.branch-29:empty {
  display: none;
}

.branch-30 {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  /* 2px */
  font-size: 1rem;
  /* text-base */
  font-weight: 600;
  /* font-semibold */
}

/* Responsive màn ≥ 768px */
@media (min-width: 768px) {
  .branch-30 {
    font-size: 1.125rem;
    /* md:text-md */
  }
}

.color {
  width: 1.125rem;
  /* ~18px */
  height: 1.125rem;
  /* ~18px */
  fill: #FACC15;
  /* yellow-400 */
}

.cpsui-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  /* lấy gap-1 cuối cùng */
  cursor: pointer;
  border: 1px solid #ffffff;
  /* cpsui:border-pure-white */
  font-size: 0.75rem;
  /* cpsui:text-small */
  padding: 0.5rem 0.25rem;
  /* py-2x-small px-1x-small */
  min-height: 24px;
  /* cpsui:min-h-[24px] */
  border-radius: 0.125rem;
  /* cpsui:rounded-small */
  color: #3B82F6;
  /* cpsui:text-info-500 */
  background-color: #ffffff;
  /* cpsui:bg-pure-white */
  margin-left: auto;
  height: 2rem;
  /* h-8 */
}

/* Hover button */
.cpsui-button:hover {
  border-color: #F9FAFB;
  /* hover:border-neutral-50 */
  background-color: #F9FAFB;
  /* hover:bg-neutral-50 */
}

/* Hover SVG animation */
.cpsui-button:hover svg {
  animation: heartbeat 0.6s infinite;
}

/* Keyframes heartbeat */
@keyframes heartbeat {

  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.2);
  }
}

/* Disabled button */
.cpsui-button:disabled {
  cursor: not-allowed;
  border-color: #ffffff;
  color: #D1D5DB;
  /* cpsui:disabled:text-black-300 */
  background-color: #ffffff;
}

/* Responsive span */
.cpsui-button span {
  display: none;
}

@media (min-width: 640px) {
  .cpsui-button span {
    display: inline-block;
  }
}

.favourite {
  width: 1.375rem;
  /* ~22px */
  height: 1.375rem;
  /* ~22px */
}

/* Khi hover parent group */
.group:hover .favourite {
  animation: heartbeat 0.6s infinite;
}

.var {
  display: none;
}

@media (min-width: 640px) {
  .var {
    display: inline-block;
  }
}

.swiper-button-next,
.swiper-button-prev {
  color: #000000;
  width: 36px;
  height: 36px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.swiper-button-next:hover,
.swiper-button-prev:hover {
  background: #f2f2f2;
}

.product-swiper .swiper-slide {
  margin-bottom: 14px;
  /* chỉnh theo ý bạn */
}

.tabs {
  display: flex;
  border-bottom: 1px solid #eee;
  height: 3.75rem;
  margin-top: 12px;
}

.tab-btn {
  flex: 1;
  padding: 10px 0;
  text-align: center;
  font-weight: 600;
  font-size: large;
  text-transform: uppercase;
  color: #3B82F6;
  background: transparent;
  border: none;
  position: relative;
  cursor: pointer;
}

/* Line dưới khi active */
.tab-btn.active::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: -1px;
  height: 2px;
  background: #3B82F6;
  /* đỏ */
}

/* Gradient nền phía sau text */
.tab-btn.active {
  color: #3B82F6;
  background: linear-gradient(to bottom, #ffffff, #E0F2FE, #93C5FD);
}

.category-brand {
  background: #fff;
  padding-top: 16px;
}

.category-wrapper {
  position: relative;
}

.category-row {
  display: flex;
  gap: 10px;
  overflow-x: hidden;
  /* ẨN SCROLL BAR */
  scroll-behavior: smooth;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #e5e7eb;
  cursor: pointer;
}

.nav-btn.left {
  left: 0
}

.nav-btn.right {
  right: 0
}


.category-card {
  width: 200px;
  height: 68px;
  /* cao hơn để đủ 2 dòng */
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  background: #f3f4f6;
  border-radius: 16px;
  cursor: pointer;
}

.category-card img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  flex-shrink: 0;
}

.category-name {
  font-size: 14px;
  font-weight: 600;
  color: #111;
  width: 85px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  /* CHỈ 2 DÒNG */
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.2;
}



.brand-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
}

.brand-list {
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.brand-pill {
  height: 32px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid #e5e7eb;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}

.brand-pill:hover {
  border-color: #d70018;
  color: #d70018;
}

.view-all {
  font-size: 14px;
  color: #2563eb;
  white-space: nowrap;
  text-decoration: none;
}

.badge-box {
  margin-bottom: 2.5rem;
  /* mb-1 */
  display: flex;
  /* d-flex */
  align-items: center;
  /* align-items-center */
  gap: 0.25rem;
  /* gap-1 */
  border-radius: 0.25rem;
  /* rounded */
  background-color: #f3f4f6;
  /* bg-light */
  padding: 0.25rem;
  /* p-1 */
  color: #212529;
  line-height: 1.2rem;
  /* text-dark */
  font-size: 12px;
  /* small */
}
</style>