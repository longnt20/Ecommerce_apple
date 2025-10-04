<template>
  <div class="right-sidebar">
    <!-- Card Chào mừng -->
    <div class="info-card">
      <div class="welcome-header">
        <img src="https://via.placeholder.com/40x40?text=Avatar" alt="Avatar" class="avatar">
        <p><strong>Chào mừng bạn đến với CellphoneS</strong></p>
      </div>
      <p class="welcome-text">Nhập hội thành viên Smember để không bỏ lỡ các ưu đãi hấp dẫn.</p>
      <div class="auth-links">
        <a href="#" class="login">Đăng nhập</a> hoặc <a href="#" class="register">Đăng ký</a>
      </div>
      <a href="#" class="s-member-link">
        <Gift :size="16" />
        <span>Xem ưu đãi Smember</span>
        <ChevronRight :size="16" />
      </a>
    </div>

    <!-- Card Ưu đãi -->
    <div class="info-card">
      <div class="card-title">Ưu đãi cho giáo dục</div>
      <ul>
        <li v-for="item in eduPromos" :key="item.text">
          <a :href="item.href">
            <GraduationCap :size="18" />
            <span>{{ item.text }}</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Card Thu cũ -->
    <div class="info-card">
      <div class="card-title">Thu cũ lên đời giá hời</div>
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
import { ref } from 'vue';
import { Gift, GraduationCap, Repeat, ChevronRight } from 'lucide-vue-next';

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
  gap: 15px;
}
.info-card {
  background-color: white;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  font-size: 14px;
}
.welcome-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.avatar { border-radius: 50%; }
.welcome-text { font-size: 13px; color: #555; margin: 0 0 15px; }
.auth-links { margin-bottom: 15px; }
.auth-links a { font-weight: bold; color: #d70018; text-decoration: none; }
.s-member-link { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #d70018; font-weight: bold; font-size: 13px; }
.s-member-link span { flex-grow: 1; }

.card-title { font-weight: bold; margin-bottom: 10px; background-color: #f5f5f5; padding: 8px; border-radius: 8px; text-align: center; }
.info-card ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.info-card li a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; }
</style>