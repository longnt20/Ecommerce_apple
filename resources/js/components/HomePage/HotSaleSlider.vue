<template>
  <div class="main">
    <div class="branch-1">
      <div class="branch-2" :style="{
        background: `url(${backgrounds.flashSaleHeader}) center center / 100% 100% no-repeat`
      }">
        <div class="branch-3">
          <div class="branch-4">
            <img class="img-1" src="https://cdn2.cellphones.com.vn/x/media/wysiwyg/flashsale/fs-tab-title-8-1.png"
              alt="Flash Sale - Tab" width="412" height="101">
            <div class="branch-5">
              <div class="branch-6">
                <div :style="{ textAlign: 'center' }">
                  <img src="https://cdn2.cellphones.com.vn/x/media/wysiwyg/tagline-hot-sale.png" alt="title"
                    :style="{ height: '60px' }">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="branch-7" :style="{
        background: `url(${backgrounds.flashSaleBlock}) center center / 100% 100% no-repeat`
      }">
        <img class="img-2" src="https://cdn2.cellphones.com.vn/x/media/wysiwyg/flashsale/fs-gift-box-8-1-2.png"
          alt="Gift Box">
        <img class="img-3" src="https://cdn2.cellphones.com.vn/x/media/wysiwyg/flashsale/fs-gift-box-8-1.png"
          alt="Gift Box">

        <div class="branch-8">
          <div class="branch-9" id="HotSale_hotSale__011o1">
            <div class="branch-10">
              <div class="branch-11">
                <!-- Tab danh mục -->
                <div class="tabs">
                  <button v-for="(tab, index) in tabs" :key="tab.id"
                    :class="['px-3 py-1 rounded transition-all duration-300', { active: activeTab === index }]"
                    @click="changeTab(tab.id, index)">
                    {{ tab.name }}
                  </button>
                </div>
              </div>

              <div class="countdown" v-if="promotion">
                <span class="label">
                  {{ isEnded ? 'Đã kết thúc' : (isBeforeStart ? 'Bắt đầu sau:' : 'Kết thúc sau:') }}
                </span>

                <div v-if="!isEnded" class="time">
                  <div class="time-box">{{ countdown.days }}</div>
                  <span class="colon">:</span>
                  <div class="time-box">{{ countdown.hours }}</div>
                  <span class="colon">:</span>
                  <div class="time-box">{{ countdown.minutes }}</div>
                  <span class="colon">:</span>
                  <div class="time-box">{{ countdown.seconds }}</div>
                </div>
              </div>
            </div>

            <div class="branch-22" :style="{ overflow: 'visible' }">
              <div class="hotsale-swiper swiper swiper-horizontal rounded-2xl">
                  <div class="swiper-wrapper">
                    <div v-for="product in filteredProducts" :key="product.id" class="swiper-slide !h-auto">
                      <div class="branch-23">
                        <div class="branch-24">
                          <a class="a1" :href="product.link">
                            <span class="sp-2">
                              <img :alt="product.name" loading="lazy" width="300" height="300" decoding="async"
                                data-nimg="1" class="img-4" :src="product.thumbnail" :style="{ color: 'transparent' }">
                            </span>
                            <h3 class="title-ss">{{ product.name }}</h3>
                            <div class="branch-26">
                              <p class="price">{{ product.final_price }}đ</p>
                              <p class="cost-price">{{ product.original_price }}đ</p>
                            </div>
                            <div class="branch-27" :style="{
                              backgroundImage: `url(${backgrounds.discountBadge})`
                            }">
                              Giảm <span class="sp-3">{{ product.discount_percent }}%</span>
                            </div>
                            <div class="branch-28" :style="{
                              background: `url(${backgrounds.zeroInsBadge})`
                            }">
                              <span class="sp-4">
                                Trả góp <span class="sp-5">0%</span>
                              </span>
                            </div>
                          </a>
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
                                stroke-linecap="round" stroke-linejoin="round" class="favourite" height="1em"
                                width="1em" xmlns="http://www.w3.org/2000/svg">
                                <path
                                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                                </path>
                              </svg>
                              <span class="var">Yêu thích</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <div class="swiper-button-prev hotsale-prev"></div>
                <div class="swiper-button-next hotsale-next"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Swiper from 'swiper'
import { Autoplay, Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'

const promotion = ref(null)
const products = ref([])
const swiperRef = ref(null)
const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' })
const now = ref(Date.now())
const filteredProducts = ref([])
let startTime = 0
let endTime = 0
let timer

const isBeforeStart = computed(() => now.value < startTime)
const isEnded = computed(() => now.value > endTime)
let timerInterval = null
// Background images
const backgrounds = {
  flashSaleHeader: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/flashsale/fs-head-8-1.png',
  flashSaleBlock: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/flashsale/bg-block-fs-8-1-desk.png',
  discountBadge: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/discount-badge-ui-2025.png',
  zeroInsBadge: 'https://cdn2.cellphones.com.vn/x/media/wysiwyg/zero-ins-badge-ui-2025.png'
}

const tabs = ref([])
const activeTab = ref(0)

onMounted(async () => {
  try {
    const res = await fetch('http://127.0.0.1:8000/api/promotion-categories')
    const data = await res.json()
    if (data.data && data.data.length) {
      // Nối category từ API vào tabs
      tabs.value = [{ id: null, name: 'Tất cả' }, ...data.data]
    }
    const resProducts = await fetch('http://127.0.0.1:8000/api/promotions/')
    const productData = await resProducts.json()

    promotion.value = productData.data
    products.value = promotion.value.items || []
    filteredProducts.value = products.value
    const toLocal = date => new Date(date.replace(' ', 'T') + '+07:00').getTime()

    startTime = toLocal(promotion.value.start_date)
    endTime = toLocal(promotion.value.end_date)

    // Cập nhật đếm ngược
    updateCountdown()
    timer = setInterval(updateCountdown, 1000)

    // Đợi DOM render xong rồi khởi tạo Swiper
    await nextTick()
    const hotsale_swiper =  new Swiper('.hotsale-swiper', {
      modules: [Navigation, Autoplay],
      slidesPerView: 5,
      spaceBetween: 8,
      slidesPerGroup: 1,
      loop: false,
      speed: 600,
      grabCursor: true,
      navigation: {
        nextEl: '.hotsale-next',
        prevEl: '.hotsale-prev'
      },
      autoplay: {
        delay: 2500,
        disableOnInteraction: true
      }
    })
  } catch (e) {
    console.error('Lỗi lấy danh mục:', e)
  }
})

const changeTab = (categoryId, index) => {
  activeTab.value = index
  if (categoryId === null) {
    // Tab "Tất cả"
    filteredProducts.value = products.value
  } else {
    // Lọc theo category_id
    filteredProducts.value = products.value.filter(
      p => p.category_id === categoryId
    )
  }
}

// 🕒 Format giá
const formatPrice = (price) => Number(price).toLocaleString('vi-VN') + 'đ'

/**
 * Cập nhật đếm ngược
 */
const updateCountdown = () => {
  if (!promotion.value) return
  now.value = Date.now()

  let diff
  if (isBeforeStart.value) diff = startTime - now.value
  else if (!isEnded.value) diff = endTime - now.value
  else diff = 0

  if (diff <= 0) {
    countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' }
    return
  }
  const totalSeconds = Math.floor(diff / 1000)
  const days = Math.floor(totalSeconds / (3600 * 24))
  const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60

  countdown.value = {
    days: String(days).padStart(2, '0'),
    hours: String(hours).padStart(2, '0'),
    minutes: String(minutes).padStart(2, '0'),
    seconds: String(seconds).padStart(2, '0')
  }
}

onUnmounted(() => {
  clearInterval(timerInterval)
})

// ▶ Điều khiển slider
const slideNext = () => swiperRef.value?.swiper.slideNext()
const slidePrev = () => swiperRef.value?.swiper.slidePrev()

const toggleFavorite = (productId) => {
  console.log('Toggle favorite for product:', productId)
  // Xử lý logic yêu thích ở đây
}
</script>
<style scoped>
.main {
  margin-top: 8px;
}

.main:empty {
  display: none;
}

@media (min-width: 768px) {
  .main {
    margin-top: 24px;
  }
}

.branch-1 {
  position: relative;
  margin-top: 2rem;
  /* mt-8 */
  animation: scale-in 0.4s ease-out;
}

@media (min-width: 768px) {
  .branch-1 {
    margin-top: 2.5rem;
    /* md:mt-10 */
  }
}

@keyframes scale-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

.branch-2 {
  position: relative;
  margin-left: auto;
  margin-right: auto;
  height: 2.5rem;
  /* tương đương h-10 */
  width: calc(100% - 24px);
}

@media (min-width: 768px) {
  .branch-2 {
    height: 3.125rem;
    /* tương đương md:h-12.5 */
    width: calc(100% - 60px);
  }
}

.branch-3 {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  height: 100%;
  justify-content: space-between;
  padding-left: 1rem;
  padding-right: 1rem;
}

@media (min-width: 768px) {
  .branch-3 {
    left: 1.75rem;
    /* tương đương md:inset-x-7 */
    right: 1.75rem;
  }
}

.branch-4 {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: calc(100% + 2px);
  transform: translateY(-0.875rem);
  /* tương đương -translate-y-3.5 */
  transition: all 0.3s ease;
  user-select: none;
}

.img-1 {
  position: absolute;
  top: 0;
  z-index: 0;
  height: 100%;
  width: 100%;
}

.branch-5 {
  position: relative;
  z-index: 1;
  font-size: 1rem;
  /* text-md */
  font-weight: 800;
  /* font-extrabold */
  text-transform: uppercase;
  /* uppercase */
  font-style: italic;
  /* italic */
  text-align: center;
  /* text-center */
  padding-left: 2rem;
  /* px-8 */
  padding-right: 2rem;
  color: #ffffff;
  /* text-white */
}

@media (min-width: 768px) {
  .branch-5 {
    font-size: 1.25rem;
    /* md:text-xl */
  }
}

.branch-6 {
  width: fit-content;
}

.branch-7 {
  margin-top: 0.125rem;
  /* mt-0.5 */
  min-height: 100px;
  /* min-h-100 */
}

@media (min-width: 768px) {
  .branch-7 {
    margin-top: 0;
    /* md:mt-0 */
  }
}

.img-2 {
  position: absolute;
  top: 1.25rem;
  /* top-5 */
  left: -0.375rem;
  /* -left-1.5 */
  width: 2.875rem;
  /* size-11.5 */
  height: 2.875rem;
}

@media (min-width: 768px) {
  .img-2 {
    top: 3.5rem;
    /* md:top-14 */
    left: -1rem;
    /* md:-left-4 */
    width: 3.5rem;
    /* md:size-14 */
    height: 3.5rem;
  }
}

.img-3 {
  position: absolute;
  top: -1rem;
  /* -top-4 */
  right: 0;
  /* right-0 */
  width: 2.25rem;
  /* size-9 */
  height: 2.25rem;
}

@media (min-width: 768px) {
  .img-3 {
    top: 1rem;
    /* md:top-4 */
    right: 0.25rem;
    /* md:right-1 */
    width: 4.25rem;
    /* md:size-17 */
    height: 4.25rem;
  }
}

.branch-8 {
  padding-left: 8px;
  padding-right: 8px;
}

.branch-9 {
  position: relative;
  /* relative */
  display: flex;
  /* flex */
  flex-direction: column;
  /* flex-col */
  min-height: 110px;
  /* min-h-110 */
  padding: 0.5rem;
  /* p-2 */
  animation: fade-in 0.5s ease-in-out;
  /* animate-fade-in */
}

@media (min-width: 768px) {
  .branch-9 {
    padding: 1rem;
    /* md:p-4 */
  }
}

/* Tạo hiệu ứng fade-in */
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(5px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.branch-10 {
  margin-top: 0.5rem;
  /* mt-2 */
  margin-bottom: 0.75rem;
  /* mb-3 */
  display: flex;
  /* flex */
  flex-direction: column;
  /* flex-col */
  align-items: center;
  /* items-center */
  justify-content: space-between;
  /* justify-between */
  gap: 0.5rem;
  /* gap-2 */
  padding-left: 1.5rem;
  /* px-6 */
  padding-right: 1.5rem;
}

@media (min-width: 768px) {
  .branch-10 {
    flex-direction: row;
    /* md:flex-row */
  }
}

.branch-11 {
  display: flex;
  /* flex */
  flex-wrap: wrap;
  /* flex-wrap */
  align-items: center;
  /* items-center */
  justify-content: center;
  /* justify-center */
  column-gap: 0.75rem;
  /* gap-x-3 */
  row-gap: 0.25rem;
  /* gap-y-1 */
}

@media (min-width: 768px) {
  .branch-11 {
    justify-content: flex-start;
    /* md:justify-start */
    gap: 0.625rem;
    /* md:gap-2.5 */
  }
}

.btn-1 {
  cursor: pointer;
  border: 0;
  border-bottom: 2px solid #fde047;
  /* border-yellow-300 */
  border-radius: 0;
  padding: 0.375rem 0.25rem;
  /* py-1.5 px-1 */
  font-size: 0.75rem;
  color: #fff;
  background: transparent;
  transition: all 0.3s ease;
}

/* Hover */
.btn-1:hover {
  transform: scale(0.95);
}

/* Disabled */
.btn-1:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: scale(1);
}

/* Responsive (>=768px) */
@media (min-width: 768px) {
  .btn-1 {
    border-radius: 9999px;
    /* md:rounded-full */
    border-width: 3px;
    /* md:border-3 */
    border-color: #fff;
    /* md:border-white */
    padding: 0.5rem 1rem;
    /* md:px-4 */
    font-size: 1rem;
    /* md:text-base */
    font-weight: 700;
    /* md:font-bold */
    color: #3b82f6;
    /* md:text-primary-500 */
    background-color: #fff;
    /* md:bg-white */
  }
}

.btn-2 {
  cursor: pointer;
  border: none;
  border-bottom: 2px solid #fff;
  /* border-b-2 border-white */
  border-radius: 0;
  padding: 0.375rem 0.25rem;
  /* py-1.5 px-1 */
  font-size: 0.75rem;
  /* text-xs */
  color: #fff;
  /* text-white */
  background: transparent;
  transition: all 0.3s ease;
}

/* Hover */
.btn-2:hover {
  transform: scale(0.95);
}

/* Disabled */
.btn-2:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: scale(1);
}

/* Responsive: md ≥ 768px */
@media (min-width: 768px) {
  .btn-2 {
    border-radius: 9999px;
    /* md:rounded-full */
    border-width: 3px;
    /* md:border-3 */
    padding: 0.375rem 1rem;
    /* md:px-4 */
    font-size: 1rem;
    /* md:text-base */
    font-weight: 700;
    /* md:font-bold */
  }
}

.btn-3 {
  cursor: pointer;
  border: none;
  border-bottom: 2px solid #fff;
  /* border-b-2 border-white */
  border-radius: 0;
  padding: 0.375rem 0.25rem;
  /* py-1.5 px-1 */
  font-size: 0.75rem;
  /* text-xs */
  color: #fff;
  /* text-white */
  background: transparent;
  transition: all 0.3s ease;
}

/* Hover */
.btn-3:hover {
  transform: scale(0.95);
}

/* Disabled */
.btn-3:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: scale(1);
}

/* Responsive: md ≥ 768px */
@media (min-width: 768px) {
  .btn-3 {
    border-radius: 9999px;
    /* md:rounded-full */
    border-width: 3px;
    /* md:border-3 */
    padding: 0.375rem 1rem;
    /* md:px-4 */
    font-size: 1rem;
    /* md:text-base */
    font-weight: 700;
    /* md:font-bold */
  }
}

.branch-12 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  /* 8px */
  min-height: 2.125rem;
  /* tương đương 34px */
}

/* Responsive: từ 768px trở lên */
@media (min-width: 768px) {
  .branch-12 {
    margin-left: auto;
  }
}

.sp-1 {
  font-size: 1rem;
  /* text-base */
  font-weight: 600;
  /* font-semibold */
  color: #ffffff;
  /* text-white */
  text-transform: uppercase;
  /* uppercase */
}

/* Responsive từ 768px trở lên */
@media (min-width: 768px) {
  .sp-1 {
    font-size: 1.125rem;
    /* text-lg */
    font-weight: 500;
    /* font-medium */
  }
}

.branch-13 {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  /* 4px */
}

@media (min-width: 768px) {
  .branch-13 {
    gap: 0.5rem;
    /* 8px */
  }
}

.branch-14 {
  display: flex;
  flex-direction: column;
  /* xếp theo cột */
  align-items: center;
  /* canh giữa ngang */
}

.branch-15 {
  perspective: 1000px;
  width: 36px;
  height: 40px;
}

.branch-16 {
  position: relative;
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  transform-origin: bottom;
}

.branch-17 {
  position: absolute;
  backface-visibility: hidden;
  background-color: #fff;
  color: #000;
  font-size: 20px;
  font-weight: 700;
  width: 100%;
  height: 100%;
  line-height: 40px;
  text-align: center;
  border-radius: 6px;
}

.branch-18 {
  transform: rotateX(180deg);
}

.branch-18 {
  position: absolute;
  backface-visibility: hidden;
  background-color: #fff;
  color: #000;
  font-size: 20px;
  font-weight: 700;
  width: 100%;
  height: 100%;
  line-height: 40px;
  text-align: center;
  border-radius: 6px;
}

.hr1 {
  position: absolute;
  /* absolute */
  top: 50%;
  /* top-1/2 */
  width: 100%;
  /* w-full */
  transform: translateY(-50%);
  /* -translate-y-1/2 */
  border-top: 1px solid #b91c1c;
  /* border-t + border-red-700 */
}

.branch-19 {
  position: absolute;
  /* absolute */
  top: 50%;
  /* top-1/2 */
  right: 0;
  /* right-0 */
  height: 0.375rem;
  /* h-1.5 => 6px */
  width: 0.1875rem;
  /* w-0.75 => 3px */
  transform: translateY(-50%);
  /* -translate-y-1/2 */
  border-top-left-radius: 9999px;
  /* rounded-l-full */
  border-bottom-left-radius: 9999px;
  /* bo tròn bên trái */
  background-color: rgba(239, 68, 68, 0.5);
  /* bg-red-500/50 */
}

.branch-20 {
  position: absolute;
  /* absolute */
  top: 50%;
  /* top-1/2 */
  left: 0;
  /* left-0 */
  height: 0.375rem;
  /* h-1.5 => 6px */
  width: 0.1875rem;
  /* w-0.75 => 3px */
  transform: translateY(-50%);
  /* -translate-y-1/2 */
  border-top-right-radius: 9999px;
  /* rounded-r-full */
  border-bottom-right-radius: 9999px;
  /* bo tròn bên phải */
  background-color: rgba(239, 68, 68, 0.5);
  /* bg-red-500/50 */
}

.branch-21 {
  font-size: 1.25rem;
  /* text-xl => 20px */
  font-weight: 700;
  /* font-bold */
  color: #ffffff;
  /* text-white */
}

.branch-22 {
  margin-bottom: 0.625rem;
  /* mb-2.5 => 10px */
  height: 100%;
  /* h-full */
}

.hotsale-swiper {
  margin-left: auto;
  margin-right: auto;
  position: relative;
  overflow: hidden;
  list-style: none;
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
  height: 100%;
  z-index: 1;
  display: flex;
  transition-property: transform;
  transition-timing-function: var(--swiper-wrapper-transition-timing-function, initial);
  box-sizing: content-box;
}

.swiper-slide {
  flex-shrink: 0;
  width: 100%;
  height: 100%;
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
  min-height: 12.75rem;
  /* ~204px */
  border-radius: 1rem;
  /* 16px */
  background-color: #fff;
  animation: fade-in 0.5s ease-in-out forwards;
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
  aspect-ratio: 1 / 1;
  height: auto;
  width: 100%;
  transform: scale(0.9);
  transition: opacity 0.5s, transform 0.3s;
}

/* Khi hover parent group */
.group:hover .img-4 {
  transform: scale(0.95);
}

.title-ss {
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 2.25rem;
  font-size: 0.75rem;
  line-height: 1.5rem;
  font-weight: 700;
  color: #111827;
}

/* Responsive trên màn hình ≥ 640px */
@media (min-width: 640px) {
  .title-ss {
    height: 2.625rem;
    font-size: 1rem;
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
  margin-bottom: 2rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.375rem;
  /* 6px */
}

.branch-26 .price {
  font-size: 15px;
  font-weight: 700;
  color: #e91030;
  /* primary-600 */
  text-decoration: none;
}

/* Responsive màn ≥ 640px */
@media (min-width: 640px) {
  .price {
    font-size: 0.875rem;
    /* text-medium */
  }
}

.branch-26 .cost-price {
  font-size: 14px;
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
  padding: 0 0.625rem 0 0.625rem;
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

.countdown {
  display: flex;
  align-items: center;
  gap: 12px;
  font-family: 'Inter', sans-serif;
  font-size: 18px;
}

.countdown .label {
  font-weight: 600;
  color: #ffffff;
}

.time {
  display: flex;
  align-items: center;
  gap: 6px;
}

.time-box {
  width: 42px;
  height: 42px;
  background: #f5f6ff;
  color: #1a1a1a;
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 700;
  font-size: 18px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.colon {
  font-weight: 700;
  color: #333;
  font-size: 18px;
}

.tabs {
  display: flex;
  gap: 10px;
}

.tabs button {
  padding: 8px 16px;
  border-radius: 24px;
  /* bo tròn như ảnh */
  border: 2px solid #fff;
  /* viền trắng */
  background-color: transparent;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.tabs button.active {
  background-color: #f3ebeb;
  /* màu nền tab active */
  border-color: #f15a5a;
  /* đổi viền khi active */
  color: rgb(238, 11, 11);
  font-weight: 600;

}

.tabs button:hover:not(.active) {
  background-color: rgba(255, 255, 255, 0.2);
  /* hover nhẹ */
}
</style>