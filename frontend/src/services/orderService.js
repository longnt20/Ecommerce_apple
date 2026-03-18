import api from './api.js'

export const orderService = {
  createOrder(orderData) {
    return api.post('/checkout', orderData)
  },
  
  getOrders() {
    return api.get('/orders')
  },
  
  getOrderById(id) {
    return api.get(`/orders/${id}`)
  },
  
  updateOrderStatus(id, status) {
    return api.patch(`/orders/${id}`, { status })
  },
  
  processPayment(orderId, paymentData) {
    return api.post(`/orders/${orderId}/payment`, paymentData)
  },
  
  vnpayReturn(params) {
    return api.get('/vnpay/return', { params })
  }
}
