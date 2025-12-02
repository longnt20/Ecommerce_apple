import { createRouter, createWebHistory } from "vue-router";
import LoginSuccess from "../components/status/LoginSuccess.vue";
import App from "../App.vue";

// Import pages
import Home from "../pages/Home.vue";
import Product from "../pages/Product.vue";
import ProductDetail from "../pages/ProductDetail.vue";
import { useLoadingStore } from "../effects/loading";
import CartPage from "../components/Cart/CartPage.vue";
import ProfilePage from "../components/Profile/ProfilePage.vue";
import CheckoutPage from "../components/Cart/CheckoutPage.vue";
import PaymentSuccess from "../components/Cart/PaymentSuccess.vue";
const routes = [
    {
        path: "/",
        name: "home",
        component: Home,
    },
    {
        path: "/profile",
        name: "profile",
        component: ProfilePage,
    },
    {
        path: "/products",
        name: "product",
        component: Product,
    },
    {
        path: "/:slug",
        name: "product-detail",
        component: ProductDetail,
        props: true,
    },
    {
        path: "/cart",
        name: "cart-page",
        component: CartPage,
        props: true,
    },
    {
        path: "/checkout",
        name: "checkout-page",
        component: CheckoutPage,
        props: true,
    },
        {
        path: "/payment-success",
        name: "payment-success",
        component: PaymentSuccess,
        props: true,
    },
    {
        path: "/login-success",
        name: "login.success",
        component: LoginSuccess,
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});
router.beforeEach((to, from, next) => {
    const loading = useLoadingStore();
    loading.show();
    next();
});

router.afterEach(() => {
    const loading = useLoadingStore();
    setTimeout(() => loading.hide(), 300); // mượt hơn
});
export default router;
