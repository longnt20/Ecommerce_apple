import api from './api.js'

export const productService = {
  getProducts(params = {}) {
    return api.get('/products', { params })
  },
  
  getProductBySlug(slug) {
    return api.get(`/products/${slug}`)
  },
  
  getProductById(id) {
    return api.get(`/products/${id}`)
  },
  
  getFeaturedProducts() {
    return api.get('/products/featured')
  },
  
  getHotSaleProducts() {
    return api.get('/products/hot-sale')
  },
  
  getProductsByCategory(categoryId) {
    return api.get(`/products/category/${categoryId}`)
  },
  
  searchProducts(query) {
    return api.get('/products/search', { params: { q: query } })
  }
}
