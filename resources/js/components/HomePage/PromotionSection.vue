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
const studentPromos = [
  { title: 'Laptop Student', image: '/logos/1.webp' },
  { title: 'Copilot PC', image: '/logos/2.webp' },
  { title: 'S-Student', image: '/logos/3.jpg' },
  { title: 'iPad Student', image: '/logos/4.webp' },
]

const paymentPromos = [
  { title: 'HSBC', image: '/logos/5.webp' },
  { title: 'OCB', image: '/logos/6.webp' },
  { title: 'Standard Chartered', image: '/logos/7.png' },
  { title: 'Home Credit', image: '/logos/8.webp' },
]

const brands = [
  { name: 'Apple', image: '/logos/9.webp' },
  { name: 'Asus', image: '/logos/10.webp' },
  { name: 'Samsung', image: '/logos/11.webp' },
  { name: 'Xiaomi', image: '/logos/12.webp' },
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
