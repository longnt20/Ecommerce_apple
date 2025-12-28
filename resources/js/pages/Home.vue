<template>
  <div class="container">
    <MainContent />
    <HomePage />
  </div>
</template>
<style scoped>
  .container {
      width: 1200px;
  margin: 0 auto;
  background-color: #ffffff;
  }
</style>
<script setup>
import HomePage from '../components/HomePage/HomPage.vue'
import MainContent from '../components/navbar/MainContent.vue';
</script>
