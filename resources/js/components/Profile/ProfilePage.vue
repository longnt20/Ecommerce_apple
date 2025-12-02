<template>
  <div class="profile-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <img class="avatar" src="https://i.pravatar.cc/100" alt="avatar">
        <div>
          <h3 class="user-name">{{ user.name }}</h3>
          <p class="user-email">{{ user.email }}</p>
        </div>
      </div>

      <ul class="sidebar-menu">
        <li :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">Thông tin tài khoản</li>
        <li :class="{ active: activeTab === 'orders' }" @click="activeTab = 'orders'">Đơn mua</li>
        <li :class="{ active: activeTab === 'address' }" @click="activeTab = 'address'">Sổ địa chỉ</li>
        <li :class="{ active: activeTab === 'password' }" @click="activeTab = 'password'">Đổi mật khẩu</li>
      </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">

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
        <h2>Lịch sử đơn hàng</h2>

        <div v-if="orders.length === 0" class="empty-state">
          <img src="https://cdn-icons-png.flaticon.com/512/4076/4076505.png">
          <p>Bạn chưa có đơn hàng nào.</p>
        </div>

        <div v-for="order in orders" :key="order.id" class="order-item">
          <div>
            <strong>Mã đơn:</strong> {{ order.code }}  
          </div>
          <div><strong>Ngày đặt:</strong> {{ order.date }}</div>
          <div><strong>Tổng:</strong> {{ format(order.total) }}đ</div>
          <span class="status" :class="order.status">{{ order.statusText }}</span>
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
import { ref } from 'vue';

const activeTab = ref('info');

const user = ref({
  name: 'Thành Long Nguyễn',
  email: 'longnt2k4@gmail.com',
  phone: '0987654321'
});

const orders = ref([
  { id: 1, code: 'DH001', date: '12/10/2025', total: 24690000, status: 'success', statusText: 'Hoàn thành' },
  { id: 2, code: 'DH002', date: '20/10/2025', total: 16900000, status: 'shipping', statusText: 'Đang giao' }
]);

const addresses = ref([
  { id: 1, name: 'Nguyễn Thành Long', phone: '0987...', full: '123 Nguyễn Huệ, Q1, TP.HCM', default: true },
  { id: 2, name: 'Long', phone: '0903...', full: 'Phường 5, Gò Vấp, TPHCM', default: false },
]);

const password = ref({
  old: '',
  new: '',
  confirm: ''
});

const saveInfo = () => alert("Lưu thông tin thành công!");
const changePassword = () => alert("Đổi mật khẩu thành công!");
const addAddress = () => alert("Chức năng thêm địa chỉ!");

const format = (number) =>
  number.toLocaleString("vi-VN");
</script>

<style scoped>
.profile-container {
  display: flex;
  gap: 25px;
  padding: 25px;
  max-width: 1100px;
  margin: auto;
}

/* SIDEBAR */
.sidebar {
  width: 260px;
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 3px 12px rgba(0,0,0,0.08);
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
  padding: 25px;
  border-radius: 14px;
  box-shadow: 0 3px 12px rgba(0,0,0,0.08);
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
</style>
