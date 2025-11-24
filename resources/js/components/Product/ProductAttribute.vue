<template>
  <div class="wrapper">

    <!-- PHIÊN BẢN -->
    <div class="section">
      <div class="title">Phiên bản</div>

      <div class="storage-list">
        <div
          v-for="item in storage"
          :key="item"
          class="storage-item"
          :class="{ active: selectedStorage === item }"
          @click="selectedStorage = item"
        >
          {{ item }}
          <span v-if="selectedStorage === item" class="check"></span>
        </div>
      </div>
    </div>

    <!-- MÀU SẮC -->
    <div class="section">
      <div class="title">Màu sắc</div>

      <div class="color-list">
        <div
          v-for="item in colors"
          :key="item.name"
          class="color-item"
          :class="{ active: selectedColor === item.name }"
          @click="selectedColor = item.name"
        >
                <img :src="item.img" class="color-img">
            </img>
            <div class="">
                <div class="color-name">{{ item.name }}</div>
          <div class="color-price">{{ item.price }}đ</div>
            </div>
          <span v-if="selectedColor === item.name" class="check"></span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  data() {
    return {
      storage: ["1TB", "512GB", "256GB"],
      selectedStorage: "256GB",

      colors: [
        {
          name: "Titan Tự Nhiên",
          img: "https://cdn2.cellphones.com.vn/insecure/rs:fill:50:50/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-16-pro-max-titan-tu-nhien.png",
          price: "30.490.000",
        },
        {
          name: "Titan Đen",
          img: "https://cdn2.cellphones.com.vn/insecure/rs:fill:50:50/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-16-pro-max-titan-den.png",
          price: "30.490.000",
        },
        {
          name: "Titan Trắng",
          img: "https://cdn2.cellphones.com.vn/insecure/rs:fill:50:50/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-16-pro-max-titan-trang.png",
          price: "30.490.000",
        },
        {
          name: "Titan Sa Mạc",
          img: "https://cdn2.cellphones.com.vn/insecure/rs:fill:50:50/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-16-pro-max-titan-sa-mac.png",
          price: "30.390.000",
        },
      ],

      selectedColor: "Titan Sa Mạc",
    };
  },
};
</script>

<style scoped>
.wrapper {
  font-family: sans-serif;
}

.section {
  margin-bottom: 22px;
}
.title {
  font-size: 17px;
  font-weight: 600;
  margin-bottom: 12px;
}

/* STORAGE BUTTONS */
.storage-list {
  display: flex;
  gap: 12px;
}

.storage-item {
  padding: 14px 26px;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  font-size: 17px;
  cursor: pointer;
  position: relative;
  transition: 0.15s;
}

.storage-item.active {
  border: 2px solid #c40000;
}

.check {
  width: 16px;
  height: 16px;
  background: #c40000;
  border-radius: 5px;
  position: absolute;
  top: -1px;
  right:-1px;
}

/* Nếu muốn thêm icon ✔ trắng bên trong */
.check::after {
  content: "✔";
  color: white;
  font-size: 12px;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -55%);
}
/* COLOR OPTIONS */
.color-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.color-item {
  display: flex;
  gap: 2px;
  align-items: center;
  width: 160px;
  padding: 5px;
  border: 1.5px solid #ddd;
  border-radius: 12px;
  cursor: pointer;
  position: relative;
  transition: 0.15s;
  min-height: 60px;
}

.color-item.active {
  border: 2px solid #c40000;
}

.color-img {
  width: 40px;
  height: auto;        
  display: block;
  margin-top: 1px;
}

.color-name {
  font-size: 14px;
  font-weight: 600;
}

.color-price {
  font-size: 14px;
  margin-top: 2px;
}
</style>
