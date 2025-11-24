<template>
  <div class="product-header container">
    <!-- LEFT: Title + rating + actions -->
    <div class="left-block">
      <h1 class="product-title">Điện thoại iPhone 16 Pro Max 256GB</h1>

      <div class="rating-row">
        <div class="star">⭐</div>
        <span class="rating-score">4.9</span>
        <span class="rating-count">(346 đánh giá)</span>
      </div>
        <div class="rating-row">
        <a class="action-btn" href="#">❤ Yêu thích</a>
        <span class="divider"></span>

        <a class="action-btn" href="#">💬 Hỏi đáp</a>
        <span class="divider"></span>

        <a class="action-btn" href="#">⚙ Thông số</a>
        <span class="divider"></span>

        <a class="action-btn" href="#">➕ So sánh</a>
      </div>
    </div>

    <!-- RIGHT: Price box -->
    <div class="price-box">
      <div class="price-column">
        <div class="label">Giá sản phẩm</div>
        <div class="price-main">30.390.000đ</div>
        <div class="price-old">34.990.000đ</div>
      </div>

      <div class="vertical-line"></div>

      <div class="price-column">
        <div class="label promo-label">Thu cũ lên đời chỉ từ</div>
        <div class="price-main">28.390.000đ</div>
        <div class="price-sale">Trợ giá đến 2.000.000</div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Layout 2 bên */
.product-header {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  align-items: flex-start;
}

/* Left block */
.product-title {
  font-size: 26px;
  font-weight: 700;
  margin-bottom: 8px;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #5a5a5a;
  font-size: 15px;
}

.divider {
  height: 16px;
  width: 1px;
  background: #ddd;
  display: inline-block;
}

.action-btn {
  color: #0065ff;
  text-decoration: none;
  font-size: 15px;
}

/* RIGHT PRICE BOX */
.price-box {
  background: #f5f9ff;
  border: 1px solid #d7e5ff;
  padding: 18px 24px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 24px;
  min-width: 500px;
  margin-right: 13px;
  margin-bottom: 10px;
}

.price-column .label {
  font-size: 15px;
  color: #333;
  margin-bottom: 4px;
}

.promo-label {
  background: #e4ecff;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 14px;
}

.price-main {
  font-size: 22px;
  font-weight: 700;
  color: #000;
}

.price-old {
  font-size: 15px;
  text-decoration: line-through;
  color: #999;
}

.price-sale {
  font-size: 15px;
  color: red;
  margin-top: 2px;
}

.vertical-line {
  width: 1px;
  height: 55px;
  background: #d2d9e6;
}
</style>
