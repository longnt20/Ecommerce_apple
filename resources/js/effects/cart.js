import { defineStore } from "pinia";
import axios from "axios";
import { log } from "three";
import { useAuthStore } from "./auth";
axios.defaults.baseURL = "http://127.0.0.1:8000/api";
axios.defaults.headers.common["Authorization"] = `Bearer ${localStorage.getItem(
    "token"
)}`;

export const useCartStore = defineStore("cart", {
    state: () => ({
        items: [],
        loading: false,
    }),

    getters: {
        totalQty(state) {
            return (state.items ?? []).reduce(
                (sum, item) => sum + item.quantity,
                0
            );
        },
        totalPrice(state) {
            return (state.items ?? []).reduce(
                (sum, item) => sum + item.quantity * item.price_at_add,
                0
            );
        },
    },

    actions: {
        async loadCart() {
            const authStore = useAuthStore();

            if (!authStore.isLoggedIn) {
                this.items = [];
                return;
            }

            const res = await axios.get("http://127.0.0.1:8000/api/cart");
            this.items = res.data.items ?? [];
        },

        async addToCart(productId, variantId, qty = 1) {
            try {
                console.log("Sending:", {
                    product_id: productId,
                    product_variant_id: variantId,
                    quantity: qty,
                });

                const res = await axios.post(
                    "http://127.0.0.1:8000/api/cart/add",
                    {
                        product_id: productId,
                        product_variant_id: variantId,
                        quantity: qty,
                    }
                );

                this.items = res.data.data ?? [];
            } catch (e) {
                if (e.response) {
                    console.error("API Error:", e.response.data); // sẽ thấy chi tiết lỗi 422
                    alert(JSON.stringify(e.response.data));
                } else {
                    console.error(e);
                }
            }
        },

        async updateQty(itemId, qty) {
            try {
                const res = await axios.put(
                    `http://127.0.0.1:8000/api/cart/item/${itemId}`,
                    { quantity: qty }
                );

                this.items = res.data.items ?? res.data.data ?? [];
            } catch (e) {
                console.error("updateQty:", e);
            }
        },

        async removeItem(itemId) {
            try {
                const res = await axios.delete(
                    `http://127.0.0.1:8000/api/cart/item/${itemId}`
                );

                this.items = res.data.items ?? res.data.data ?? [];
            } catch (e) {
                console.error("removeItem:", e);
            }
        },
        async syncAfterLogin() {
            try {
                const res = await axios.post(
                    "http://127.0.0.1:8000/api/cart/sync"
                );
                this.items = res.data.data ?? [];
            } catch (e) {
                console.error("syncAfterLogin:", e);
            }
        },

        clear() {
            this.items = [];
        },
    },
});
