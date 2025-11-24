<template>
    <div class="feature-box">
        <div class="feature-content">
            <!-- LEFT IMAGE (optional) -->
            <div class="image-box" v-if="images && images.length">
                <img :src="images[0]" alt="iphone" />
            </div>

        
        </div>

        <!-- BOTTOM SLIDER -->
        <div class="bottom-slider">
            <button class="arrow-btn" @click="prevThumb">‹</button>

            <!-- Thumbnails -->
            <div class="thumb-list">
                <div v-for="(img, index) in images" :key="index" class="thumb-item" @click="selectThumb(index)">
                    <img :src="img" />
                </div>
            </div>

            <button class="arrow-btn" @click="nextThumb">›</button>
        </div>
    </div>
</template>
<script></script>

<style scoped>
/* MAIN CARD */
.feature-box {
    background: linear-gradient(135deg, #d86b86, #f6d1a6);
    border-radius: 20px;
    padding: 24px;
    color: white;
}

/* TOP CONTENT: IMG + TEXT */
.feature-content {
    display: flex;
    gap: 24px;
    align-items: center;
}

.image-box img {
    width: 260px;
    height: 320px;
    object-fit: cover;
    border-radius: 14px;
    background: #fff;
}

.text-box {
    flex: 1;
}

.text-box h2 {
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 12px;
    text-transform: uppercase;
}

.text-box ul {
    list-style: disc;
    padding-left: 20px;
    font-size: 16px;
    line-height: 1.55;
    margin: 0;
}

/* BOTTOM SLIDER */
.bottom-slider {
    margin-top: 20px;
    padding-top: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    overflow-x: auto;
    white-space: nowrap;
}

.main-btn {
    min-width: 140px;
    height: 70px;
    border: 2px solid #ddcfcf;
    border-radius: 12px;
    background: #fff;
    color: #333;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 14px;
    cursor: pointer;
}

.thumb-list {
    display: flex;
    gap: 12px;
}

.thumb-item {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    background: white;
    padding: 4px;
    cursor: pointer;
}

.thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* NEXT ARROW */
.arrow-btn {
    width: 36px;
    height: 70px;
    background: white;
    border-radius: 10px;
    border: none;
    font-size: 24px;
    cursor: pointer;
}
</style>
