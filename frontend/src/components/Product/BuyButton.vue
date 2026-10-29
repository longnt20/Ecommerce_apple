<template>
    <div class="buy-box">

        <!-- HÀNG 1: MUA NGAY + GIỎ HÀNG -->
        <div class="action-row">
            <button class="btn-buy-now"  @click="buyNowAction">
                <strong>MUA NGAY</strong> <br>
                <span>Giao nhanh 2 giờ hoặc nhận tại cửa hàng</span>
            </button>
            <button class="btn-cart" @click="addToCart">
                
                    <span class="fa-layers fa-fw" style="font-size: 20px;">
                    <i class="fa-solid fa-cart-shopping"></i>
                </span>
                <span>+ Thêm vào giỏ</span>
            </button>
        </div>

        <!-- Auth Modal -->
        <AuthModal 
            :showModal="showAuthModal" 
            @close="showAuthModal = false"
            @success="handleAuthSuccess"
        />
    </div>
</template>
<script setup>
import { beginCheckoutIntent } from "@/services/checkoutCommand";
import { ref } from 'vue';
import { useCartStore } from '@/stores/cart';
import { useBuyNowStore } from '@/stores/buynow';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
// import { toast } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import AuthModal from '@/components/common/AuthModal.vue';

const props = defineProps({
  product: {
    type: Object,
    default: null,
  },
  selectedVariant: {
    type: Object,
    default: null,
  }
});

const quantity = ref(1);
const cart = useCartStore();
const buyNow = useBuyNowStore();
const auth = useAuthStore();
const router = useRouter();
const showAuthModal = ref(false);

const checkAuth = () => {
  if (!auth.isLoggedIn) {
    showAuthModal.value = true;
    return false;
  }
  return true;
};

const addToCart = async () => {
  if (!checkAuth()) return;
  
  if (!props.selectedVariant) {
    return toast.error("Vui lòng chọn màu/dung lượng!");
  }

  try {
    await cart.addToCart(props.product.id, props.selectedVariant?.id, quantity.value);
    toast.success("Đã thêm vào giỏ hàng!");
  } catch (error) {
    toast.error("Thêm vào giỏ hàng thất bại!");
  }
};

const buyNowAction = () => {
  if (!checkAuth()) return;
  
  if (!props.selectedVariant) {
    return toast.error("Vui lòng chọn màu/dung lượng!");
  }

  buyNow.set({
    product: props.product,
    variant: props.selectedVariant,
    quantity: quantity.value,
  });

  beginCheckoutIntent();
  router.push("/checkout?mode=buy-now");
};

const handleAuthSuccess = () => {
  // Auth successful, proceed with the action
  showAuthModal.value = false;
  // The user can now try the action again
};
</script>
<style scoped>
.buy-box {
    margin-right: 40px;
    user-select: none;
}

/* ===== HÀNG 1 ===== */
.action-row {
    display: flex;
    gap: 12px;
    margin-bottom: 14px;
}
.btn-buy-now {
    flex: 4;
    background: linear-gradient(90deg, #d70018, #ff2b4a);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 14px 10px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(215, 0, 24, 0.35);
    transition: 0.25s;
}

.btn-buy-now strong {
    font-size: 18px;
}

.btn-buy-now span {
    font-size: 11px;
    opacity: 0.9;
}

.btn-buy-now:hover {
    filter: brightness(1.08);
    transform: translateY(-2px);
}

/* GIỎ HÀNG */
.btn-cart {
    flex: 1.2;
    border: 2px solid #d70018;
    background: #fff;
    color: #d70018;
    border-radius: 12px;
    padding: 10px 0;
    text-align: center;
    cursor: pointer;
    transition: 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-cart .icon {
    font-size: 22px;
}

.btn-cart span:last-child {
    font-size: 11px;
    font-weight: 600;
}

.btn-cart:hover {
    background: #d70018;
    color: #fff;
}

/* ===== HÀNG 2 ===== */
.installment-row {
    display: flex;
    gap: 12px;
}

.btn-installment {
    flex: 1;
    background: #267cd8;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 10px;
    cursor: pointer;
    font-size: 13px;
    transition: 0.25s;
    text-align: center;
    box-shadow: 0 2px 8px rgba(38, 124, 216, 0.3);
}

.btn-installment strong {
    display: block;
    font-size: 13px;
    margin-bottom: 2px;
}

.btn-installment span {
    font-size: 10px;
    opacity: 0.9;
}

.btn-installment:hover {
    background: #1e6ab8;
    transform: translateY(-2px);
}
</style>
