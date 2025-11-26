<template>
  <div class="product-wrapper">
    <!-- LEFT CONTENT -->
    <div class="left">
      <h2 class="title">Đặc điểm nổi bật của {{ product?.name }}</h2>

      <p class="desc">
        {{ product?.short_description }}
      </p>

      <button class="collapse-btn" @click="toggleContent">
        Nội dung chính
        <span :class="{ rotated: open }">⌄</span>
      </button>

      <!-- CKEditor description -->
      <div 
        class="content-section" 
        :class="{ collapsed: !expanded }" 
        ref="contentSection"
      >
        <div class="ck-content" v-html="product?.description"></div>
      </div>

      <!-- Xem thêm / Thu gọn -->
      <button 
        v-if="showToggle" 
        class="more-btn" 
        @click="expanded = !expanded"
      >
        {{ expanded ? "Thu gọn" : "Xem thêm" }}
      </button>
</div>
    <!-- RIGHT SIDEBAR -->
    <div class="right" ref="newsRight">
      <div class="news-header">
        <h3>Tin tức sản phẩm</h3>
        <a href="#">Xem tất cả ></a>
      </div>

      <div class="news-list">
        <div class="news-item" v-for="blog in product?.blogs" :key="blog.id">
          <img :src="blog.thumbnail" />
          <div class="news-text">
            <p class="news-title">{{ blog.title }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from "vue";

const props = defineProps({
  product: {
    type: Object,
    default: null,
  },
  maxHeight: {
    type: Number,
    default: 300
  }
})
const open = ref(true);
const expanded = ref(false);     // Xem thêm / Thu gọn CKEditor
const showToggle = ref(false);   // Hiện nút Xem thêm nếu nội dung cao
const contentSection = ref(null);
const newsRight = ref(null);
const toggleContent = () => {
  open.value = !open.value;
};
// Kiểm tra chiều cao CKEditor để show nút Xem thêm
onMounted(() => {
  nextTick(() => {
    // tạm bật full để đo
    const wasExpanded = expanded.value
    expanded.value = true
    
    nextTick(() => {
      const height = contentSection.value?.scrollHeight || 0

      if (height > props.maxHeight) showToggle.value = true

      // thu lại trạng thái ban đầu
      expanded.value = wasExpanded
    })
  })
})



</script>

<style scoped>
.product-wrapper {
  display: flex;
  gap: 24px;
  padding: 16px 16px 16px 0;
}

.left {
  width: 65%;
  background: #f7f7f8;
    border: 1px solid #d9cece;
    border-radius: 10px;
    padding: 16px;
}

.right {
  width: 35%;
    border: 1px solid #d9cece;
    border-radius: 10px;
    background: #f7f7f8;
    padding: 16px;
}

.title {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 12px;
}

.desc {
  font-size: 15px;
  line-height: 1.6;
  margin-bottom: 16px; 
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 10px;
  background: #fff;
}

.collapse-btn {
  width: 100%;
  padding: 12px;
  background: #f2f2f2;
  border: 1px solid #ddd;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  font-size: 16px;
}

.collapse-btn span {
  transition: transform 0.2s ease;
}

.collapse-btn span.rotated {
  transform: rotate(180deg);
}
.content-section {
  overflow: hidden;
  transition: max-height 0.3s ease;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 10px;
  background: #fff;
  margin-top: 20px;
}
.content-section.collapsed {
  max-height: 300px; /* collapsed height */
  position: relative;
}

/* Gradient fade-bottom */
.content-section.collapsed::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 50px;
  background: linear-gradient(rgba(255,255,255,0), #fff);
}

/* Xem thêm / Thu gọn button */
.more-btn {
  display: block;
  margin-top: 10px;
  padding: 6px 12px;
  background-color: #0066ff;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.content-section h3 {
  font-size: 18px;
  margin-top: 16px;
}

.content-section p {
  margin: 8px 0 16px;
  line-height: 1.6;
}

.image-box img {
  width: 100%;
  border-radius: 8px;
  margin: 12px 0;
}

.news-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.news-header h3 {
  margin: 0;
  font-size: 20px;
}

.news-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.news-item {
  display: flex;
  gap: 12px;
  padding: 8px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #eee;
  cursor: pointer;
  transition: background 0.2s ease;
}

.news-item:hover {
  background: #f6f6f6;
}

.news-item img {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  object-fit: cover;
}

.news-title {
  font-size: 15px;
  line-height: 1.4;
}
</style>
<style>
.ck-content img {
  max-width: 100% !important;
  height: auto !important;
  display: block;
  margin: 0 auto;
}

.ck-content {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}
/* Table cơ bản */
.ck-content table {
  width: 100%;          /* table chiếm toàn bộ container */
  border-collapse: collapse;
  margin-bottom: 16px;
  table-layout: auto;   /* tự co theo nội dung, nếu muốn fix width dùng table-layout: fixed; */
}

/* Table cell */
.ck-content th,
.ck-content td {
  border: 1px solid #ddd;   /* viền nhẹ */
  padding: 8px 12px;
  text-align: left;
}

/* Header table */
.ck-content th {
  background-color: #f5f5f5;
  font-weight: 600;
}

/* Table hover */
.ck-content tr:hover {
  background-color: #f1f3f4;
}

/* Responsive: cuộn ngang nếu table quá rộng */
@media (max-width: 768px) {
  .ck-content table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
}

/* Optional: căn giữa table */
.ck-content table.center {
  margin-left: auto;
  margin-right: auto;
}
.product-wrapper {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

/* LEFT */
.left {
  flex: 2;
}

/* Collapse / expand content */


/* CKEditor content */
.ck-content img {
  max-width: 100% !important;
  height: auto !important;
  display: block;
  margin: 0 auto;
}

/* RIGHT */
.right {
  flex: 1;
  position: sticky;
  top: 20px; /* scroll theo page */
  align-self: flex-start;
}

/* Tin tức */
.news-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.news-item img {
  width: 100%;
  border-radius: 6px;
}

</style>
