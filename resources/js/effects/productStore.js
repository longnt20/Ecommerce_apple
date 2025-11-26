import { defineStore } from "pinia";
import axios from "axios";

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
                const res = await axios.get(
                    `http://127.0.0.1:8000/api/${slug}`
                );
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
