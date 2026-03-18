import { defineStore } from "pinia";
import { cartService } from "@/services/cartService";
import { log } from "three";
import { useAuthStore } from "./auth";

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

            const res = await cartService.getCart();
            this.items = res.data.items ?? [];
        },

        async addToCart(productId, variantId, qty = 1) {
            try {
                console.log("Sending:", {
                    product_id: productId,
                    product_variant_id: variantId,
                    quantity: qty,
                });

                const res = await cartService.addToCart(productId, qty, variantId);

                this.items = res.data.data ?? [];
            } catch (e) {
                if (e.response) {
                    console.error("API Error:", e.response.data);
                    alert(JSON.stringify(e.response.data));
                } else {
                    console.error(e);
                }
            }
        },

        async updateQty(itemId, qty) {
            try {
                const res = await cartService.updateCartItem(itemId, qty);

                this.items = res.data.items ?? res.data.data ?? [];
            } catch (e) {
                console.error("updateQty:", e);
            }
        },

        async removeItem(itemId) {
            try {
                const res = await cartService.removeFromCart(itemId);

                this.items = res.data.items ?? res.data.data ?? [];
            } catch (e) {
                console.error("removeItem:", e);
            }
        },
        async syncAfterLogin() {
            try {
                const res = await cartService.syncCart();
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
