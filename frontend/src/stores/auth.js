import { defineStore } from 'pinia'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isLoggedIn: !!localStorage.getItem('token'),
  }),

  actions: {
    async fetchUser() {
      if (!this.token) return

      try {
        const res = await authService.getUser()

        this.user = res.data
        this.isLoggedIn = true
      } catch (err) {
        this.logout()
      }
    },

    async login(email, password) {
      const res = await authService.login({ email, password })

      this.token = res.data.token
      localStorage.setItem('token', this.token)

      await this.fetchUser()
    },

    async register(name, email, password) {
      const res = await authService.register({
        name, email, password
      })

      return res.data
    },

    logout() {
      this.user = null
      this.token = null
      this.isLoggedIn = false
      localStorage.removeItem('token')
    }
  }
})
