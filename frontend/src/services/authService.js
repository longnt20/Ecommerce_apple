import api from './api.js'

export const authService = {
  login(credentials) {
    return api.post('/login', credentials)
  },
  
  logout() {
    return api.post('/logout')
  },
  
  register(userData) {
    return api.post('/register', userData)
  },
  
  getUser() {
    return api.get('/user')
  },
  
  refreshToken() {
    return api.post('/refresh-token')
  }
}
