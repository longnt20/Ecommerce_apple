<script setup lang="ts">
import { LifeBuoy, ListCheck, MessageCircle, Shield, ShieldCheck, Truck, TruckElectric } from 'lucide-vue-next';
const payment_methods = [
    {
        name: 'Apple Pay',
        logo: '/logos/applepay.webp'
    },
    {
        name: 'VNPay',
        logo: '/logos/vnpay.webp'
    },
    {
        name: 'MoMo',
        logo: '/logos/momo.webp'
    },
    {
        name: 'ZaloPay',
        logo: '/logos/zalo.webp'
    }
]
const socials = [
    {
        name: 'Facebook',
        logo: '/logos/cellphones-facebook.webp'
    },
    {
        name: 'Instagram',
        logo: '/logos/cellphones-instagram.webp'
    },
    {
        name: 'TikTok',
        logo: '/logos/cellphones-tiktok.webp'
    },
    {
        name: 'Youtube',
        logo: '/logos/cellphones-youtube.webp'
    },
    {
        name: 'Zalo',
        logo: '/logos/cellphones-zalo.webp'
    }
]
</script>

<template>
    <footer class="footer">
        <!-- TOP SERVICE -->
        <div class="footer-top">
            <div class="service-item">
                <TruckElectric :size="50" />
                <strong> Giao hàng nhanh</strong>
                <span>Toàn quốc 24–48h</span>
            </div>
            <div class="service-item">
                <ListCheck :size="50" />
                <strong>Đổi trả dễ dàng</strong>
                <span>Trong 7 ngày</span>
            </div>
            <div class="service-item">
                <ShieldCheck :size="50" />
                <strong>Thanh toán an toàn</strong>
                <span>COD / Online</span>
            </div>
            <div class="service-item">
                <MessageCircle :size="50" />
                <strong>Hỗ trợ 24/7</strong>
                <span>Hotline & Chat</span>
            </div>
        </div>

        <!-- MAIN -->
        <div class="footer-main">
            <div class="footer-col">
                <h4>Hỗ trợ khách hàng</h4>
                <ul>
                    <li><a href="#">Trung tâm trợ giúp</a></li>
                    <li><a href="#">Hướng dẫn mua hàng</a></li>
                    <li><a href="#">Chính sách đổi trả</a></li>
                    <li><a href="#">Chính sách bảo hành</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Về chúng tôi</h4>
                <ul>
                    <li><a href="#">Giới thiệu</a></li>
                    <li><a href="#">Tuyển dụng</a></li>
                    <li><a href="#">Liên hệ</a></li>
                    <li><a href="#">Tin tức</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Chính sách</h4>
                <ul>
                    <li><a href="#">Chính sách thanh toán</a></li>
                    <li><a href="#">Chính sách vận chuyển</a></li>
                    <li><a href="#">Chính sách bảo mật</a></li>
                    <li><a href="#">Điều khoản sử dụng</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Kết nối với LongTech</h4>
                <div class="payment-icons">
                        <img v-for="social in socials" :key="social.name" :src="social.logo"
                            :alt="social.name" />
                    </div>

                <div class="payment">
                    <span>Phương thức thanh toán:</span>
                    <div class="payment-icons">
                        <img v-for="method in payment_methods" :key="method.name" :src="method.logo"
                            :alt="method.name" />
                    </div>
                </div>

            </div>
        </div>

        <!-- BOTTOM -->
        <div class="footer-bottom">
            © 2026 LongTech Store. All rights reserved.
        </div>
    </footer>
</template>
<style scoped>
.footer {
    background: #fffdfd;
    font-size: 14px;
    color: #333;
}

/* TOP */
.footer-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    width: 1200px;
    margin: 0 auto;
}

.service-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: center;
    align-items: center;
}

/* MAIN */
.footer-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 32px 20px;
    width: 1200px;
    margin: 0 auto;
}

.footer-col h4 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 12px;
}

.footer-col ul {
    list-style: none;
    padding: 0;
}

.footer-col li {
    margin-bottom: 8px;
}

.footer-col a {
    color: #555;
    text-decoration: none;
}

.footer-col a:hover {
    color: #2563eb;
}

/* SOCIAL */
.socials {
    display: flex;
    gap: 6px;
}

/* PAYMENT */
.payment {
    margin-top: 12px;
}

.payment-icons {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}

.payment-icons img {
    height: 28px;
}

/* BOTTOM */
.footer-bottom {
    background: #e5e7eb;
    text-align: center;
    padding: 12px;
    font-size: 13px;
}

/* RESPONSIVE */
@media (max-width: 992px) {

    .footer-top,
    .footer-main {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {

    .footer-top,
    .footer-main {
        grid-template-columns: 1fr;
    }
}
</style>
