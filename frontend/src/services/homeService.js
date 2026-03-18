import api from './api.js'

export const homeService = {
  async getHomeData() {
    const response = await api.get('/home')
    return response.data
  },
  async getHomeMainData() {
    const response = await api.get('/home-main')
    return response.data
  }
}
