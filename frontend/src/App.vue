<template>
  <AppHeader />
  <router-view />
  <Snowfall />
  <GlobalLoading />
  <DragonChat />
  <AppFooter />
</template>

<script setup>
import AppHeader from '@/components/header/AppHeader.vue'
import GlobalLoading from '@/components/loading/GlobalLoading.vue'
import Snowfall from '@/components/Snowfall.vue'
import AppFooter from '@/components/AppFooter.vue'
import DragonChat from '@/components/DragonChat.vue'
import { onMounted } from "vue"
import { useCartStore } from '@/stores/cart'
import bgImage from '@/assets/1920x1080_BG_Homepage_Noel-v2.webp'
document.documentElement.style.setProperty('--bg-image', `url(${bgImage})`);
const cart = useCartStore()

onMounted(() => {
  cart.loadCart()
})
</script>

<style>
:root {
  --primary-color: #3B82F6;
  --text-color: #333333;
  --text-color-light: #555555;
  --text-color-white: #ffffff;
  --font-family-main: 'Inter','Inter Fallback' ,Arial,sans-serif;
}

body {
  background: var(--bg-image);
  background-size: cover;
  background-position: center center;
  background-attachment: fixed;
  font-family: var(--font-family-main);
  margin: 0;
  padding: 0;
}

#app {
  min-height: 100vh;
}
</style>
