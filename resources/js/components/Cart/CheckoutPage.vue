<template>
  <div class="checkout-container">

    <!-- ======== LEFT ======== -->
    <div class="checkout-left">

      <!-- Địa chỉ -->
      <section class="card shipping-card">
        <h3>Địa chỉ giao hàng</h3>
        <!-- Họ tên -->
        <div class="row">
          <div class="form-group col-6">
            <label>Họ và tên</label>
            <input v-model="shipping.name" type="text" placeholder="Vui lòng nhập họ tên" name="fullname" />
          </div>

          <!-- Số điện thoại -->
          <div class="form-group col-6">
            <label>Số điện thoại</label>
            <input v-model="shipping.phone" type="text" placeholder="Nhập số điện thoại người nhận hàng" name="phone" />
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label>Email</label>
          <input v-model="shipping.email" type="email" placeholder="Nhập email của bạn" name="email" />
        </div>

        <div class="row">
          <!-- Tỉnh / Thành phố -->
          <div class="form-group col-6">
            <label>Tỉnh / Thành phố</label>
            <select v-model="shipping.province_id" @change="fetchDistricts" name="province">
              <option value="">-- Chọn tỉnh/thành --</option>
              <option v-for="p in provinces" :key="p.code" :value="p.code">{{ p.name }}</option>
            </select>
          </div>

          <!-- Quận / Huyện -->
          <div class="form-group col-6">
            <label>Quận / Huyện</label>
            <select v-model="shipping.district_id" @change="fetchWards" :disabled="districts.length === 0" name="district">
              <option value="">-- Chọn quận/huyện --</option>
              <option v-for="d in districts" :key="d.code" :value="d.code">{{ d.name }}</option>
            </select>
          </div>
        </div>

        <div class="row">
          <!-- Phường / Xã -->
          <div class="form-group col-6">
            <label>Phường / Xã</label>
            <select v-model="shipping.ward_id" :disabled="wards.length === 0" name="ward">
              <option value="">-- Chọn phường/xã --</option>
              <option v-for="w in wards" :key="w.code" :value="w.code">{{ w.name }}</option>
            </select>
          </div>

          <!-- Địa chỉ -->
          <div class="form-group col-6">
            <label>Địa chỉ cụ thể</label>
            <input v-model="shipping.address" type="text" placeholder="Số nhà, đường..." name="address" />
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
        <div v-for="item in cart.items" :key="item.id" class="product-row">
          <img :src="item.variant.thumbnail_url ?? item.variant.thumbnail" class="thumb" />

          <div class="product">
            <div class="info">
              <h5>{{ item.product.name }}</h5>
              Màu: <strong>{{ item.variant?.color_label ?? item.variant.color }}</strong>
              <p>Dung lượng: <strong>{{ item.variant?.storage }}</strong></p>
            </div>
            <div class="quantity">
              <p>{{ item.quantity }}</p>
            </div>
            <div class="price">
              <p>{{ format(item.quantity * item.price_at_add) }}đ</p>
            </div>
          </div>
        </div>
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
import { useBuyNowStore } from "../../effects/buynow";


// Cart
// const cart = ref({ items: [] });
// const mode = ref("cart");
const buyNow = useBuyNowStore();
const cart = ref({ items: [] });
onMounted(async () => {
  const mode = new URLSearchParams(window.location.search).get("mode");

  // BUY NOW MODE
  if (mode === "buy-now" && buyNow.active) {
    cart.value.items = [
      {
        id: 0,
        product: buyNow.product,
        variant: buyNow.variant,
        quantity: buyNow.quantity,
        price_at_add: Number(buyNow.variant.final_price.toString().replace(/\./g, ""))
      }
    ];

    console.log(">>> BUY NOW DATA:", cart.value.items);
    return;
  }

  // NORMAL CART MODE
  const res = await axios.get("http://127.0.0.1:8000/api/cart", {
    headers: {
      Authorization: `Bearer ${localStorage.getItem("token")}`,
    },
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
    shipping.value.ward_name = wards.value.find(w => w.code == shipping.value.ward_id)?.name;
    shipping.value.district_name = districts.value.find(d => d.code == shipping.value.district_id)?.name;
    shipping.value.province_name = provinces.value.find(p => p.code == shipping.value.province_id)?.name;
    const payload = {
      payment_method: paymentMethod.value,
      items: cart.value.items.map(item => ({
        product_id: item.product.id,
        variant_id: item.variant.id,
        quantity: item.quantity,
        price: item.price_at_add
      })),
      fullname: shipping.value.name,
      phone: shipping.value.phone,
      email: shipping.value.email,
      address: shipping.value.address,
      ward: shipping.value.ward_name,
      district: shipping.value.district_name,
      province: shipping.value.province_name,
      note: shipping.value.note ?? null,
    };

    const res = await axios.post(
      "http://127.0.0.1:8000/api/checkout",
      payload,
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    if (paymentMethod.value === "vnpay") {
      window.location.href = res.data.payment_url;
    } else {
      window.location.href = `/payment-success?order_id=${res.data.order_id}&amount=${res.data.final_amount}&method=COD`;
    }

  } catch (err) {
    console.error("CHECKOUT ERROR:", err);

    if (err.response) {
      console.error("SERVER RESPONSE:", err.response.data);
      alert("SERVER ERROR: " + JSON.stringify(err.response.data));
    } else {
      alert("NETWORK ERROR: " + err.message);
    }
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
  flex: 50%;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.checkout-right {
  flex: 50%;
}

.product {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
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
  width: 100%;
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

.product-row .info {
  padding-top: 15px;
  font-size: 14px;
}

.payment-option {
  display: block;
  padding: 10px 0;
  cursor: pointer;
}

.summary {
  position: sticky;
  top: 20px;
  justify-content: space-between;
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

.quantity {
  width: 30px;
  /* chiều rộng ô */
  height: 30px;
  /* chiều cao ô → vuông */
  border: 1px solid #ccc;
  /* viền */
  border-radius: 6px;
  /* bo góc (tùy thích) */
  display: flex;
  align-items: center;
  justify-content: center;
  /* căn giữa nội dung */
  background: #f8f8f8;
  /* màu nền nhẹ */
  margin-left: 10px;
  /* khoảng cách với bên cạnh */
}

.quantity p {
  margin: 0;
  padding: 0;
  font-weight: bold;
  font-size: 14px;
}
</style>
