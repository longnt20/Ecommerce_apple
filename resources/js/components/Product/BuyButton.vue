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

    </div>
</template>
<script setup>
import { ref } from 'vue';
import { useCartStore } from '../../effects/cart';
import { useBuyNowStore } from '../../effects/buynow';
import { useRouter } from 'vue-router';

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
const addToCart = async () => {
  await cart.addToCart(props.product.id, props.selectedVariant?.id, quantity.value);
  alert("Đã thêm vào giỏ hàng!");
};

const router = useRouter()

const buyNow = useBuyNowStore();

const buyNowAction = () => {
  if (!props.selectedVariant) {
    return alert("Vui lòng chọn màu/dung lượng!");
  }

  buyNow.set({
    product: props.product,
    variant: props.selectedVariant,
    quantity: quantity.value,
  });

  router.push("/checkout?mode=buy-now");
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
