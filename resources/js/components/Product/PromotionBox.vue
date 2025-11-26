<template>
  <div class="promo-box">
    <div class="promo-header">
      <i class="fa-solid fa-gift"></i>
      Khuyến mãi hấp dẫn
    </div>

    <ul class="promo-list">
      <li v-for="(item, index) in promotions" :key="index">
        <div class="num">{{ index + 1 }}</div>
        <div class="content">
          {{ item.text }}
          <a href="#" class="more">Xem chi tiết</a>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: "PromotionBox",
  data() {
    return {
      promotions: [
        {
          text: "Đặc quyền trợ giá lên đến 3 triệu khi thu cũ lên đời iPhone",
        },
        {
          text: "Giảm ngay 5% tối đa 500k mua Apple Watch/Airpods khi mua iPhone",
        },
        {
          text: "Trả góp 0% lãi suất, tối đa 12 tháng, trả trước từ 10% qua CTTC hoặc 0đ qua thẻ tín dụng",
        },
        {
          text: "Tặng Sim/Esim VNSKY, có ngay 5GB data 5G/ngày, miễn phí 30 ngày đầu - chỉ áp dụng tại cửa hàng",
        },
        {
          text: "Giảm thêm 10% cho Loa, Tai nghe, Máy tính bàn, TV (từ 10 triệu) khi mua Điện thoại/Laptop",
        },
      ],
    };
  },
};
</script>

<style scoped>
.promo-box {
  background: linear-gradient(249.83deg, #eff5ff 3.89%, rgba(244, 251, 255, .25) 65.75%);;
  border: 1px solid #609afa;
  padding: 20px;
  border-radius: 12px;
  margin-top: 20px;
  margin-right: 40px;
}

.promo-header {
  font-size: 20px;
  font-weight: 600;
  color: #121212;
  margin-bottom: 14px;
  display: flex;
  gap: 8px;
  align-items: center;
}

.promo-header i {
  color: #e63946;
  font-size: 20px;
}

.promo-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.promo-list li {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.num {
  width: 26px;
  height: 26px;
  background: #e8f0fe;
  color: #0a43c8;
  border-radius: 50%;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.content {
  font-size: 15px;
  color: #333;
  line-height: 1.4;
}

.more {
  color: #0a43c8;
  font-weight: 500;
  margin-left: 6px;
  text-decoration: none;
}
</style>
