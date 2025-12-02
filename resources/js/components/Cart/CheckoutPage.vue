<template>
  <div class="checkout-container">

    <!-- ======== LEFT ======== -->
    <div class="checkout-left">

      <!-- Địa chỉ -->
      <section class="card shipping-card">
  <h3>Địa chỉ giao hàng</h3>

  <!-- Họ tên -->
  <div class="form-group">
    <label>Họ và tên</label>
    <input v-model="shipping.name" type="text" placeholder="Nguyễn Văn A" />
  </div>

  <!-- Số điện thoại -->
  <div class="form-group">
    <label>Số điện thoại</label>
    <input v-model="shipping.phone" type="text" placeholder="0123 456 789" />
  </div>

  <!-- Email -->
  <div class="form-group">
    <label>Email</label>
    <input v-model="shipping.email" type="email" placeholder="email@gmail.com" />
  </div>

  <!-- Tỉnh / Thành phố -->
  <div class="form-group">
    <label>Tỉnh / Thành phố</label>
    <select v-model="shipping.province_id" @change="fetchDistricts">
      <option value="">-- Chọn tỉnh/thành --</option>
      <option v-for="p in provinces" :key="p.code" :value="p.code">{{ p.name }}</option>
    </select>
  </div>

  <!-- Quận / Huyện -->
  <div class="form-group">
    <label>Quận / Huyện</label>
    <select v-model="shipping.district_id" @change="fetchWards" :disabled="districts.length === 0">
      <option value="">-- Chọn quận/huyện --</option>
      <option v-for="d in districts" :key="d.code" :value="d.code">{{ d.name }}</option>
    </select>
  </div>

  <!-- Phường / Xã -->
  <div class="form-group">
    <label>Phường / Xã</label>
    <select v-model="shipping.ward_id" :disabled="wards.length === 0">
      <option value="">-- Chọn phường/xã --</option>
      <option v-for="w in wards" :key="w.code" :value="w.code">{{ w.name }}</option>
    </select>
  </div>

  <!-- Địa chỉ -->
  <div class="form-group">
    <label>Địa chỉ cụ thể</label>
    <input v-model="shipping.address" type="text" placeholder="Số nhà, đường..." />
  </div>
</section>


      <!-- Sản phẩm -->
      <section class="card">
        <h3>Sản phẩm</h3>

        <div v-for="item in cart.items" :key="item.id" class="product-row">
          <img :src="item.variant.thumbnail_url" class="thumb" />

          <div class="info">
            <h4>{{ item.product.name }}</h4>
            <p>Màu: <strong>{{ item.variant?.color_label }}</strong> | Dung lượng: <strong>{{ item.variant?.storage }}</strong></p>
            <p>Số lượng: x{{ item.quantity }}</p>
          </div>

          <div class="price">
            {{ format(item.quantity * item.price_at_add) }}đ
          </div>
        </div>
      </section>

      <!-- Payment -->
      <section class="card">
        <h3>Phương thức thanh toán</h3>

        <label class="payment-option">
          <input type="radio" value="cod" v-model="paymentMethod" />
          Thanh toán khi nhận hàng (COD)
        </label>

        <label class="payment-option">
          <input type="radio" value="vnpay" v-model="paymentMethod" />
          Thanh toán qua VNPay
        </label>
      </section>
    </div>

    <!-- ======== RIGHT ======== -->
    <div class="checkout-right">
      <section class="card summary">
        <h3>Đơn hàng</h3>

        <div class="summary-row">
          <span>Tạm tính</span>
          <strong>{{ format(subtotal) }}đ</strong>
        </div>

        <div class="summary-row">
          <span>Phí vận chuyển</span>
          <strong>0đ</strong>
        </div>

        <div class="summary-total">
          <span>Tổng thanh toán</span>
          <strong>{{ format(subtotal) }}đ</strong>
        </div>

        <button class="btn-checkout" @click="submitCheckout" :disabled="loading">
          {{ loading ? 'Đang xử lý...' : 'Đặt hàng' }}
        </button>
      </section>
    </div>

  </div>
</template>


<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

// Cart
const cart = ref({ items: [] });

onMounted(async () => {
  const res = await axios.get("http://127.0.0.1:8000/api/cart", {
    headers: {
      Authorization: `Bearer ${localStorage.getItem('token')}`
    }
  });
  cart.value = res.data;
});

// Dữ liệu form
const shipping = ref({
  name: "",
  phone: "",
  email: "",
  province_id: "",
  district_id: "",
  ward_id: "",
  address: ""
});

// Danh sách
const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);

// Gọi API tỉnh/thành khi load trang
onMounted(async () => {
  const res = await axios.get("http://127.0.0.1:8000/api/provinces");
  provinces.value = res.data;
});

// Khi chọn tỉnh → load quận
async function fetchDistricts() {
  shipping.value.district_id = "";
  shipping.value.ward_id = "";
  wards.value = [];
  const provinceId = shipping.value.province_id; // ✔ Lấy đúng ID

  if (!provinceId) return;
  const res = await axios.get(`http://127.0.0.1:8000/api/districts/${provinceId}?depth=2`);
  districts.value = res.data.districts;
}

// Khi chọn quận → load phường
async function fetchWards() {
  shipping.value.ward_id = "";
  const districtId = shipping.value.district_id; // ✔ Lấy đúng ID

  if (!districtId) return;
  const res = await axios.get(`http://127.0.0.1:8000/api/wards/${districtId}?depth=2`);
  wards.value = res.data.wards;
}

// Payment method
const paymentMethod = ref("cod");

const loading = ref(false);

// Tính tổng
const subtotal = computed(() => {
  return cart.value.items.reduce((sum, item) => {
    return sum + item.quantity * item.price_at_add;
  }, 0);
});

const format = (n) => n.toLocaleString();

// Xử lý checkout
const submitCheckout = async () => {
  if (!shipping.value.name || !shipping.value.phone || !shipping.value.address) {
    alert("Vui lòng nhập đầy đủ thông tin giao hàng!");
    return;
  }

  loading.value = true;

  try {
    const res = await axios.post(
      "http://127.0.0.1:8000/api/checkout",
      {
        payment_method: paymentMethod.value,
        shipping: shipping.value
      },
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    // Nếu là VNPay → redirect URL
    if (paymentMethod.value === "vnpay") {
      window.location.href = res.data.payment_url;
    } else {
      // COD → chuyển sang trang thành công
      window.location.href = `/payment-success?order_id=${res.data.order_id}&amount=${res.data.final_amount}&method=COD`;
    }

  } catch (err) {
    alert(err.response?.data?.error ?? "Lỗi không xác định");
  }

  loading.value = false;
};
</script>


<style scoped>
.checkout-container {
  max-width: 1200px;
  margin: auto;
  display: flex;
  gap: 20px;
  padding: 20px;
}

.checkout-left {
  flex: 2;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.checkout-right {
  flex: 1;
}

.card {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  border: 1px solid #ececec;
}

.product-row {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding: 15px 0;
}

.thumb {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: 6px;
  margin-right: 15px;
}

.payment-option {
  display: block;
  padding: 10px 0;
  cursor: pointer;
}

.summary {
  position: sticky;
  top: 20px;
}

.summary-row,
.summary-total {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.summary-total {
  border-top: 1px solid #ddd;
  margin-top: 10px;
  padding-top: 10px;
  font-size: 18px;
}

.btn-checkout {
  width: 100%;
  margin-top: 20px;
  padding: 12px;
  background: #ff4d30;
  color: #fff;
  font-weight: bold;
  border-radius: 6px;
  cursor: pointer;
  border: none;
}
.shipping-card {
  padding: 16px;
  border-radius: 6px;
  border: 1px solid #ddd;
  background: #fff;
  margin-bottom: 20px;
}

.shipping-card h3 {
  font-size: 18px;
  margin-bottom: 12px;
}

.form-group {
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 4px;
  font-weight: 600;
  font-size: 14px;
}

.form-group input,
.form-group select {
  padding: 8px 10px;
  font-size: 14px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

</style>
