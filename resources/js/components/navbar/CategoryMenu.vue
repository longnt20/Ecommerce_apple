<template>
  <div class="category-menu">
    <ul>
      <!-- 
        1. v-for: Lặp qua mảng 'categories' trong script.
        Với mỗi 'category' trong mảng, nó sẽ tạo ra một thẻ <li>.
        ':key' là bắt buộc và phải là duy nhất để Vue theo dõi các phần tử.
      -->
      <li v-for="category in categories" :key="category.text">
        <a :href="category.href">
          <!-- 
            2. <component :is="...">: Đây là một tính năng mạnh mẽ của Vue.
            Nó cho phép bạn render một component động dựa trên tên được truyền vào.
            Ở đây, giá trị của 'category.iconComponent' sẽ quyết định icon nào được hiển thị.
          -->
          <component :is="category.iconComponent" :size="20" class="icon" />
          <span>{{ category.text }}</span>
          <ChevronRight :size="16" class="arrow-icon" />
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue';
// Import tất cả các icon cần thiết từ thư viện
import {
  Smartphone, Laptop, Headphones, Watch, Home, Puzzle, PcCase,
  Tv, Repeat, Package, Percent, FileText, ChevronRight
} from 'lucide-vue-next';

// 3. Dữ liệu cho menu
// Sử dụng ref để bọc mảng. Dù mảng này tĩnh, đây là một thói quen tốt.
// Mỗi object chứa text, link, và component icon tương ứng.
const categories = ref([
  { text: 'Điện thoại, Tablet', href: '#', iconComponent: Smartphone },
  { text: 'Laptop', href: '#', iconComponent: Laptop },
  { text: 'Âm thanh, Mic thu âm', href: '#', iconComponent: Headphones },
  { text: 'Đồng hồ, Camera', href: '#', iconComponent: Watch },
  { text: 'Đồ gia dụng', href: '#', iconComponent: Home },
  { text: 'Phụ kiện', href: '#', iconComponent: Puzzle },
  { text: 'PC, Màn hình, Máy in', href: '#', iconComponent: PcCase },
  { text: 'Tivi, Máy lạnh, Tủ lạnh', href: '#', iconComponent: Tv },
  { text: 'Thu cũ đổi mới', href: '#', iconComponent: Repeat },
  { text: 'Hàng cũ', href: '#', iconComponent: Package },
  { text: 'Khuyến mãi', href: '#', iconComponent: Percent },
  { text: 'Tin công nghệ', href: '#', iconComponent: FileText },
]);
</script>

<style scoped>
/* "scoped" đảm bảo CSS này chỉ áp dụng cho component này */
.category-menu {
  background-color: white;
  border-radius: 12px;
  padding: 8px 0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

li a {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  text-decoration: none;
  color: #333;
  font-size: 13px; /* Giảm size một chút */
  font-weight: 500; /* Tăng độ đậm để dễ đọc hơn */
  transition: background-color 0.2s;
}

li a:hover {
  background-color: #f5f5f5;
}

.icon {
  margin-right: 12px;
  color: #555;
}

span {
  flex-grow: 1; /* Đẩy mũi tên sang hết bên phải */
}

.arrow-icon {
  color: #999;
}
</style>