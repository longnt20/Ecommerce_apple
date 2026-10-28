<template>
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

              <div class="form-group">
                <label for="register-confirm">Xác nhận mật khẩu</label>
                <div class="input-wrapper">
                  <Lock :size="18" class="input-icon" />
                  <input
                    id="register-confirm"
                    v-model="registerForm.confirmPassword"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Xác nhận mật khẩu"
                    required
                  />
                </div>
              </div>

              <div class="form-options">
                <label class="checkbox-wrapper">
                  <input type="checkbox" v-model="agreeTerms" />
                  <span>Tôi đồng ý với điều khoản sử dụng</span>
                </label>
              </div>

              <button type="submit" class="submit-btn" :disabled="isLoading || !agreeTerms">
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
import { ref, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
// import { toast } from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import { 
  X, Mail, Lock, Eye, EyeOff, User, Loader2 
} from 'lucide-vue-next'

const props = defineProps({
  showModal: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'success'])

const auth = useAuthStore()
const activeTab = ref('login')
const showPassword = ref(false)
const rememberMe = ref(false)
const agreeTerms = ref(false)
const isLoading = ref(false)

const loginForm = reactive({
  email: '',
  password: ''
})

const registerForm = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: ''
})

const closeModal = () => {
  emit('close')
}

const handleLogin = async () => {
  if (!loginForm.email || !loginForm.password) {
    toast.error('Vui lòng nhập đầy đủ thông tin!')
    return
  }

  isLoading.value = true
  try {
    await auth.login(loginForm.email, loginForm.password)
    toast.success('Đăng nhập thành công!')
    emit('success')
    closeModal()
  } catch (error) {
    toast.error('Đăng nhập thất bại!')
  } finally {
    isLoading.value = false
  }
}

const handleRegister = async () => {
  if (registerForm.password !== registerForm.confirmPassword) {
    toast.error('Mật khẩu xác nhận không khớp!')
    return
  }

  if (!agreeTerms.value) {
    toast.error('Vui lòng đồng ý điều khoản sử dụng!')
    return
  }

  isLoading.value = true
  try {
    await auth.register(registerForm.name, registerForm.email, registerForm.password)
    toast.success('Đăng ký thành công! Vui lòng đăng nhập.')
    activeTab.value = 'login'
  } catch (error) {
    toast.error('Đăng ký thất bại!')
  } finally {
    isLoading.value = false
  }
}

const handleForgotPassword = () => {
  toast.info('Tính năng đang phát triển!')
}
</script>

<style scoped>
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
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.input-wrapper {
  position: relative;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #ff4d30;
}

.input-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.2s;
}

.toggle-password:hover {
  background: #f5f5f5;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.forgot-link {
  color: #ff4d30;
  text-decoration: none;
  font-size: 14px;
  transition: opacity 0.2s;
}

.forgot-link:hover {
  opacity: 0.8;
}

.submit-btn {
  background: #ff4d30;
  color: white;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background 0.2s;
  font-size: 15px;
}

.submit-btn:hover:not(:disabled) {
  background: #e6392c;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.divider {
  text-align: center;
  margin: 16px 0;
  position: relative;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e5e5e5;
}

.divider span {
  background: white;
  padding: 0 16px;
  color: #666;
  font-size: 14px;
}

.social-login {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  text-decoration: none;
  color: #333;
  font-weight: 500;
  transition: all 0.2s;
  font-size: 14px;
}

.social-btn:hover {
  background: #f8f9fa;
}

.social-btn.facebook {
  background: #1877f2;
  color: white;
  border-color: #1877f2;
}

.social-btn.facebook:hover {
  background: #166fe5;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s;
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
  .modal-overlay {
    padding: 10px;
  }
  
  .modal-container {
    max-width: 100%;
    margin: 10px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .modal-container {
    background: #1e1e1e;
  }
  
  .tab-switcher {
    background: #2a2a2a;
  }
  
  .form-group label {
    color: #fff;
  }
  
  .input-wrapper input {
    background: #2a2a2a;
    border-color: #444;
    color: #fff;
  }
  
  .divider span {
    background: #1e1e1e;
    color: #fff;
  }
}
</style>
