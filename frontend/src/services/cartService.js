import api from './api.js'

export const cartService = {
  getCart() {
    return api.get('/cart')
  },
  
  addToCart(productId, quantity = 1, variantId = null) {
    return api.post('/cart/add', {
      product_id: productId,
      quantity,
      variant_id: variantId
    })
  },
  
  updateCartItem(itemId, quantity) {
    return api.put(`/cart/${itemId}`, { quantity })
  },
  
  removeFromCart(itemId) {
    return api.delete(`/cart/${itemId}`)
  },
  
  clearCart() {
    return api.delete('/cart')
  },
  
  applyCoupon(couponCode) {
    return api.post('/cart/apply-coupon', { coupon_code: couponCode })
  },
  
  removeCoupon() {
    return api.delete('/cart/coupon')
  },
  
  syncCart() {
    return api.post('/cart/sync')
  }
}
