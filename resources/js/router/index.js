import { createRouter, createWebHistory } from "vue-router";
import LoginSuccess from "../components/status/LoginSuccess.vue";
import App from "../App.vue";
import DetailProduct from "../components/Product/DetailProduct.vue";
import HomPage from "../components/HomePage/HomPage.vue";

// Import pages
import Home from '../pages/Home.vue'
import Product from '../pages/Product.vue'
import ProductDetail from '../pages/ProductDetail.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home
  },
  {
    path: '/products',
    name: 'product',
    component: Product
  },
  {
    path: '/test-products',
    name: 'product-detail',
    component: ProductDetail,
    props: true
  }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
