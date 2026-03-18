import api from './api.js';

export const locationService = {
  async getProvinces() {
    try {
      const res = await api.get('/provinces');
      const data = res.data;
      
      // Convert object to array for Vue reactivity
      const provinces = Array.isArray(data) ? data : Object.values(data || {});
      return provinces;
    } catch (error) {
      console.error('Error fetching provinces:', error);
      throw error;
    }
  },
  
  async getDistricts(provinceId) {
    try {
      const res = await api.get(`/districts/${provinceId}?depth=2`);
      const data = res.data;
      
      // API returns object with districts property
      if (data && data.districts && Array.isArray(data.districts)) {
        return data.districts;
      }
      
      // Fallback: convert to array
      return Array.isArray(data) ? data : Object.values(data || {});
    } catch (error) {
      console.error('Error fetching districts:', error);
      throw error;
    }
  },
  
  async getWards(districtId) {
    try {
      const res = await api.get(`/wards/${districtId}?depth=2`);
      const data = res.data;
      
      // API returns object with wards property
      if (data && data.wards && Array.isArray(data.wards)) {
        return data.wards;
      }
      
      // Fallback: convert to array
      return Array.isArray(data) ? data : Object.values(data || {});
    } catch (error) {
      console.error('Error fetching wards:', error);
      throw error;
    }
  }
}
