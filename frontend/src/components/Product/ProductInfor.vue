<template>
  <div class="product-header container">
    <!-- LEFT: Title + rating + actions -->
    <div class="left-block">
      <h1 class="product-title">{{ formattedName  }}</h1>

      <div class="rating-row top-row">
        <Star class="icon-star" />
        <span class="rating-score">4.9</span>
        <span class="rating-count">(346 đánh giá)</span>
      </div>

      <!-- HÀNG 2: ACTION BUTTONS -->
      <div class="rating-row bottom-row">
        <a class="action-btn" href="#">
          <Heart class="icon" />
          Yêu thích
        </a>

        <span class="divider"></span>

        <a class="action-btn" href="#">
          <MessageCircle class="icon" />
          Hỏi đáp
        </a>

        <span class="divider"></span>

        <a class="action-btn" href="#">
          <Settings class="icon" />
          Thông số
        </a>

        <span class="divider"></span>

        <a class="action-btn" href="#">
          <Scale class="icon" />
          So sánh
        </a>
      </div>
    </div>

    <!-- RIGHT: Price box -->
    <div class="price-box">
      <div class="price-column">
        <div class="label">Giá sản phẩm</div>
        <div class="price-main">{{ product?.selected_variant?.final_price }}đ </div>
        <div class="price-old">{{ product?.selected_variant?.original_price }}đ</div>
      </div>

      <div class="vertical-line"></div>

      <div class="price-column">
        <div class="label promo-label">Thu cũ lên đời chỉ từ</div>
        <div class="price-main">{{ promoPrice }}đ</div>
        <div class="price-sale">Trợ giá đến 2.000.000</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Star, Heart, MessageCircle, Settings, Scale } from "lucide-vue-next";
import { computed } from "vue";
const props = defineProps({
  product: {
    type: Object,
    default: null
  }
})
const promoPrice = computed(() => {
  if (!props.product?.selected_variant) return 0;
  const price = Number(props.product?.selected_variant?.final_price.replace(/\./g, ''));
  const result = price - 2000000;
  return result.toLocaleString('vi-VN'); // "23.990.000"
})
const formattedName = computed(() => {
  if (!props.product) return "";

  const baseName = props.product.name;
  const variant = props.product?.selected_variant;

  if (!variant) return baseName;

  return `${baseName} ${variant.storage} | Chính hãng VN/A`;
});
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
  margin-left: 20px;
  align-items: flex-start;
}

/* Left block */
.product-title {
  font-size: 26px;
  font-weight: 700;
  margin-left: 10px;
  margin-bottom: 8px;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #5a5a5a;
  font-size: 15px;
  margin-left: 10px;
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
  min-width: 570px;
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

.rating-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
}

.top-row {
  margin-bottom: 6px;
}

.icon-star {
  width: 18px;
  height: 18px;
  color: #ffca28;
  /* vàng */
  fill: #ffc107;
}

.rating-score {
  font-weight: bold;
  font-size: 16px;
}

.rating-count {
  color: #666;
}

/* BOTTOM ACTIONS */
.bottom-row {
  margin-top: 4px;
  gap: 10px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  text-decoration: none;
  color: #007bff;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
}

.action-btn .icon {
  width: 20px;
  height: 20px;
}

/* Divider */
.divider {
  width: 1px;
  height: 14px;
  background: #ddd;
  margin: 0 4px;
}
</style>
