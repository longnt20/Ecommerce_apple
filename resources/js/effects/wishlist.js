import { defineStore } from "pinia";
import axios from "@/axios"; // axios đã gắn token interceptor

export const useWishlistStore = defineStore("wishlist", {
    state: () => ({
        items: [],
    }),

    getters: {
        isWishlisted: (state) => {
            return (variantId) =>
                state.items.some(
                    (item) => item.product_variant_id === variantId,
                );
        },

        total: (state) => state.items.length,
    },

    actions: {
        async fetchWishlist() {
            try {
                const res = await axios.get(
                    "http://127.0.0.1:8000/api/wishlist",
                );
                this.items = res.data;
            } catch (e) {
                console.error("Fetch wishlist failed", e);
            }
        },

        async toggle(productId, variantId) {
            try {
                const res = await axios.post(
                    "http://127.0.0.1:8000/api/wishlist/toggle",
                    {
                        product_id: productId,
                        product_variant_id: variantId,
                    },
                );

                if (res.data.status === "added") {
                    this.items.push({
                        product_id: productId,
                        product_variant_id: variantId,
                    });
                } else {
                    this.items = this.items.filter(
                        (item) => item.product_variant_id !== variantId,
                    );
                }
            } catch (e) {
                console.error("Toggle wishlist failed", e);
            }
        },
        async removeById(wishlistId) {
            await axios.delete(`http://127.0.0.1:8000/api/wishlist/${wishlistId}`);
            this.items = this.items.filter((i) => i.id !== wishlistId);
        },
        clear() {
            this.items = [];
        },
    },
});
