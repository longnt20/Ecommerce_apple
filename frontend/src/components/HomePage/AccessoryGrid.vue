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
import phukienapple from '@/assets/phukienapple.webp'
import capsac from '@/assets/capsac.webp'
import pinsacdp from '@/assets/pinsacdp.webp'
import oplung from '@/assets/oplung.webp'
import danmh from '@/assets/danmh.webp'
import thenho from '@/assets/thenho.webp'
import gaminggear from '@/assets/gaminggear.webp'
import sim4g from '@/assets/sim4g.webp'
import thietbimang from '@/assets/thietbimang.webp'
import cam from '@/assets/cam.webp'
import gimbal from '@/assets/gimbal.webp'
import flycam from '@/assets/flycam.webp'
import mayanh from '@/assets/mayanh.webp'
import chuot from '@/assets/chuot.webp'
import balo from '@/assets/balo.webp'
import hub from '@/assets/hub.webp'
import pkdt from '@/assets/pkdt.webp'
import pklt from '@/assets/pklt.webp'
const accessories = [
  { id: 1, name: 'Phụ kiện Apple', image: phukienapple },
  { id: 2, name: 'Cáp, sạc', image: capsac },
  { id: 3, name: 'Pin sạc dự phòng', image: pinsacdp },
  { id: 4, name: 'Ốp lưng - Bao da', image: oplung },
  { id: 5, name: 'Dán màn hình', image: danmh },
  { id: 6, name: 'Thẻ nhớ, USB', image: thenho },

  { id: 7, name: 'Gaming Gear, Playstation', image: gaminggear },
  { id: 8, name: 'Sim 4G - 5G', image: sim4g },
  { id: 9, name: 'Thiết bị mạng', image: thietbimang },
  { id: 10, name: 'Camera', image: cam },
  { id: 11, name: 'Gimbal', image: gimbal },
  { id: 12, name: 'Flycam', image: flycam },

  { id: 13, name: 'Máy ảnh', image: mayanh },
  { id: 14, name: 'Chuột, bàn phím', image: chuot },
  { id: 15, name: 'Balo, túi xách', image: balo },
  { id: 16, name: 'Hub chuyển đổi', image: hub },
  { id: 17, name: 'Phụ kiện điện thoại', image: pkdt },
  { id: 18, name: 'Phụ kiện Laptop', image: pklt }
]
</script>

<style scoped>
.accessory-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px 3px 20px 3px;
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
