<template>
  <div class="offer-box">
    <div class="offer-title">
      <i class="fa-solid fa-gift"></i>
      <h2>Ưu đãi thanh toán</h2>
    </div>

    <ul class="offer-list">
      <li v-for="(item, index) in offers" :key="index">
        <span class="check">✔</span>
        <img v-if="item.logo" :src="item.logo" class="logo" />
        <p>{{ item.text }}</p>
      </li>
    </ul>
  </div>
</template>

<script setup>
const offers = [
  { text: "Xem chính sách ưu đãi dành cho thành viên Smember" },
  { logo: "/logos/DUMT_ZV0.webp", text: "Giảm đến 5.000.000đ khi thanh toán qua Kredivo" },
  { logo: "/logos/hsbc_icon.webp", text: "Hoàn tiền đến 2 triệu khi mở thẻ tín dụng HSBC" },
  { logo: "/logos/vibb_bank.webp", text: "Mở thẻ VIB nhận E-Voucher đến 600K" },
  { logo: "/logos/image_1740.webp", text: "Giảm 500K khi thanh toán bằng thẻ tín dụng HDBank" },
  { logo: "/logos/image_1747.webp", text: "Trả góp 0 lãi, phí + tặng 500k khi mở thẻ TPBANK EVO" },
  { logo: "/logos/image_1009_1__1.webp", text: "Giảm đến 300K khi thanh toán qua VNPAY-QR" },
  { text: "Liên hệ B2B để được tư vấn giá tốt nhất cho khách hàng doanh nghiệp khi mua số lượng nhiều" },
]
</script>

<style scoped>
.offer-box {
  border: 1px solid #dcdce0;
  padding: 20px;
  border-radius: 12px;
  background: linear-gradient(249.83deg, #eff5ff 3.89%, rgba(244, 251, 255, .25) 65.75%);;
  margin-right: 40px;
  margin-top: 20px;
}

.offer-title {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 18px;
}
.offer-title i {
  color: #e63946;
  font-size: 20px;
}
.offer-title h2 {
  font-size: 20px;
  font-weight: 600;
  color: #121212;
}

.offer-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.offer-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 0;
}

.check {
  font-size: 20px;
  color: #4caf50;
}

.logo {
  width: 50px;
  height: auto;
  object-fit: contain;
}

.offer-list p {
  margin: 0;
  font-size: 16px;
  color: #333;
}
</style>
