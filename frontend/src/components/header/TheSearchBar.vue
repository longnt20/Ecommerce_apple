<template>
    <div class="search-bar">
        <Search :size="20" class="search-icon" />
        <input type="text" v-model="searchQuery" placeholder="Bạn muốn mua gì hôm nay?" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Search } from 'lucide-vue-next';

const searchQuery = ref('');
</script>
<style scoped>
.search-bar {
    display: flex;
    align-items: center;
    background-color: white;
    border-radius: 8px;
    padding: 8px 12px;
    flex-grow: 1;
    max-width: 500px;
}

.search-icon {
    color: #666;
    margin-right: 8px;
}

.search-bar input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 14px;
}
</style>