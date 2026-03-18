<template>
  <section class="promo-wrapper">
    <!-- ƯU ĐÃI SINH VIÊN -->
    <div class="promo">
        <div class="promo-box">
      <h2 class="promo-title">ƯU ĐÃI SINH VIÊN</h2>
      <div class="promo-grid">
        <div
          v-for="(item, index) in studentPromos"
          :key="index"
          class="promo-item"
        >
          <img :src="item.image" :alt="item.title" />
        </div>
      </div>
    </div>

    <!-- ƯU ĐÃI THANH TOÁN -->
    <div class="promo-box">
      <h2 class="promo-title">ƯU ĐÃI THANH TOÁN</h2>
      <div class="promo-grid">
        <div
          v-for="(item, index) in paymentPromos"
          :key="index"
          class="promo-item"
        >
          <img :src="item.image" :alt="item.title" />
        </div>
      </div>
    </div>
    </div>

    <!-- CHUYỂN TRANG THƯƠNG HIỆU -->
    <div class="brand-section">
      <h2 class="promo-title1">CHUYÊN TRANG THƯƠNG HIỆU</h2>
      <div class="brand-grid">
        <div
          v-for="(brand, index) in brands"
          :key="index"
          class="brand-item"
        >
          <img :src="brand.image" :alt="brand.name" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import student1 from '@/assets/1.webp'
import student2 from '@/assets/2.webp'
import student3 from '@/assets/3.jpg'
import student4 from '@/assets/4.webp'
import payment1 from '@/assets/5.webp'
import payment2 from '@/assets/6.webp'
import payment3 from '@/assets/7.png'
import payment4 from '@/assets/8.webp'
import brand1 from '@/assets/9.webp'
import brand2 from '@/assets/10.webp'
import brand3 from '@/assets/11.webp'
import brand4 from '@/assets/12.webp'
const studentPromos = [
  { title: 'Laptop Student', image: student1 },
  { title: 'Copilot PC', image: student2 },
  { title: 'S-Student', image: student3 },
  { title: 'iPad Student', image: student4 },
]

const paymentPromos = [
  { title: 'HSBC', image: payment1 },
  { title: 'OCB', image: payment2 },
  { title: 'Standard Chartered', image: payment3 },
  { title: 'Home Credit', image: payment4 },
]

const brands = [
  { name: 'Apple', image: brand1 },
  { name: 'Asus', image: brand2 },
  { name: 'Samsung', image: brand3 },
  { name: 'Xiaomi', image: brand4 },
]
</script>

<style scoped>
.promo-wrapper {
  max-width: 1280px;
  margin: 0 auto;
  padding: 16px 0 16px 0;
}

.promo-box {
  background: #f8f8f8;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 24px;
}

.promo-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 16px;
  text-align: center;
}
.promo-title1 {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 16px;
}
.promo {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.promo-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.promo-item {
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
}

.promo-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.brand-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.brand-item {
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
}

.brand-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Responsive */
@media (max-width: 768px) {
  .promo-grid {
    grid-template-columns: 1fr;
  }

  .brand-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
