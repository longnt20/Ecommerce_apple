<template>
  <div class="category-menu">
    <ul>
      <li v-for="category in categories" :key="category.id">
        <a :href="`/category/${category.slug}`" class="menu-item">
           <div class="icon-wrapper">
            <component :is="category.iconComponent" :size="18" class="icon" />
          </div>
          <span class="menu-text">{{ category.name }}</span>
          <ChevronRight :size="14" class="arrow-icon" />
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import {
  Smartphone, Laptop, Headphones, Watch, Home, Puzzle, PcCase,
  Tv, Repeat, Package, Percent, FileText, ChevronRight
} from 'lucide-vue-next';
import axios from 'axios';

const getIconByName = (name) => {
  const iconMap = {
    'Điện thoại, Tablet': Smartphone,
    'Laptop': Laptop,
    'Âm thanh, Mic thu âm': Headphones,
    'Đồng hồ, Camera': Watch,
    'Phụ kiện': Puzzle,
    'Thu cũ đổi mới': Repeat,
    'Hàng cũ': Package,
    'Khuyến mãi': Percent,
    'Tin công nghệ': FileText,
  }

  // Nếu không trùng tên nào, trả icon mặc định
  return iconMap[name] || FileText
}
const categories = ref([])
onMounted(async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/categories')
    
    const data = response.data.categories.data

    // Gán icon tương ứng
    categories.value = data.map(cat => ({
      ...cat,
      iconComponent: getIconByName(cat.name)
    }))
  } catch (error) {
    console.error('Lỗi khi lấy danh mục:', error)
  }
})
</script>

<style scoped>
.category-menu {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
              0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: 1px solid #f0f0f0;
  /* Đảm bảo không có khoảng trống */
  display: inline-block;
  height: 495px;
}

ul {
  list-style-type: none;
  margin: 0;
  padding: 5px;
}

li {
  margin-bottom: 4px;
  /* Animation được áp dụng trực tiếp */
  animation: slideIn 0.4s ease forwards;
}

li:last-child {
  margin-bottom: 0;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 9px 11px;
  text-decoration: none;
  color: #333;
  font-size: 13px;
  font-weight: 500;
  border-radius: 10px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.menu-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.02));
  transition: width 0.3s ease;
}

.menu-item:hover::before {
  width: 100%;
}

.menu-item:hover {
  background-color: #f8f9fa;
  transform: translateX(4px);
}

.menu-item:hover .arrow-icon {
  transform: translateX(3px);
  opacity: 1;
}

.menu-item:hover .icon-wrapper {
  transform: scale(1.1);
  background-color: #e9ecef;
}

.icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  background-color: #f8f9fa;
  transition: all 0.3s ease;
}

.icon {
  color: #3B82F6;
  transition: transform 0.3s ease;
}

.menu-text {
  flex-grow: 1;
  color: #495057;
  transition: color 0.3s ease;
}

.menu-item:hover .menu-text {
  color: #212529;
  font-weight: 600;
}

.arrow-icon {
  color: #999;
  opacity: 0.5;
  transition: all 0.3s ease;
}

/* Animation khi load */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Chỉ áp dụng animation-delay cho 8 items thực tế */
li:nth-child(1) { animation-delay: 0.05s; }
li:nth-child(2) { animation-delay: 0.1s; }
li:nth-child(3) { animation-delay: 0.15s; }
li:nth-child(4) { animation-delay: 0.2s; }
li:nth-child(5) { animation-delay: 0.25s; }
li:nth-child(6) { animation-delay: 0.3s; }
li:nth-child(7) { animation-delay: 0.35s; }
li:nth-child(8) { animation-delay: 0.4s; }

/* Responsive */
@media (max-width: 768px) {
  .category-menu {
    border-radius: 12px;
  }
  
  .menu-item {
    padding: 8px 10px;
    font-size: 12px;
  }
  
  .icon-wrapper {
    width: 28px;
    height: 28px;
    margin-right: 10px;
  }
}

/* Divider giữa các items */
li:not(:last-child) {
  position: relative;
}

li:not(:last-child)::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 44px;
  right: 12px;
  height: 1px;
  background: linear-gradient(90deg, #f0f0f0, transparent);
}
</style>