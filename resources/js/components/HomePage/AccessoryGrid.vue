<template>
  <section class="accessory-section">
    <!-- Header -->
    <div class="section-header">
      <h2>SẮM THÊM PHỤ KIỆN CHẤT LƯỢNG</h2>
      <a href="#" class="view-all">Xem tất cả ›</a>
    </div>

    <!-- Grid -->
    <div class="accessory-grid">
      <div
        v-for="item in accessories"
        :key="item.id"
        class="accessory-item"
      >
        <img :src="item.image" :alt="item.name" />
        <span>{{ item.name }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
const accessories = [
  { id: 1, name: 'Phụ kiện Apple', image: '/logos/phukienapple.webp' },
  { id: 2, name: 'Cáp, sạc', image: '/logos/capsac.webp' },
  { id: 3, name: 'Pin sạc dự phòng', image: '/logos/pinsacdp.webp' },
  { id: 4, name: 'Ốp lưng - Bao da', image: '/logos/oplung.webp' },
  { id: 5, name: 'Dán màn hình', image: '/logos/danmh.webp' },
  { id: 6, name: 'Thẻ nhớ, USB', image: '/logos/thenho.webp' },

  { id: 7, name: 'Gaming Gear, Playstation', image: '/logos/gaminggear.webp' },
  { id: 8, name: 'Sim 4G - 5G', image: '/logos/sim4g.webp' },
  { id: 9, name: 'Thiết bị mạng', image: '/logos/thietbimang.webp' },
  { id: 10, name: 'Camera', image: '/logos/cam.webp' },
  { id: 11, name: 'Gimbal', image: '/logos/gimbal.webp' },
  { id: 12, name: 'Flycam', image: '/logos/flycam.webp' },

  { id: 13, name: 'Máy ảnh', image: '/logos/mayanh.webp' },
  { id: 14, name: 'Chuột, bàn phím', image: '/logos/chuot.webp' },
  { id: 15, name: 'Balo, túi xách', image: '/logos/balo.webp' },
  { id: 16, name: 'Hub chuyển đổi', image: '/logos/hub.webp' },
  { id: 17, name: 'Phụ kiện điện thoại', image: '/logos/pkdt.webp' },
  { id: 18, name: 'Phụ kiện Laptop', image: '/logos/pklt.webp' }
]
</script>

<style scoped>
.accessory-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h2 {
  font-size: 20px;
  font-weight: 700;
}

.view-all {
  font-size: 14px;
  font-weight: 600;
  color: #2563eb;
  text-decoration: none;
}

.view-all:hover {
  text-decoration: underline;
}

/* Grid */
.accessory-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  border: 1px solid #eee;
  border-radius: 12px;
  overflow: hidden;
}

.accessory-item {
    display: flex;
    align-items: center;
    gap: 3px;
  padding: 10px 10px;
  text-align: center;
  border-right: 1px solid #eee;
  border-bottom: 1px solid #eee;
  cursor: pointer;
  transition: background 0.2s ease;
}

.accessory-item:hover {
  background: #f8fafc;
}

.accessory-item img {
  width: 70px;
  height: 70px;
  object-fit: contain;
  margin-bottom: 8px;
}

.accessory-item span {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  display: block;
}

/* Remove border right for last column */
.accessory-item:nth-child(6n) {
  border-right: none;
}
</style>
