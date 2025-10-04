<template>
  <!-- User Button -->
  <button class="user-button" @click="openModal">
    <UserCircle2 :size="20" />
    <span class="user-label">{{ isLoggedIn ? userName : 'Đăng nhập' }}</span>
  </button>

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
                <button type="button" class="social-btn google">
                  <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" />
                  <span>Đăng nhập với Google</span>
                </button>
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
  UserCircle2, 
  X, 
  Mail, 
  Lock, 
  Eye, 
  EyeOff, 
  User,
  Loader2 
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

// State
const showModal = ref(false);
const activeTab = ref('login');
const showPassword = ref(false);
const isLoading = ref(false);
const rememberMe = ref(false);
const agreeTerms = ref(false);

// User state (có thể lấy từ store)
const isLoggedIn = ref(false);
const userName = ref('');

// Form data
const loginForm = ref({
  email: '',
  password: ''
});

const registerForm = ref({
  name: '',
  email: '',
  password: '',
  confirmPassword: ''
});

// Methods
const openModal = () => {
  if (isLoggedIn.value) {
    // Nếu đã đăng nhập, hiển thị menu user
    console.log('Show user menu');
  } else {
    showModal.value = true;
    document.body.style.overflow = 'hidden';
  }
};

const closeModal = () => {
  showModal.value = false;
  document.body.style.overflow = '';
  resetForms();
};

const resetForms = () => {
  loginForm.value = { email: '', password: '' };
  registerForm.value = { name: '', email: '', password: '', confirmPassword: '' };
  showPassword.value = false;
  agreeTerms.value = false;
};

const handleLogin = async () => {
  isLoading.value = true;
  try {
    // Call API login
    await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate API call
    
    // Success
    isLoggedIn.value = true;
    userName.value = 'Nguyễn Văn A';
    closeModal();
    
    // Show success message
    console.log('Đăng nhập thành công');
  } catch (error) {
    console.error('Login error:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleRegister = async () => {
  // Validate passwords match
  if (registerForm.value.password !== registerForm.value.confirmPassword) {
    alert('Mật khẩu không khớp!');
    return;
  }

  isLoading.value = true;
  try {
    // Call API register
    await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate API call
    
    // Success - switch to login tab
    activeTab.value = 'login';
    resetForms();
    
    // Show success message
    console.log('Đăng ký thành công');
  } catch (error) {
    console.error('Register error:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleForgotPassword = () => {
  console.log('Forgot password');
  // Navigate to forgot password page or show forgot password modal
};

// Handle ESC key
const handleEsc = (e) => {
  if (e.key === 'Escape' && showModal.value) {
    closeModal();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleEsc);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc);
});
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
</style>