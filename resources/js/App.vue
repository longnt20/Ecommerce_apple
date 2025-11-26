<template>
  <AppHeader />
  <router-view />
  <GlobalLoading />
</template>

<script setup>
import AppHeader from './components/header/AppHeader.vue';
import ProductApp from './components/HomePage/HomPage.vue';
import GlobalLoading from './components/loading/GlobalLoading.vue';
import MainContent from './components/navbar/MainContent.vue';
</script>

<style> /* Bỏ "scoped" đi để style này áp dụng cho toàn bộ trang */
/* 
  1. CSS Variables (Biến CSS):
  Khai báo các biến màu và font ở đây để dễ dàng tái sử dụng và quản lý.
*/
:root {
  --primary-color: #3B82F6;
  --text-color: #333333;
  --text-color-light: #555555;
  --text-color-white: #ffffff;
  --font-family-main: 'Inter', sans-serif; /* <-- SỬ DỤNG FONT INTER */
}

/* 
  2. Reset và thiết lập mặc định cho body:
  Áp dụng font cho toàn bộ trang.
*/
body {
  margin: 0;
  background-color: #ffffff;
  font-family: var(--font-family-main); /* <-- Áp dụng font ở đây */
  color: var(--text-color);
  -webkit-font-smoothing: antialiased; /* Làm chữ mượt hơn trên Chrome/Safari */
  -moz-osx-font-smoothing: grayscale;   /* Làm chữ mượt hơn trên Firefox */
}

/* 
  3. Reset mặc định cho các thẻ khác nếu cần
*/
* {
  box-sizing: border-box;
}

a {
  text-decoration: none;
  color: inherit; /* Kế thừa màu từ thẻ cha */
}

h1, h2, h3, h4, h5, h6, p {
  margin: 0;
}
</style>
