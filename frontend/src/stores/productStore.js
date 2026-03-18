import { defineStore } from "pinia";
import { productService } from "@/services/productService";

export const useProductStore = defineStore("product", {
    state: () => ({
        product: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchProductBySlug(slug) {
            this.loading = true;
            this.error = null;

            try {
                const res = await productService.getProductBySlug(slug);
                this.product = res.data.data;
            } catch (err) {
                this.error = "Không thể tải sản phẩm";
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
});
