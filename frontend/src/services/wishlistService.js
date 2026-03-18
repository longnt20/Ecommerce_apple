import api from './api.js'

export const wishlistService = {
  async getWishlist() {
    const response = await api.get('/wishlist')
    return response.data
  },
  
  async toggleWishlist(productId, variantId) {
    const response = await api.post('/wishlist/toggle', {
      product_id: productId,
      product_variant_id: variantId
    })
    return response.data
  },
  
  async removeFromWishlist(wishlistId) {
    const response = await api.delete(`/wishlist/${wishlistId}`)
    return response.data
  }
}
