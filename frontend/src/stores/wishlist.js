import { defineStore } from "pinia";
import { wishlistService } from "@/services/wishlistService";

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
                const data = await wishlistService.getWishlist();
                this.items = data;
            } catch (e) {
                console.error("Fetch wishlist failed", e);
            }
        },

        async toggle(productId, variantId) {
            try {
                const data = await wishlistService.toggleWishlist(productId, variantId);

                if (data.status === "added") {
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
            await wishlistService.removeFromWishlist(wishlistId);
            this.items = this.items.filter((i) => i.id !== wishlistId);
        },
        clear() {
            this.items = [];
        },
    },
});
