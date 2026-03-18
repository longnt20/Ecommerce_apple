import { defineStore } from "pinia";

export const useBuyNowStore = defineStore("buyNow", {
  state: () => ({
    product: null,
    variant: null,
    quantity: 1,
    active: false, 
  }),

  actions: {
    set(data) {
      this.product = data.product;
      this.variant = data.variant;
      this.quantity = data.quantity;
      this.active = true;       
    },

    clear() {
      this.product = null;
      this.variant = null;
      this.quantity = 1;
      this.active = false;
    }
  },

  persist: true,  
});
