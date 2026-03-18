<template>
  <div class="container container-custom bg-white p-4 rounded shadow-sm my-4">
    <h6 class="mb-4 fw-bold">Đánh giá {{ product.name }}</h6>

    <!-- Tổng quan đánh giá -->
    <div class="row mb-4 align-items-center">
      <!-- Điểm trung bình -->
      <div class="col-4 col-md-3 text-center" style="width: 200px;">
        <div class="fs-1 fw-bold text-danger">
          {{ avgRating }}<span class="fs-2 text-secondary">/5</span>
        </div>

        <!-- Hiển thị sao -->
        <span class="stars-inline">
          <template v-for="i in 5" :key="'avg-star-' + i">
            <svg
              v-if="i <= fullStars"
              class="text-warning"
              viewBox="0 0 24 24"
              style="width:24px;height:24px;fill:currentColor;"
            >
              <path
                d="M12 17.27L18.18 21l-1.64-7.03L22
              9.24l-7.19-.61L12 2 9.19 8.63
              2 9.24l5.46 4.73L5.82 21z"
              />
            </svg>

            <svg
              v-else-if="i === fullStars + 1 && hasHalfStar"
              viewBox="0 0 24 24"
              style="width:24px;height:24px;"
            >
              <defs>
                <linearGradient id="half-star" x1="0%" x2="100%">
                  <stop offset="50%" stop-color="gold" />
                  <stop offset="50%" stop-color="lightgray" />
                </linearGradient>
              </defs>
              <path
                fill="url(#half-star)"
                d="M12 17.27L18.18 21l-1.64-7.03L22
              9.24l-7.19-.61L12 2 9.19 8.63
              2 9.24l5.46 4.73L5.82 21z"
              />
            </svg>

            <svg
              v-else
              class="text-secondary"
              viewBox="0 0 24 24"
              style="width:24px;height:24px;fill:currentColor;"
            >
              <path
                d="M12 17.27L18.18 21l-1.64-7.03L22
              9.24l-7.19-.61L12 2 9.19 8.63
              2 9.24l5.46 4.73L5.82 21z"
              />
            </svg>
          </template>
        </span>

        <div class="text-muted large">{{ reviews.length }} lượt đánh giá</div>
      </div>

      <!-- Thanh tiến độ theo sao -->
      <div class="col-3 col-md-4" style="width: 400px;">
        <div
          v-for="star in [5,4,3,2,1]"
          :key="'rating-row-' + star"
          class="mb-1 d-flex align-items-center"
        >
          <span class="stars-inline">{{ star }}
            <svg
              class="star-icon"
              viewBox="0 0 24 24"
              style="width:20px;height:20px;fill:currentColor;"
            >
              <path
                d="M12 17.27L18.18 21l-1.64-7.03L22
              9.24l-7.19-.61L12 2 9.19 8.63
              2 9.24l5.46 4.73L5.82 21z"
              />
            </svg>
          </span>

          <div class="rating-bar flex-grow-1 mx-2">
            <div
              class="rating-bar-fill"
              :style="{ width: ratingPercent[star] + '%' }"
            ></div>
          </div>

          <span class="text-muted small" style="min-width: 70px;">
            {{ ratingCount[star] }} đánh giá
          </span>
        </div>
      </div>

      <!-- Divider -->
      <div class="col-auto d-flex justify-content-center p-0" style="height: 120px;">
        <div class="vr mx-3" style="height: 100%; opacity: 0.5;"></div>
      </div>

      <!-- Trải nghiệm -->
      <div class="col-4 col-md-3 d-none d-md-block" style="width: 380px;">
        <div class="fw-bold mb-3">Đánh giá theo trải nghiệm</div>

        <div
          v-for="(exp, label) in experienceAvg"
          :key="'exp-' + label"
          class="d-flex justify-content-between align-items-center mb-2 small"
        >
          <span>{{ label }}</span>
          <span class="stars-inline">
            <template v-for="i in 5" :key="'exp-star-'+i">
              <svg
                v-if="i <= Math.floor(exp.avg)"
                class="text-warning"
                viewBox="0 0 24 24"
                style="width:20px;height:20px;fill:currentColor;"
              >
                <path
                  d="M12 17.27L18.18 21l-1.64-7.03L22
                9.24l-7.19-.61L12 2 9.19 8.63
                2 9.24l5.46 4.73L5.82 21z"
                />
              </svg>

              <svg
                v-else-if="i === Math.floor(exp.avg) + 1 && exp.avg - Math.floor(exp.avg) >= 0.5"
                viewBox="0 0 24 24"
                style="width:20px;height:20px;"
              >
                <defs>
                  <linearGradient :id="'exp-'+label" x1="0%" x2="100%">
                    <stop offset="50%" stop-color="gold" />
                    <stop offset="50%" stop-color="lightgray" />
                  </linearGradient>
                </defs>
                <path
                  :fill="'url(#exp-'+label+')'"
                  d="M12 17.27L18.18 21l-1.64-7.03L22
                9.24l-7.19-.61L12 2 9.19 8.63
                2 9.24l5.46 4.73L5.82 21z"
                />
              </svg>

              <svg
                v-else
                class="text-secondary"
                viewBox="0 0 24 24"
                style="width:20px;height:20px;fill:currentColor;"
              >
                <path
                  d="M12 17.27L18.18 21l-1.64-7.03L22
                9.24l-7.19-.61L12 2 9.19 8.63
                2 9.24l5.46 4.73L5.82 21z"
                />
              </svg>
            </template>

            <span class="ms-1 text-black-50">{{ exp.avg }}/5</span>
            <span class="ms-2 text-muted">({{ exp.count }} đánh giá)</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Bộ lọc -->
    <div class="filter-container mb-3">
      <button class="filter-btn active">Tất cả</button>
      <button class="filter-btn">Có hình ảnh/Video (345)</button>
      <button class="filter-btn">5 sao (300)</button>
      <button class="filter-btn">4 sao (200)</button>
      <button class="filter-btn">3 sao (321)</button>
      <button class="filter-btn">2 sao (129)</button>
      <button class="filter-btn">1 sao (55)</button>
    </div>

    <!-- Danh sách đánh giá -->
    <div
      v-for="r in reviews"
      :key="r.id"
      class="mb-4 border-bottom pb-3"
    >
      <div class="d-flex gap-3">
        <div class="avatar-circle avatar-d">
          <img
            :src="r.user.avatar"
            alt=""
            class="rounded-circle"
            style="width:40px;height:40px;"
            v-if="r.user.avatar"
          />
          <span v-else>{{ r.user.name.charAt(0) }}</span>
        </div>

        <div class="flex-grow-1">
          <div class="review-header">
            {{ r.user.name }}

            <!-- Stars -->
            <span class="stars-inline ms-2">
              <svg
                v-for="i in 5"
                :key="'r-star-' + r.id + '-' + i"
                class="star-icon"
                :class="i <= r.rating ? 'text-warning' : 'text-light'"
                viewBox="0 0 24 24"
                style="width:16px;height:16px;fill:currentColor;"
              >
                <path
                  d="M12 17.27L18.18 21l-1.64-7.03L22
                9.24l-7.19-.61L12 2 9.19 8.63
                2 9.24l5.46 4.73L5.82 21z"
                />
              </svg>
            </span>
          </div>

          <div class="text-muted small">Phân loại hàng: {{ r.variant }}</div>

          <div class="review-subtitle">{{ r.comment }}</div>

          <div class="review-time">
            <i class="bi bi-clock"></i> {{ r.created_at }}
          </div>

          <!-- Media -->
          <div class="review-media mt-2 d-flex gap-2" v-if="r.media.length">
            <img
              v-for="(img, idx) in r.media"
              :key="'rm-' + idx"
              :src="img"
              style="width:60px;height:60px;object-fit:cover;border-radius:4px;"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Xem tất cả -->
    <div class="text-center">
      <button class="btn btn-outline-secondary fw-semibold">
        Xem tất cả đánh giá
        <i class="bi bi-arrow-right ms-2"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

// Fake product data
const product = {
  name: "iPhone 16 Pro Max",
};

// Fake review data
const reviews = [
  {
    id: 1,
    user: { name: "Nguyen Van A", avatar: null },
    rating: 5,
    comment: "Máy dùng rất mượt, pin trâu.",
    variant: "128GB - Xanh",
    created_at: "2 ngày trước",
    media: [
      "https://via.placeholder.com/100",
      "https://via.placeholder.com/100",
    ],
  },
  {
    id: 2,
    user: { name: "Tran B", avatar: null },
    rating: 4,
    comment: "Chụp ảnh đẹp. Quá ổn!",
    variant: "256GB - Đen",
    created_at: "5 ngày trước",
    media: [],
  },
];

const avgRating = 4.5;
const fullStars = Math.floor(avgRating);
const hasHalfStar = avgRating - fullStars >= 0.5;

// Fake rating distribution
const ratingPercent = {
  5: 80,
  4: 60,
  3: 40,
  2: 20,
  1: 10,
};

const ratingCount = {
  5: 300,
  4: 200,
  3: 321,
  2: 129,
  1: 55,
};

// Fake experience
const experienceAvg = {
  "Hiệu năng": { avg: 4.8, count: 200 },
  "Camera": { avg: 4.3, count: 150 },
  "Pin": { avg: 4.0, count: 180 },
};
</script>

<style scoped>
.rating-bar {
  background: #e5e5e5;
  height: 6px;
  border-radius: 4px;
}
.rating-bar-fill {
  background: #ffc107;
  height: 100%;
  border-radius: 4px;
}

.filter-btn {
  padding: 6px 12px;
  border-radius: 20px;
  border: 1px solid #ddd;
  background: white;
  margin-right: 8px;
  cursor: pointer;
}
.filter-btn.active {
  background: #f5f5f5;
  border-color: #bbb;
}

.review-subtitle {
  margin-top: 6px;
}

.review-time {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}
</style>
