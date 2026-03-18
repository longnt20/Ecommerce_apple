<template>
  <!-- User Button -->
  <button class="user-button" @click="openModal">
    <UserCircle2 :size="20" />
    <span class="user-label">{{ auth.isLoggedIn ? auth.user?.name : 'Đăng nhập' }}</span>
  </button>
  <!-- User Menu Dropdown -->
<Transition name="fade">
  <div
    v-if="auth.isLoggedIn && showUserMenu"
    class="user-dropdown"
    @click.stop
  >
    <div class="user-info">
     <strong>{{ auth.user?.name }}</strong>
    <span class="email">{{ auth.user?.email }}</span>
    </div>

    <router-link to="/profile" class="menu-item">Tài khoản của tôi</router-link>
    <router-link to="/orders" class="menu-item">Đơn mua</router-link>

    <button class="menu-item logout" @click="logout">
      Đăng xuất
    </button>
  </div>
</Transition>

  <!-- Auth Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
          <!-- Close button -->
          <button class="modal-close" @click="closeModal">
            <X :size="20" />
          </button>

          <!-- Modal Header -->
          <div class="modal-header">
            <div class="tab-switcher">
              <button 
                :class="['tab-btn', { active: activeTab === 'login' }]"
                @click="activeTab = 'login'"
              >
                Đăng nhập
              </button>
              <button 
                :class="['tab-btn', { active: activeTab === 'register' }]"
                @click="activeTab = 'register'"
              >
                Đăng ký
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <!-- Login Form -->
            <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="auth-form">
              <div class="form-group">
                <label for="login-email">Email/Số điện thoại</label>
                <div class="input-wrapper">
                  <Mail :size="18" class="input-icon" />
                  <input
                    id="login-email"
                    v-model="loginForm.email"
                    type="text"
                    placeholder="Nhập email hoặc số điện thoại"
                    required
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="login-password">Mật khẩu</label>
                <div class="input-wrapper">
                  <Lock :size="18" class="input-icon" />
                  <input
                    id="login-password"
                    v-model="loginForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Nhập mật khẩu"
                    required
                  />
                  <button
                    type="button"
                    class="toggle-password"
                    @click="showPassword = !showPassword"
                  >
                    <Eye v-if="!showPassword" :size="18" />
                    <EyeOff v-else :size="18" />
                  </button>
                </div>
              </div>

              <div class="form-options">
                <label class="checkbox-wrapper">
                  <input type="checkbox" v-model="rememberMe" />
                  <span>Ghi nhớ đăng nhập</span>
                </label>
                <a href="#" class="forgot-link" @click.prevent="handleForgotPassword">
                  Quên mật khẩu?
                </a>
              </div>

              <button type="submit" class="submit-btn" :disabled="isLoading">
                <Loader2 v-if="isLoading" :size="18" class="spinner" />
                <span>{{ isLoading ? 'Đang xử lý...' : 'Đăng nhập' }}</span>
              </button>

              <div class="divider">
                <span>Hoặc</span>
              </div>

              <div class="social-login">
                <a class="social-btn google" href="http://127.0.0.1:8000/api/auth/google/redirect">
                  <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" />
                  <span>Đăng nhập với Google</span>
                </a>
                <button type="button" class="social-btn facebook">
                  <img src="https://www.svgrepo.com/show/475647/facebook-color.svg" alt="Facebook" width="20" />
                  <span>Đăng nhập với Facebook</span>
                </button>
              </div>
            </form>

            <!-- Register Form -->
            <form v-else @submit.prevent="handleRegister" class="auth-form">
              <div class="form-group">
                <label for="register-name">Họ và tên</label>
                <div class="input-wrapper">
                  <User :size="18" class="input-icon" />
                  <input
                    id="register-name"
                    v-model="registerForm.name"
                    type="text"
                    placeholder="Nhập họ và tên"
                    required
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="register-email">Email/Số điện thoại</label>
                <div class="input-wrapper">
                  <Mail :size="18" class="input-icon" />
                  <input
                    id="register-email"
                    v-model="registerForm.email"
                    type="text"
                    placeholder="Nhập email hoặc số điện thoại"
                    required
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="register-password">Mật khẩu</label>
                <div class="input-wrapper">
                  <Lock :size="18" class="input-icon" />
                  <input
                    id="register-password"
                    v-model="registerForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                    required
                    minlength="6"
                  />
                  <button
                    type="button"
                    class="toggle-password"
                    @click="showPassword = !showPassword"
                  >
                    <Eye v-if="!showPassword" :size="18" />
                    <EyeOff v-else :size="18" />
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label for="register-confirm">Xác nhận mật khẩu</label>
                <div class="input-wrapper">
                  <Lock :size="18" class="input-icon" />
                  <input
                    id="register-confirm"
                    v-model="registerForm.confirmPassword"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Nhập lại mật khẩu"
                    required
                  />
                </div>
              </div>

              <div class="form-options">
                <label class="checkbox-wrapper">
                  <input type="checkbox" v-model="agreeTerms" required />
                  <span>
                    Tôi đồng ý với 
                    <a href="#" @click.prevent>Điều khoản sử dụng</a>
                  </span>
                </label>
              </div>

              <button type="submit" class="submit-btn" :disabled="isLoading">
                <Loader2 v-if="isLoading" :size="18" class="spinner" />
                <span>{{ isLoading ? 'Đang xử lý...' : 'Đăng ký' }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { 
  UserCircle2, X, Mail, Lock, Eye, EyeOff, User, Loader2 
} from 'lucide-vue-next'
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore()

// Modal state
const showModal = ref(false)
const activeTab = ref('login')
const showPassword = ref(false)
const isLoading = ref(false)
const showUserMenu = ref(false)

// Form state
const loginForm = ref({ email: '', password: '' })
const registerForm = ref({ name: '', email: '', password: '', confirmPassword: '' })
const rememberMe = ref(false)
const agreeTerms = ref(false)

// Open modal or menu
const openModal = () => {
  if (auth.isLoggedIn) {
    showUserMenu.value = !showUserMenu.value
  } else {
    showModal.value = true
    document.body.style.overflow = 'hidden'
  }
}

// Fetch user when component loads
onMounted(() => {
  auth.fetchUser()
})

// Close dropdown when click outside
const closeUserMenu = (e) => {
  if (!e.target.closest('.user-button') && !e.target.closest('.user-dropdown')) {
    showUserMenu.value = false
  }
}

onMounted(() => document.addEventListener('click', closeUserMenu))
onUnmounted(() => document.removeEventListener('click', closeUserMenu))

// Close modal
const closeModal = () => {
  showModal.value = false
  document.body.style.overflow = ''
  resetForms()
}

const resetForms = () => {
  loginForm.value = { email: '', password: '' }
  registerForm.value = { name: '', email: '', password: '', confirmPassword: '' }
  showPassword.value = false
  agreeTerms.value = false
}

// Login
const handleLogin = async () => {
  isLoading.value = true
  try {
    await auth.login(loginForm.value.email, loginForm.value.password)
    closeModal()
  } catch (err) {
    alert("Sai tài khoản hoặc mật khẩu")
  } finally {
    isLoading.value = false
  }
}

// Register
const handleRegister = async () => {
  if (registerForm.value.password !== registerForm.value.confirmPassword) {
    return alert("Mật khẩu không khớp!")
  }

  isLoading.value = true
  try {
    await auth.register(
      registerForm.value.name,
      registerForm.value.email,
      registerForm.value.password
    )

    alert("Đăng ký thành công! Hãy đăng nhập.")
    activeTab.value = 'login'
    resetForms()
  } finally {
    isLoading.value = false
  }
}

const logout = () => {
  auth.logout()
  showUserMenu.value = false
}

</script>

<style scoped>
/* User Button */
.user-button {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  color: #333;
}

.user-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.user-label {
  font-size: 14px;
  font-weight: 500;
}

/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

/* Modal Container */
.modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 440px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  background: transparent;
  border: none;
  padding: 8px;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
  border-radius: 50%;
  z-index: 1;
}

.modal-close:hover {
  background: #f5f5f5;
  color: #333;
}

/* Modal Header */
.modal-header {
  padding: 24px 24px 0;
}

.tab-switcher {
  display: flex;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 4px;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  background: transparent;
  border: none;
  border-radius: 6px;
  font-size: 15px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn.active {
  background: white;
  color: #333;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Modal Body */
.modal-body {
  padding: 24px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #333;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  color: #999;
}

.input-wrapper input {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #ef3343;
  box-shadow: 0 0 0 3px rgba(239, 51, 67, 0.1);
}

.toggle-password {
  position: absolute;
  right: 12px;
  background: transparent;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #999;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #333;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #666;
  cursor: pointer;
}

.checkbox-wrapper input {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.checkbox-wrapper a {
  color: #ef3343;
  text-decoration: none;
}

.checkbox-wrapper a:hover {
  text-decoration: underline;
}

.forgot-link {
  font-size: 13px;
  color: #ef3343;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

/* Submit Button */
.submit-btn {
  padding: 12px;
  background: #ef3343;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.submit-btn:hover:not(:disabled) {
  background: #d62b3a;
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Divider */
.divider {
  position: relative;
  text-align: center;
  margin: 20px 0;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e0e0e0;
}

.divider span {
  position: relative;
  background: white;
  padding: 0 12px;
  font-size: 13px;
  color: #999;
}

/* Social Login */
.social-login {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  transition: all 0.2s;
}

.social-btn:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

/* Modal Animations */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.9);
}

/* Responsive */
@media (max-width: 480px) {
  .user-label {
    display: none;
  }
  
  .modal-container {
    max-width: 100%;
    margin: 10px;
  }
  
  .modal-body {
    padding: 20px;
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .user-button {
    color: #f0f0f0;
  }
  
  .user-button:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
  
  .modal-container {
    background: #1e1e1e;
  }
  
  .modal-close {
    color: #aaa;
  }
  
  .modal-close:hover {
    background: #2a2a2a;
    color: #fff;
  }
  
  .tab-switcher {
    background: #2a2a2a;
  }
  
  .tab-btn {
    color: #aaa;
  }
  
  .tab-btn.active {
    background: #333;
    color: #fff;
  }
  
  .form-group label {
    color: #f0f0f0;
  }
  
  .input-wrapper input {
    background: #2a2a2a;
    border-color: #444;
    color: #f0f0f0;
  }
  
  .input-wrapper input:focus {
    border-color: #ef3343;
  }
  
  .divider span {
    background: #1e1e1e;
  }
  
  .social-btn {
    background: #2a2a2a;
    border-color: #444;
    color: #f0f0f0;
  }
  
  .social-btn:hover {
    background: #333;
  }
}
/* === USER DROPDOWN MENU === */
.user-dropdown {
  position: absolute;
  top: 120px;
  right: 180px;
  width: 240px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  padding: 12px 0;
  animation: fadeIn 0.18s ease-out;
  overflow: hidden;
  border: 1px solid #eee;
  z-index: 5;
}

/* Header user info */
.user-dropdown .user-info {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f1f1;
}

.user-dropdown .user-info strong {
  font-size: 15px;
  color: #222;
  display: block;
  margin-bottom: 2px;
}

.user-dropdown .user-info .email {
  font-size: 12px;
  color: #888;
}

/* Menu items */
.menu-item {
  padding: 12px 18px;
  display: block;
  cursor: pointer;
  transition: all 0.2s;
  color: #333;
  font-size: 14px;
  user-select: none;
  border: none;      /* <== FIX */
  outline: none;     /* <== FIX */
  background: none;
  width: 100%;
  text-align: left;
}

.menu-item:hover {
  background: #f8f9fa;
}

/* Nút đăng xuất */
.logout {
  color: #e63946;
  font-weight: 500;
}

.logout:hover {
  background: #ffe8e8 !important;
  color: #d62828;
}

/* Xóa focus viền đen của button */
.menu-item:focus,
.logout:focus,
button:focus {
  outline: none !important;
  box-shadow: none !important;
  border: none !important;
}

/* Fade animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


</style>