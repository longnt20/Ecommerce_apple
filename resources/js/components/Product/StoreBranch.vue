<template>
  <div class="store-wrapper">

    <!-- Header -->
    <div class="header-row">
      <div>
        <div class="title">Xem chi nhánh có hàng</div>
        <div class="sub">
          Có <span class="count">{{ product?.warehouse_count }}</span> cửa hàng có sản phẩm
        </div>
      </div>

      <div class="filter-group">
        <div class="select-box">
          <select>
            <option>Hồ Chí Minh</option>
            <option>Hà Nội</option>
            <option>Đà Nẵng</option>
          </select>
        </div>

        <div class="select-box">
          <select>
            <option>Quận/Huyện</option>
            <option>Thủ Đức</option>
            <option>Quận 1</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Branch list -->
    <div class="branch-list">

      <div class="branch-card" v-for="w in product?.warehouse" :key="w.warehouse_id">
        <div class="address">
          {{ w.address }} , {{ w.district }}, {{ w.city }}
        </div>

        <div class="info-row">
          <div class="phone">
            <i class="fa-solid fa-phone"></i>
            {{ w.phone }}
          </div>

          <div class="map">
            <i class="fa-solid fa-location-dot"></i>
            Bản đồ
          </div>
        </div>
      </div>


    </div>

  </div>
</template>

<script>
export default {
  name: "StoreBranch",
  props: {
    product: Object,
  },
};
</script>

<style scoped>
.store-wrapper {
  background: #f7f7f8;
  padding: 20px;
  border-radius: 16px;
  margin-right: 40px;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.title {
  font-weight: 600;
  font-size: 16px;
}

.sub {
  color: #555;
  margin-top: 2px;
}

.count {
  color: #0066ff;
  font-weight: 600;
}

.filter-group {
  display: flex;
  gap: 10px;
}

.select-box select {
  width: 150px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid #ddd;
  background: white;
  font-size: 14px;
  cursor: pointer;
}

.branch-list {
  display: flex;
  align-items: center;
  gap: 12px;
  overflow-x: auto;
  padding: 4px 0;
}

.branch-card {
  min-width: 260px;
  background: white;
  border-radius: 10px;
  padding: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.address {
  font-size: 14px;
  line-height: 1.4;
  margin-bottom: 10px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.phone {
  background: #fce4e4;
  padding: 6px 10px;
  border-radius: 12px;
  color: #d70000;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

.map {
  background: #ffffff;
  border: 1px solid gray;
  padding: 6px 10px;
  border-radius: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
  color: #333;
}

.map:hover {
  background: f1f3f4;
}

.left {
  margin-right: 4px;
}

.right {
  margin-left: 4px;
}

@media (max-width: 768px) {
  .header-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
