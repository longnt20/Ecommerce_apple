<template>
  <div 
    v-if="loading.isLoading" 
    class="global-loading"
  >
    <div class="loader-spin"></div>
  </div>
</template>

<script setup>
import { useLoadingStore } from '@/stores/loading';

const loading = useLoadingStore()
</script>

<style scoped>
.global-loading {
  position: fixed;
  inset: 0;
  backdrop-filter: blur(3px);
  background: rgba(0,0,0,0.35);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loader-spin {
  width: 60px;
  height: 60px;
  border: 5px solid #fff;
  border-top: 5px solid #3498db;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
