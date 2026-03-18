import { defineStore } from 'pinia'

export const useLoadingStore = defineStore('loading', {
  state: () => ({
    isLoading: false
  }),
  actions: {
    show() { this.isLoading = true },
    hide() { this.isLoading = false }
  }
})
