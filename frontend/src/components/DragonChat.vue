<template>
    <!-- Greeting Bubble (chưa click) -->
    <div class="greeting" v-if="showGreeting && !open">
        Chào bạn!<br />
        Có cần tôi giúp gì không ?
    </div>
    <!-- Dragon Button -->
    <div class="dragon-btn" @click="toggleChat">
        <img :src="DragonCute" alt="Dragon AI" />
    </div>

    <!-- Chat Box (sau khi click) -->
    <div class="chat-box" v-if="open">
        <div class="chat-header">
            Mercphobia
            <span @click="open = false">✖</span>
        </div>

        <div class="chat-body">
            <div class="bot">
                Xin chào 👋<br />
                Mình là Mercphobia, mình có thể hỗ trợ bạn 😊
            </div>
        </div>

        <div class="chat-footer">
            <input v-model="message" placeholder="Nhập câu hỏi..." @keyup.enter="send" />
            <button @click="send">Gửi</button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import DragonCute from '@/assets/logodragoncute.png'
import { CircleSmall } from 'lucide-vue-next'
const open = ref(false)
const message = ref('')
const showGreeting = ref(false)
// Delay bubble 3s
onMounted(() => {
    setTimeout(() => {
        showGreeting.value = true
    }, 10000)
})

const toggleChat = () => {
    open.value = true
    showGreeting.value = false
}

const send = () => {
    if (!message.value.trim()) return
    // sau này gọi API AI
    message.value = ''
}
</script>

<style scoped>
/* Greeting bubble */
.greeting {
    position: fixed;
    bottom: 105px;
    right: 70px;
    background: #ffffff;
    padding: 12px 16px;
    border-radius: 14px;
    font-size: 14px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, .15);
    animation: float 2s infinite;
    z-index: 998;
}
.greeting::after {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: #ffffff;
  bottom: -6px;
  right: 20px;     /* đổi sang left nếu cần */
  transform: rotate(45deg);
}

@keyframes float {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-6px);
    }
}

/* Dragon */
.dragon-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 90px;
    cursor: pointer;
    animation: wave 2s infinite;
    z-index: 999;
}

.dragon-btn img {
    width: 100%;
}

@keyframes wave {

    0%,
    100% {
        transform: rotate(0);
    }

    20% {
        transform: rotate(-8deg);
    }

    40% {
        transform: rotate(8deg);
    }
}

/* Chat box */
.chat-box {
    position: fixed;
    bottom: 120px;
    right: 55px;
    width: 300px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, .25);
    display: flex;
    flex-direction: column;
    z-index: 999;
}
.chat-box::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 88%;
  width: 16px;
  height: 16px;
  background: #3B82F6; /* cùng màu footer */
  transform: translateX(-50%) rotate(45deg);
  border-radius: 2px;
}
.chat-header {
    background: #3B82F6;
    color: white;
    padding: 12px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
}

.chat-body {
    padding: 12px;
    height: 300px;
    overflow-y: auto;
    font-size: 14px;
}

.bot {
    background: #f1f5f9;
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 6px;
}

.chat-footer {
    display: flex;
    border-top: 1px solid #eee;
}

.chat-footer input {
    flex: 1;
    border: none;
    padding: 10px;
}

.chat-footer button {
    background: #3B82F6;
    color: white;
    border: none;
    padding: 0 16px;
}
</style>
