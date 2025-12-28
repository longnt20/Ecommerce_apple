<template>
  <div class="snowfall">
    <div
      v-for="flake in flakes"
      :key="flake.id"
      class="flake-wrapper"
      :style="flake.wrapperStyle"
    >
      <div class="snowflake" :style="flake.flakeStyle">
        <img 
        :src="snow.image" alt="" class="snow-img">
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
const snow = {
    image: '/logos/snow2.png'
}
const flakes = ref([])
let timer

const createFlake = () => {
  const fallDuration = Math.random() * 8 + 6

  const id = Math.random().toString(36)

  setTimeout(() => {
    flakes.value = flakes.value.filter(f => f.id !== id)
  }, fallDuration * 1000)

  return {
    id,
    wrapperStyle: {
      left: Math.random() * 100 + 'vw',
      animationDuration: `${fallDuration}s`
    },
    flakeStyle: {
      opacity: Math.random() * 0.6 + 0.4,
      animationDuration: `${Math.random() * 3 + 2}s`,
      '--sway': `${Math.random() * 80 - 40}px`
    }
  }
}


onMounted(() => {
  timer = setInterval(() => {
    flakes.value.push(createFlake())
    if (flakes.value.length > 80) flakes.value.shift()
  }, 250)
})

onBeforeUnmount(() => clearInterval(timer))
</script>

<style scoped>
.snowfall {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
  overflow: hidden;
}

/* RƠI XUỐNG */
.flake-wrapper {
  position: absolute;
  top: -50px;
  animation-name: fall;
  animation-timing-function: linear;
  animation-iteration-count: 1;
}

/* LẮC NGANG */
.snowflake {
  color: white;
  animation-name: sway;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}
.snow-img {
  width: 24px;
  height: auto;
  pointer-events: none;
  user-select: none;
}

@keyframes fall {
  to {
    transform: translateY(110vh);
  }
}

@keyframes sway {
  0%   { transform: translateX(0); }
  50%  { transform: translateX(var(--sway)); }
  100% { transform: translateX(0); }
}
</style>
