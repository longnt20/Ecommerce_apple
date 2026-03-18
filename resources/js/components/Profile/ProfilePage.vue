<template>
  <div class="profile-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header" v-if="profile">
        <img class="avatar" :src="profile.user.avatar" alt="avatar">
        <div>
          <h3 class="user-name">{{ profile.user.name }}</h3>
          <p class="user-email">{{ profile.user.email }}</p>
        </div>

      </div>

      <ul class="sidebar-menu">
        <li :class="{ active: activeTab === 'dashboard' }" @click="activeTab = 'dashboard'">Tổng quan</li>
        <li :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">Thông tin tài khoản</li>
        <li :class="{ active: activeTab === 'orders' }" @click="activeTab = 'orders'">Lịch sử mua hàng</li>
        <li :class="{ active: activeTab === 'address' }" @click="activeTab = 'address'">Sổ địa chỉ</li>
        <li :class="{ active: activeTab === 'password' }" @click="activeTab = 'password'">Đổi mật khẩu</li>
      </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">
      <div v-if="activeTab === 'dashboard'" class="dashboard">
        <div class="d-flex gap-2" style="min-height: 300px;">
          <div class="card col-sm-8">
            <h5 class="card-title">Đơn hàng gần đây</h5>

            <div v-if="profile?.orders?.data?.length" class="order-list">
              <div class="order-item" v-for="order in recentOrders" :key="order.id">
                <div class="order-header">
                  <span class="order-code">#{{ order.code }}</span>
                  <span class="order-status" :class="order.status">
                    {{ order.status_label }}
                  </span>
                </div>

                <div class="order-products">
                  <div class="product" v-for="item in order.items" :key="item.id">
                    <div class="product-info">
                      <div class="d-flex">
                        <img :src="item.variant.thumbnail_url" alt="" srcset="" width="50px">
                        <div class="">
                          <strong>{{ item.product_name }}</strong><br>
                          <span class="variant">{{ item.variant_name }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="product-price">
                      x{{ item.quantity }} · {{ formatPrice(item.total) }}
                    </div>
                  </div>
                </div>

                <div class="order-footer">
                  <span class="order-date">
                    {{ new Date(order.created_at).toLocaleDateString('vi-VN') }}
                  </span>
                  <span class="order-total">
                    Tổng: {{ formatPrice(order.final_amount) }}
                  </span>
                </div>
              </div>
            </div>

            <div v-else class="empty">
              <p>Bạn chưa có đơn hàng nào</p>
            </div>
          </div>

          <div class="card col-sm-4">
            <h5 class="card-title">Ưu đãi của bạn</h5>

            <div v-if="profile?.vouchers?.length" class="voucher-list">
              <div class="voucher-item" v-for="voucher in profile.vouchers" :key="voucher.id">
                <div class="voucher-value">
                  <strong v-if="voucher.discount_type === 'percentage'">
                    -{{ voucher.discount_value }}%
                  </strong>
                  <strong v-else>
                    -{{ formatPrice(voucher.discount_value) }}
                  </strong>
                </div>

                <div class="voucher-info">
                  <div class="voucher-name">{{ voucher.name }}</div>
                  <div class="voucher-code">Mã: {{ voucher.code }}</div>
                  <div class="voucher-expire">
                    HSD: {{ new Date(voucher.expire_date).toLocaleDateString('vi-VN') }}
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="empty">
              <p>Bạn chưa có ưu đãi nào</p>
            </div>
          </div>

        </div>
        <div class="card mt-2 wishlist-card">
          <h5>Sản phẩm yêu thích</h5>

          <!-- Không có sản phẩm -->
          <div v-if="wishlist.items.length === 0" class="empty">
            <img :src="Nofavouriteimage" alt="" srcset="" width="200px" style="display: block;
  margin: 0 auto;backdrop-filter: blur(10px);
  background: rgba(255,255,255,0.4);border-radius: 50%;">
            <p style="text-align: center;">Bạn chưa có sản phẩm yêu thích nào 💔</p>
          </div>

          <!-- Danh sách wishlist -->
          <div v-else class="wishlist-list">
            <RouterLink v-for="item in wishlist.items" :key="item.id" class="wishlist-item"
              :to="{ name: 'product-detail', params: { slug: item.product?.slug } }">
              <img :src="item.variant.thumbnail_url" :alt="item.product.name_product" />

              <div class="info">
                <p class="name">{{ item.product.name_product }}</p>
                <div class="branch-26">
                  <p class="price">{{ formatPrice(item.variant.price) }}</p>
                  <p class="cost-price">{{ formatPrice(item.variant.cost_price) }}</p>
                </div>
              </div>
              <button data-slot="button" class="cpsui-button active" @click.stop.prevent="removeWishlist(item)">
                <svg stroke="currentColor" fill="currentColor" stroke-width="2" viewBox="0 0 24 24"
                  stroke-linecap="round" stroke-linejoin="round" class="favourite" height="1.8em" width="1.8em">
                  <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                  </path>
                </svg>
              </button>
            </RouterLink>
          </div>
        </div>

      </div>
      <!-- TAB: INFO -->
      <div v-if="activeTab === 'info'" class="card">
        <h2>Thông tin tài khoản</h2>

        <div class="form-group">
          <label>Họ và tên</label>
          <input type="text" v-model="user.name">
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" v-model="user.email" disabled>
        </div>

        <div class="form-group">
          <label>Số điện thoại</label>
          <input type="text" v-model="user.phone">
        </div>

        <button class="btn" @click="saveInfo">Lưu thay đổi</button>
      </div>

      <!-- TAB: ORDERS -->
      <div v-if="activeTab === 'orders'" class="card">
        <div class="tabs">
          <span v-for="tab in tabs" :key="tab.value" class="tab" :class="{ active: tab.value === activeTabOrder }"
            @click="activeTabOrder = tab.value">
            {{ tab.label }}
          </span>
        </div>

        <!-- Filter -->
        <div class="filter">
          <span class="title">Lịch sử mua hàng</span>
          <div class="date-range">
            <input type="date" v-model="fromDate" />
            <span>→</span>
            <input type="date" v-model="toDate" />
          </div>

        </div>

        <!-- Empty state -->
        <div v-if="orderList.length === 0" class="empty">
          <img :src="Nofavouriteimage" alt="" srcset="" width="200px" style="display: block;
  margin: 0 auto;backdrop-filter: blur(10px);
  background: rgba(255,255,255,0.4);border-radius: 50%;">
          <p>
            Bạn chưa có đơn hàng nào
            <a href="#">Trang chủ</a>
          </p>
        </div>
        <div v-else class="order-list">

          <div v-for="order in orderList" :key="order.id" class="order-card">
            <div class="order-header">
              <div>
                <strong>Mã đơn:</strong> {{ order.code }}
                <span class="date">• {{ order.created_at }}</span>
              </div>

              <span class="status" :class="order.status">
                {{ order.status_label }}
              </span>
            </div>

            <div class="order-body">
              <div class="order-products">
                <div class="order-info" v-for="item in order.items" :key="item.id">
                <div class="d-flex justify-content-beetween">
                  <img :src="item.variant?.thumbnail_url" alt="" srcset="" width="50px">
                  <div class="">
                    <strong>{{ item.product_name }}</strong><br>
                    <span class="variant">{{ item.variant_name }}</span>
                  </div>
                  <div class="product-price">
                      x{{ item.quantity }} · {{ formatPrice(item.total) }}
                    </div>
                </div>
                
              </div>
              
              </div>
              

              <div class="summary">
                <div>Tổng tiền: <strong>{{ formatPrice(order.total_price) }}</strong></div>
                <button class="btn-detail" @click="openOrderModal(order)">
                  Xem chi tiết
                </button>
              </div>
            </div>

          </div>


        </div>
                  <div v-if="showOrderModal" class="modal-overlay" @click.self="closeOrderModal">
            <div class="modal">

              <div class="modal-header">
                <h3>Chi tiết đơn hàng</h3>
                <button class="close" @click="closeOrderModal">×</button>
              </div>

              <div class="modal-body">
                <p><strong>Mã đơn:</strong> {{ selectedOrder.code }}</p>
                <p><strong>Trạng thái:</strong> {{ selectedOrder.status_label }}</p>

                <div class="modal-items">
                  <div v-for="(item, i) in selectedOrder.items" :key="i" class="modal-item">
                    <img :src="item.thumbnail_url" />
                    <div>
                      <div>Số lượng: {{ item.quantity }}</div>
                      <div>Giá: {{ formatPrice(item.price) }}</div>
                    </div>
                  </div>
                </div>

                <div class="total">
                  Tổng tiền: <strong>{{ formatPrice(selectedOrder.total_price) }}</strong>
                </div>
              </div>

            </div>
          </div>
      </div>

      <!-- TAB: ADDRESS -->
      <div v-if="activeTab === 'address'" class="card">
        <h2>Sổ địa chỉ</h2>

        <div v-for="address in addresses" :key="address.id" class="address-item">
          <p>{{ address.name }} — {{ address.phone }}</p>
          <p>{{ address.full }}</p>
          <span v-if="address.default" class="default-tag">Mặc định</span>
        </div>

        <button class="btn-outline" @click="addAddress">+ Thêm địa chỉ</button>
      </div>

      <!-- TAB: PASSWORD -->
      <div v-if="activeTab === 'password'" class="card">
        <h2>Đổi mật khẩu</h2>

        <div class="form-group">
          <label>Mật khẩu hiện tại</label>
          <input type="password" v-model="password.old">
        </div>

        <div class="form-group">
          <label>Mật khẩu mới</label>
          <input type="password" v-model="password.new">
        </div>

        <div class="form-group">
          <label>Nhập lại mật khẩu mới</label>
          <input type="password" v-model="password.confirm">
        </div>

        <button class="btn" @click="changePassword">Đổi mật khẩu</button>
      </div>

    </main>

  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useWishlistStore } from '../../effects/wishlist';
import { useToast } from 'vue-toastification';
import Nofavouriteimage from '../../../images/noimage.png';
import axios from 'axios';
const activeTab = ref('dashboard');
const activeTabOrder = ref(null);
const fromDate = ref('')
const toDate = ref('')
const tabs = [
  { label: "Tất cả", value: null },
  { label: "Chờ xác nhận", value: "pending" },
  { label: "Đã xác nhận", value: "confirmed" },
  { label: "Đang vận chuyển", value: "shipping" },
  { label: "Đã giao hàng", value: "delivered" },
  { label: "Đã huỷ", value: "cancelled" },
];
const showOrderModal = ref(false)
const selectedOrder = ref(null)

const openOrderModal = (order) => {
  selectedOrder.value = order
  showOrderModal.value = true
};
const closeOrderModal = () => {
  showOrderModal.value = false
  selectedOrder.value = null
}

const profile = ref(null);

const fetchProfile = async () => {
  const res = await axios.get("http://127.0.0.1:8000/api/user", {
    params: {
      status: activeTabOrder.value,
      from_date: fromDate.value,
      to_date: toDate.value,
    }
  });

  profile.value = res.data;
};
const recentOrders = computed(() => {
  return profile.value?.orders?.data?.slice(0, 3) || []
});
const orderList = computed(() => {
  return profile.value?.orders?.data || []
});
const password = ref({
  old: '',
  new: '',
  confirm: ''
});
const wishlist = useWishlistStore()
const toast = useToast()

const removeWishlist = async (item) => {
  await wishlist.removeById(item.id)
  toast.info('Đã xóa khỏi danh sách yêu thích')
}
onMounted(() => {
  fetchProfile()
  if (!wishlist.items.length) {
    wishlist.fetchWishlist()
  }
})
watch([activeTabOrder, fromDate, toDate], () => {
  fetchProfile()
})
const formatPrice = (value) => {
  if (value === null || value === undefined) return '0đ'
  return Number(value).toLocaleString('vi-VN') + 'đ'
}
const saveInfo = () => alert("Lưu thông tin thành công!");
const changePassword = () => alert("Đổi mật khẩu thành công!");
const addAddress = () => alert("Chức năng thêm địa chỉ!");

const format = (number) =>
  number.toLocaleString("vi-VN");
</script>

<style scoped>
.profile-container {
  display: flex;
  gap: 20px;
  padding: 25px;
  max-width: 1200px;
  margin: auto;
}

/* SIDEBAR */
.sidebar {
  width: 280px;
  background: #fff;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.avatar {
  width: 55px;
  height: 55px;
  border-radius: 50%;
}

.user-name {
  font-size: 17px;
  font-weight: bold;
}

.user-email {
  font-size: 13px;
  color: #777;
}

/* MENU */
.sidebar-menu {
  list-style: none;
  padding: 0;
}

.sidebar-menu li {
  padding: 12px;
  cursor: pointer;
  border-radius: 8px;
}

.sidebar-menu li:hover {
  background: #f5f5f5;
}

.sidebar-menu .active {
  background: #d70018;
  color: #fff;
}

/* CONTENT */
.content {
  flex: 1;
}

.card {
  background: #fff;
  padding: 20px;
  border-radius: 14px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
}

/* FORM */
.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
}

/* ORDER LIST */
.order-item {
  padding: 15px;
  border-bottom: 1px solid #eee;
  margin-bottom: 12px;
}

.status {
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 13px;
}

.status.success {
  background: #e1f8e9;
  color: #1b9b4a;
}

.status.shipping {
  background: #fff4d1;
  color: #d88d00;
}

/* ADDRESS */
.address-item {
  padding: 12px 0;
  border-bottom: 1px solid #eee;
}

.default-tag {
  display: inline-block;
  margin-top: 5px;
  background: #d70018;
  color: white;
  padding: 4px 10px;
  font-size: 12px;
  border-radius: 6px;
}

/* BUTTONS */
.btn {
  background: #d70018;
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

.btn-outline {
  border: 1px solid #d70018;
  color: #d70018;
  background: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
}

.empty-state {
  text-align: center;
}

.empty-state img {
  width: 110px;
  margin-bottom: 10px;
}


/* RESPONSIVE */
@media (max-width: 900px) {
  .profile-container {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }
}

.wishlist-card {
  padding: 12px;
  display: flex;
}

.wishlist-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.wishlist-item {
  display: flex;
  align-items: center;
  text-decoration: none;
  color: inherit;
  padding: 5px;
  border-radius: 6px;
  transition: 0.2s;
  border: 1px solid rgb(228, 228, 228);
}

.wishlist-item img {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 6px;
}

.info {
  flex: 1;
}

.name {
  font-size: 14px;
  font-weight: 500;
  margin: 0;
}


.remove {
  background: none;
  border: none;
  cursor: pointer;
}

.branch-26 {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  /* 6px */
}

.branch-26 .price {
  font-size: 13px;
  font-weight: 700;
  color: #e91030;
  /* primary-600 */
  text-decoration: none;
}

/* Responsive màn ≥ 640px */
@media (min-width: 640px) {
  .price {
    font-size: 13px;
    /* text-medium */
  }
}

.branch-26 .cost-price {
  font-size: 13px;
  color: #D1D5DB;
  text-decoration: line-through;
}

/* Responsive màn ≥ 640px */
@media (min-width: 640px) {
  .cost_price {
    font-size: 13px;
  }
}

.favourite {
  color: #3B82F6;
  transition: 0.2s;
}

.favourite.active {
  color: #3B82F6;
  /* xanh lá */
  fill: #3B82F6;
}

.cpsui-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  /* lấy gap-1 cuối cùng */
  cursor: pointer;
  border: 1px solid #ffffff;
  /* cpsui:border-pure-white */
  font-size: 0.75rem;
  /* cpsui:text-small */
  padding: 0.5rem 0.25rem;
  /* py-2x-small px-1x-small */
  min-height: 24px;
  /* cpsui:min-h-[24px] */
  border-radius: 0.125rem;
  /* cpsui:rounded-small */
  color: #3B82F6;
  /* cpsui:text-info-500 */
  background-color: #ffffff;
  /* cpsui:bg-pure-white */
  margin-left: auto;
  height: 2rem;
  /* h-8 */
}

/* Hover button */
.cpsui-button:hover {
  border-color: #F9FAFB;
  /* hover:border-neutral-50 */
  background-color: #F9FAFB;
  /* hover:bg-neutral-50 */
}

/* Hover SVG animation */
.cpsui-button:hover svg {
  animation: heartbeat 0.6s infinite;
}

/* Keyframes heartbeat */
@keyframes heartbeat {

  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.2);
  }
}

/* Disabled button */
.cpsui-button:disabled {
  cursor: not-allowed;
  border-color: #ffffff;
  color: #D1D5DB;
  /* cpsui:disabled:text-black-300 */
  background-color: #ffffff;
}

/* Responsive span */
.cpsui-button span {
  display: none;
}

@media (min-width: 640px) {
  .cpsui-button span {
    display: inline-block;
  }
}

.cpsui-button {
  background: transparent;
  border: none;
  cursor: pointer;
}

.cpsui-button .favourite {
  color: #999;
  transition: all 0.2s ease;
}

.cpsui-button.active .favourite {
  color: #3B82F6;
  /* xanh lá */
}

/* Tabs */
.tabs {
  display: flex;
  gap: 28px;
  border-bottom: 1px solid #eee;
}

.tab {
  padding-bottom: 12px;
  cursor: pointer;
  color: #777;
}

.tab.active {
  color: #d70018;
  border-bottom: 2px solid #d70018;
  font-weight: 600;
}

/* Filter */
.filter {
  margin-top: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.title {
  font-size: 18px;
  font-weight: 600;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.date-range input {
  border: none;
  outline: none;
}

/* Empty state */
.empty {
  margin-top: 80px;
  text-align: center;
  color: #888;
}

.empty img {
  width: 140px;
  margin-bottom: 16px;
}

.empty a {
  color: #d70018;
  text-decoration: none;
  margin-left: 6px;
}

.card {
  background: #fff;
  border-radius: 10px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.card-title {
  font-weight: 600;
  margin-bottom: 16px;
}

/* ===== ORDERS ===== */
.order-list {
  display: flex;
  flex-direction: column;
}

.order-item {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px;
}

.order-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.order-code {
  font-weight: 600;
}

.order-status {
  font-size: 13px;
  padding: 4px 10px;
  border-radius: 20px;
  text-transform: capitalize;
}

.order-status.pending {
  background: #fff3cd;
  color: #856404;
}

.order-status.confirmed {
  background: #e7f1ff;
  color: #004085;
}

.order-status.shipping {
  background: #e6fffa;
  color: #0c5460;
}

.order-status.completed {
  background: #d4edda;
  color: #155724;
}

.order-status.cancelled {
  background: #f8d7da;
  color: #721c24;
}

.order-products {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.product {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.variant {
  color: #777;
  font-size: 13px;
}

.order-footer {
  margin-top: 8px;
  display: flex;
  justify-content: space-between;
  font-weight: 500;
}

/* ===== VOUCHERS ===== */
.voucher-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.voucher-item {
  display: flex;
  gap: 12px;
  border: 1px dashed #d70018;
  padding: 12px;
  border-radius: 8px;
}

.voucher-value {
  min-width: 70px;
  background: #d70018;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.voucher-name {
  font-weight: 600;
}

.voucher-code,
.voucher-expire {
  font-size: 13px;
  color: #555;
}

/* ===== EMPTY ===== */
.empty {
  text-align: center;
  color: #888;
  padding: 40px 0;
}

.order-card {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 16px;
}

.order-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.order-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.images img {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 6px;
  margin-right: 6px;
}

.btn-detail {
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #5381dd;
  cursor: pointer;
  color: white;
  background-color: #3B82F6;
  margin-left: 60px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, .4);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal {
  width: 520px;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
}
</style>
