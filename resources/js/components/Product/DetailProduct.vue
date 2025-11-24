<template>
  <div class="product-page">
    <!-- Breadcrumb -->
    <div class="container breadcrumb">
      <a href="#">Trang chủ</a> &gt; 
      <a href="#">Điện thoại</a> &gt; 
      <a href="#">Apple</a> &gt; 
      <span>iPhone 16 Pro Max 256GB</span>
    </div>

    <!-- Header -->
    <div class="container product-header">
      <h1>Điện thoại iPhone 16 Pro Max 256GB</h1>
      <div class="rating-area">
        <span class="stars">★★★★★</span>
        <span class="count">(45 đánh giá)</span>
        <a href="#" class="compare-link">+ So sánh</a>
      </div>
    </div>

    <div class="container main-layout">
      <!-- LEFT COLUMN -->
      <div class="left-column">
        
        <!-- Gallery -->
        <div class="gallery-box">
          <div class="main-image">
            <div class="overlay-text">
              <h3>TÍNH NĂNG NỔI BẬT</h3>
              <ul>
                <li>Titan Sa mạc vẻ đẹp sang trọng</li>
                <li>Chip A18 Pro hiệu năng đỉnh cao</li>
                <li>Camera Control chuyên nghiệp</li>
              </ul>
            </div>
            <img src="https://cdn2.cellphones.com.vn/insecure/rs:fill:0:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-16-pro-max-titan-sa-mac-1.jpg" alt="iPhone 16" />
          </div>
          <div class="thumbnails">
            <div v-for="i in 5" :key="i" class="thumb-item">
              <span>Ảnh {{ i }}</span>
            </div>
          </div>
        </div>

        <!-- Policies -->
        <div class="policy-grid">
          <div v-for="(policy, index) in policies" :key="index" class="policy-item">
            <span class="icon-check">✓</span>
            <p>{{ policy }}</p>
          </div>
        </div>

        <!-- Specs -->
        <div class="specs-box section-box">
          <h3>Thông số kỹ thuật</h3>
          <div class="specs-table">
            <div v-for="(spec, index) in specs" :key="index" class="spec-row">
              <div class="spec-label">{{ spec.label }}</div>
              <div class="spec-value">{{ spec.value }}</div>
            </div>
          </div>
          <button class="btn-outline">Xem cấu hình chi tiết</button>
        </div>

        <!-- FAQ -->
        <div class="faq-box section-box">
          <h3>Câu hỏi thường gặp</h3>
          <div class="faq-list">
            <div v-for="q in faqs" :key="q" class="faq-item">
              <span>{{ q }}</span>
              <span class="toggle">+</span>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-column">
        
        <!-- Price Tabs -->
        <div class="price-tabs">
          <div 
            class="price-tab" 
            :class="{ active: priceMode === 'buy' }"
            @click="priceMode = 'buy'"
          >
            <span class="tab-label">Giá sản phẩm</span>
            <span class="price-main">30.390.000đ</span>
            <span class="price-old">34.990.000đ</span>
          </div>
          <div 
            class="price-tab" 
            :class="{ active: priceMode === 'tradein' }"
            @click="priceMode = 'tradein'"
          >
            <span class="tab-label">Thu cũ lên đời từ</span>
            <span class="price-main">28.390.000đ</span>
            <span class="price-note">Trợ giá 2.000.000đ</span>
          </div>
        </div>

        <!-- Storage Selector -->
        <div class="option-group">
          <label>Dung lượng</label>
          <div class="option-list">
            <button 
              v-for="storage in storages" 
              :key="storage"
              :class="['btn-option', { active: selectedStorage === storage }]"
              @click="selectedStorage = storage"
            >
              {{ storage }}
            </button>
          </div>
        </div>

        <!-- Color Selector -->
        <div class="option-group">
          <label>Màu sắc</label>
          <div class="color-grid">
            <button 
              v-for="color in colors" 
              :key="color.name"
              :class="['btn-color', { active: selectedColor === color.name }]"
              @click="selectedColor = color.name"
            >
              <div class="color-circle" :style="{ backgroundColor: color.hex }"></div>
              <span class="color-name">{{ color.name }}</span>
              <span class="color-price">{{ color.price }}</span>
            </button>
          </div>
        </div>

        <!-- Promotion -->
        <div class="promo-box">
          <div class="promo-header">🎁 Khuyến mãi hấp dẫn</div>
          <div class="promo-content">
            <div v-for="(promo, idx) in promotions" :key="idx" class="promo-item">
              <span class="promo-badge">{{ idx + 1 }}</span>
              <span>{{ promo }}</span>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div class="action-buttons">
          <button class="btn-buy-now">
            <strong>MUA NGAY</strong>
            <span>(Giao nhanh từ 2 giờ hoặc nhận tại cửa hàng)</span>
          </button>
          <button class="btn-cart">
            <span class="icon-cart">+</span>
            <span>Giỏ hàng</span>
          </button>
        </div>

        <div class="installment-buttons">
          <button class="btn-blue">
            <strong>TRẢ GÓP 0%</strong>
            <span>Duyệt nhanh qua điện thoại</span>
          </button>
          <button class="btn-blue">
            <strong>TRẢ GÓP QUA THẺ</strong>
            <span>Visa, Master, JCB</span>
          </button>
        </div>

        <!-- Extra Offers -->
        <div class="extra-offers">
          <div class="offer-title">Ưu đãi thanh toán</div>
          <div v-for="offer in extraOffers" :key="offer" class="offer-item">
            <span class="check-green">✓</span> {{ offer }}
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// --- STATE & DATA ---
const priceMode = ref('buy');
const selectedStorage = ref('256GB');
const selectedColor = ref('Titan Sa Mạc');

const policies = [
  "Máy mới 100%, chính hãng Apple Việt Nam",
  "1 ĐỔI 1 trong 30 ngày nếu có lỗi NSX",
  "Giao hàng miễn phí toàn quốc",
  "Bảo hành chính hãng 12 tháng"
];

const specs = [
  { label: "Kích thước màn hình", value: "6.9 inches" },
  { label: "Công nghệ màn hình", value: "Super Retina XDR OLED" },
  { label: "Camera sau", value: "Chính 48MP & Phụ 48MP, 12MP" },
  { label: "Camera trước", value: "12 MP" },
  { label: "Chipset", value: "Apple A18 Pro" },
  { label: "Dung lượng RAM", value: "8 GB" },
  { label: "Pin", value: "4676 mAh" },
  { label: "Hệ điều hành", value: "iOS 18" },
];

const storages = ['256GB', '512GB', '1TB'];

const colors = [
  { name: 'Titan Tự Nhiên', price: '30.390.000đ', hex: '#9e9e98' },
  { name: 'Titan Trắng', price: '30.390.000đ', hex: '#f2f2f2' },
  { name: 'Titan Đen', price: '30.390.000đ', hex: '#3b3b3b' },
  { name: 'Titan Sa Mạc', price: '30.390.000đ', hex: '#beab96' },
];

const promotions = [
  "Giảm ngay 1% tối đa 500k khi thanh toán Kredivo",
  "Thu cũ đổi mới: Trợ giá đến 2 triệu",
  "Giảm thêm 300K cho thành viên Smember",
  "Mua kèm phụ kiện Apple giảm đến 20%"
];

const extraOffers = [
    "Giảm thêm 500.000đ qua VNPAY",
    "Hoàn tiền 2 triệu mở thẻ HSBC",
    "Tặng gói bảo hành VIP"
];

const faqs = [
    "iPhone 16 Pro Max có những màu nào?",
    "Dung lượng pin dùng được bao lâu?",
    "Camera Control dùng để làm gì?"
];
</script>

<style scoped>
/* --- VARIABLES --- */
:root {
  --primary-red: #d70018;
  --primary-blue: #00483d;
  --bg-gray: #f9fafb;
  --border-color: #e5e7eb;
  --text-main: #333;
  --text-sub: #707070;
}

/* --- GLOBAL RESET & LAYOUT --- */
.product-page {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: #333;
  background-color: #f4f6f8;
  padding-bottom: 50px;
  font-size: 14px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

a { text-decoration: none; color: inherit; }
ul { list-style: none; padding: 0; margin: 0; }

/* --- BREADCRUMB & HEADER --- */
.breadcrumb {
  padding: 10px 15px;
  font-size: 12px;
  color: #707070;
}
.breadcrumb span { color: #000; }

.product-header {
  border-bottom: 1px solid #ddd;
  padding-bottom: 15px;
  margin-bottom: 20px;
}
.product-header h1 {
  font-size: 24px;
  margin: 0 0 10px 0;
}
.rating-area {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
}
.stars { color: #fbbf24; }
.compare-link { color: #0066cc; }

/* --- MAIN GRID LAYOUT --- */
.main-layout {
  display: grid;
  grid-template-columns: 1.6fr 1.1fr; /* Chia tỉ lệ 60% - 40% */
  gap: 20px;
}

/* Responsive Mobile */
@media (max-width: 768px) {
  .main-layout { grid-template-columns: 1fr; }
}

/* --- LEFT COLUMN STYLES --- */
.gallery-box {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}

.main-image {
  position: relative;
  height: 400px;
  background: linear-gradient(135deg, #ffd1dc 0%, #fff3e0 100%);
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 15px;
  overflow: hidden;
}

.main-image img {
  max-height: 90%;
  mix-blend-mode: multiply;
}

.overlay-text {
  position: absolute;
  top: 20px;
  left: 20px; /* Đổi sang trái cho dễ nhìn */
  z-index: 2;
  color: #4a4a4a;
}
.overlay-text h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: bold; color: #d70018; }
.overlay-text ul { font-size: 12px; list-style-type: disc; padding-left: 15px; }

.thumbnails {
  display: flex;
  gap: 10px;
  overflow-x: auto;
}
.thumb-item {
  width: 60px;
  height: 60px;
  border: 1px solid #ddd;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 10px;
  color: #999;
}
.thumb-item:hover { border-color: #d70018; }

.policy-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 20px;
}
.policy-item {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 5px;
  padding: 10px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
.icon-check { color: #d70018; font-weight: bold; }
.policy-item p { margin: 0; font-size: 13px; line-height: 1.4; }

.section-box {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}
.section-box h3 { margin-top: 0; font-size: 18px; }

.specs-table {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.spec-row {
  display: flex;
  padding: 10px 15px;
}
.spec-row:nth-child(even) { background-color: #f9fafb; }
.spec-label { width: 35%; color: #666; }
.spec-value { width: 65%; font-weight: 500; }

.btn-outline {
  width: 100%;
  padding: 10px;
  margin-top: 10px;
  border: 1px solid #ccc;
  background: #fff;
  border-radius: 5px;
  cursor: pointer;
}
.btn-outline:hover { background: #f5f5f5; }

.faq-item {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  background: #f9fafb;
  border: 1px solid #eee;
  border-radius: 5px;
  margin-bottom: 5px;
  cursor: pointer;
}

/* --- RIGHT COLUMN STYLES --- */

.price-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}
.price-tab {
  flex: 1;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 10px;
  text-align: center;
  cursor: pointer;
  background: #fff;
}
.price-tab.active {
  border-color: #d70018;
  background-color: #fff2f2;
  color: #d70018;
}
.tab-label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; }
.price-main { display: block; font-size: 18px; font-weight: bold; }
.price-old { font-size: 12px; color: #999; text-decoration: line-through; }
.price-note { font-size: 11px; color: #d70018; }

.option-group { margin-bottom: 15px; }
.option-group label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 13px; }

.option-list { display: flex; gap: 10px; }
.btn-option {
  padding: 8px 15px;
  border: 1px solid #ddd;
  background: #fff;
  border-radius: 5px;
  cursor: pointer;
  font-size: 13px;
}
.btn-option.active {
  border-color: #d70018;
  background: #fff2f2;
  color: #d70018;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.btn-color {
  border: 1px solid #ddd;
  background: #fff;
  border-radius: 5px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  cursor: pointer;
}
.btn-color.active { border-color: #d70018; box-shadow: 0 0 0 1px #d70018; }
.color-circle { width: 25px; height: 25px; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
.color-name { font-size: 11px; font-weight: bold; }
.color-price { font-size: 11px; color: #666; }

.promo-box {
  border: 1px solid #d70018;
  border-radius: 8px;
  margin-bottom: 15px;
  overflow: hidden;
  background: #fff;
}
.promo-header {
  background: #fee2e2;
  color: #d70018;
  padding: 8px 10px;
  font-weight: bold;
  font-size: 13px;
}
.promo-content { padding: 10px; }
.promo-item { display: flex; gap: 8px; margin-bottom: 8px; font-size: 13px; }
.promo-badge {
  background: #2563eb;
  color: white;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  flex-shrink: 0;
  margin-top: 2px;
}

.action-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}
.btn-buy-now {
  flex: 4;
  background: linear-gradient(to right, #d70018, #ff4d4d);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px;
  cursor: pointer;
  text-transform: uppercase;
  box-shadow: 0 4px 10px rgba(215, 0, 24, 0.3);
}
.btn-buy-now strong { font-size: 18px; display: block; margin-bottom: 2px; }
.btn-buy-now span { font-size: 10px; text-transform: none; }

.btn-cart {
  flex: 1;
  border: 2px solid #d70018;
  background: #fff;
  color: #d70018;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.btn-cart .icon-cart { font-size: 20px; line-height: 1; }
.btn-cart span:last-child { font-size: 9px; font-weight: bold; }

.installment-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}
.btn-blue {
  flex: 1;
  background: #288ad6;
  color: white;
  border: none;
  border-radius: 5px;
  padding: 8px;
  font-size: 12px;
  cursor: pointer;
}
.btn-blue strong { display: block; }
.btn-blue:hover { background: #1e74b9; }

.extra-offers {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 10px;
  background: #fff;
}
.offer-title { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 5px; font-size: 13px; }
.offer-item { display: flex; gap: 5px; font-size: 13px; margin-bottom: 5px; }
.check-green { color: #16a34a; font-weight: bold; }
</style>