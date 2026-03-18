<template>
  <section class="news-section">
    <!-- Header -->
    <div class="news-header">
      <h2 class="title">TIN TỨC</h2>
      <RouterLink to="/tin-tuc" class="view-all">
        Xem tất cả →
      </RouterLink>
    </div>

    <!-- List -->
    <div class="news-list">
      <RouterLink
        v-for="blog in blogs"
        :key="blog.slug"
        :to="`/tin-tuc/${blog.slug}`"
        class="news-card"
      >
        <div class="thumbnail">
          <img
            :src="blog.thumbnail"
            :alt="blog.title"
            loading="lazy"
          />
        </div>

        <h3 class="news-title">
          {{ blog.title }}
        </h3>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  blogs: {
    type: Array,
    default: () => []
  }
})

</script>

<style scoped>
.news-section {
  background: #fff;
  padding: 12px 0;
}

/* HEADER */
.news-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.title {
  font-size: 20px;
  font-weight: 700;
}

.view-all {
  font-size: 14px;
  color: #2563eb;
  text-decoration: none;
}

.view-all:hover {
  text-decoration: underline;
}

/* LIST */
.news-list {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
}

/* CARD */
.news-card {
  background: #fff;
  border-radius: 12px;
  padding: 8px;
  text-decoration: none;
  color: #111;
  transition: box-shadow 0.2s ease;
}

.news-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.thumbnail {
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 8px;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* TITLE */
.news-title {
  font-size: 14px;
  font-weight: 600;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .news-list {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .news-list {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
