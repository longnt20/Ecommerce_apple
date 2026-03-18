<template>
  <div class="right-sidebar">
    <!-- Card Chào mừng -->
    <!-- Nếu chưa login -->
<div class="info-card" v-if="!auth.isLoggedIn">
  <div class="welcome-header">
    <img :src="DragonCute" alt="dragon cute" width="60px">
    <p><strong>Chào mừng bạn đến với LongTech</strong></p>
  </div>

    <p class="welcome-text">
    Đăng nhập để không bỏ lỡ những ưu đãi tại LongTech Store.
  </p>

  <div class="auth-links">
    <a href="#" @click="openLogin">Đăng nhập</a> hoặc 
    <a href="#">Đăng ký</a>
  </div>
</div>

<!-- Nếu đã login -->
<div class="info-card" v-else>
  <div class="user-info">
    <div>
      <p><strong>{{ auth.user?.name }}</strong></p>
      <p class="welcome-text">{{ auth.user?.email }}</p>
    </div>
  </div>

  <div class="user-stats">
    <div class="stat-item">
      <div class="stat-icon pink-icon">
        <Gift :size="30" />
      </div>
      <p class="main">Xem ưu đãi của bạn</p>
      <span class="arrow"><ChevronRight /></span>
    </div>
    <div class="stat-item">
      <div class="stat-icon pink-icon">
        <ShoppingBag :size="30" />
      </div>
      <p class="main">Đơn hàng đã mua</p>
      <span class="arrow"><ChevronRight /></span>
    </div>
  </div>

</div>



    <!-- Card Ưu đãi -->
    <div class="info-card-bottom">
      <div class="card-title">Ưu đãi cho giáo dục</div>
      <ul>
        <li v-for="item in eduPromos" :key="item.text">
          <a :href="item.href">
            <GraduationCap :size="18" />
            <span>{{ item.text }}</span>
          </a>
        </li>
      </ul>
      <div class="card-title" style="margin-top: 20px;">Thu cũ lên đời giá hời</div>
       <ul>
        <li v-for="item in tradeInPromos" :key="item.text">
          <a :href="item.href">
            <Repeat :size="18" />
            <span>{{ item.text }}</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Gift, GraduationCap, Repeat, ChevronRight, ShoppingBag, ChevronLeft } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import DragonCute from '@/assets/logodragoncute.png';
const auth = useAuthStore();

onMounted(() => {
  auth.fetchUser();
});
const eduPromos = ref([
  { text: 'Đăng ký nhận ưu đãi', href: '#' },
  { text: 'Tựu trường lên cấp - Máy mới lên đời', href: '#' },
  { text: 'Laptop giảm thêm đến 500K', href: '#' },
]);

const tradeInPromos = ref([
  { text: 'iPhone trợ giá đến 5 triệu', href: '#' },
  { text: 'Samsung trợ giá đến 4 triệu', href: '#' },
  { text: 'Laptop trợ giá đến 4 triệu', href: '#' },
]);
</script>

<style scoped>
.right-sidebar {
  display: flex;
  flex-direction: column;
  gap: 10px;
  height: 100%;
}
.info-card {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0px 2px 8px rgba(0,0,0,0.08);
  font-family: sans-serif;
  height: 188px;
}
.info-card-bottom {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0px 2px 8px rgba(0,0,0,0.08);
  font-family: sans-serif;
  height: 320px;
}
.user-info p {
  margin: 0;
  color: #111;
}

.user-stats {
  margin-top: 20px;
}

.stat-item {
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-bottom: 10px;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 18px;
  font-weight: bold;
}

.pink-icon {
  color: #e63216;
;
}

.main {
  font-size: 13px;
  font-weight: 500;
  margin: 0;
  color: #504d4d;
}


.arrow {
  margin-left: auto;
  color: #999;
  font-weight: bold;
}

.manage-account {
  display: block;
  margin-top: 16px;
  text-align: right;
  font-size: 14px;
  color: #d6336c;
  text-decoration: none;
  font-weight: 600;
}

.manage-account:hover {
  opacity: .8;
}

.welcome-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.welcome-header p {
  font-size: 14px;
  margin-top: 10px;
}
.avatar { border-radius: 50%; }
.welcome-text { font-size: 13px; color: #555; margin: 0 0 15px; }
.auth-links { margin-bottom: 15px; }
.auth-links a { font-weight: bold; color: #d70018; text-decoration: none; }
.s-member-link { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #d70018; font-weight: bold; font-size: 13px; }
.s-member-link span { flex-grow: 1; }

.card-title {font-size: 13px; font-weight: bold; margin-bottom: 10px; background-color: #f5f5f5; padding: 8px; border-radius: 8px; text-align: center; }
.info-card ul {font-size: 13px; list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.info-card li a {font-size: 13px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; }
.info-card-bottom ul {font-size: 13px; list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.info-card-bottom li a {font-size: 13px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; }
</style>