<template>
  <button 
    class="location-button"
    @click="toggleDropdown"
    :class="{ 'active': isOpen }"
  >
    <div class="location-content">
      <MapPin :size="18" class="location-icon" />
      <span class="location-value">{{ selectedLocation }}</span>
      <ChevronDown 
        :size="16" 
        class="chevron-icon"
      />
    </div>
    
    <!-- Dropdown menu -->
    <Transition name="dropdown">
      <div v-if="isOpen" class="location-dropdown">
        <div 
          v-for="location in locations" 
          :key="location.id"
          class="location-item"
          :class="{ 'selected': location.name === selectedLocation }"
          @click.stop="selectLocation(location)"
        >
          <span>{{ location.name }}</span>
          <Check v-if="location.name === selectedLocation" :size="16" class="check-icon" />
        </div>
      </div>
    </Transition>
  </button>
</template>

<script setup>
import { MapPin, ChevronDown, Check } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const isOpen = ref(false);
const selectedLocation = ref('Hà Nội');

const locations = ref([
  { id: 1, name: 'Hà Nội' },
  { id: 2, name: 'TP. Hồ Chí Minh' },
  { id: 3, name: 'Đà Nẵng' },
  { id: 4, name: 'Hải Phòng' },
  { id: 5, name: 'Cần Thơ' },
  { id: 6, name: 'Biên Hòa' },
  { id: 7, name: 'Nha Trang' },
  { id: 8, name: 'Bình Dương' },
]);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectLocation = (location) => {
  selectedLocation.value = location.name;
  isOpen.value = false;
  
  // Emit event hoặc call API để update giá và tồn kho
  // emit('location-changed', location);
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.location-button')) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.location-button {
  position: relative;
  background: transparent;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  font-family: inherit;
}

.location-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.location-button.active {
  background-color: rgba(0, 0, 0, 0.05);
}

.location-content {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #333;
}

.location-icon {
  color: #ffffff;
  display: flex;
  align-items: center;
}

.location-value {
  font-size: 14px;
  font-weight: 500;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chevron-icon {
  color: #ffffff;
  transition: transform 0.2s ease;
}

.location-button.active .chevron-icon {
  transform: rotate(180deg);
}

/* Dropdown Styles */
.location-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  min-width: 200px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  z-index: 1000;
  overflow: hidden;
  max-height: 320px;
  overflow-y: auto;
}

.location-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 14px;
  color: #333;
}

.location-item:not(:last-child) {
  border-bottom: 1px solid #f5f5f5;
}

.location-item:hover {
  background-color: #f8f9fa;
}

.location-item.selected {
  background-color: #fff4f4;
  color: #ef3343;
  font-weight: 500;
}

.check-icon {
  color: #ef3343;
  flex-shrink: 0;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-10px);
}

/* Scrollbar styles */
.location-dropdown::-webkit-scrollbar {
  width: 4px;
}

.location-dropdown::-webkit-scrollbar-track {
  background: transparent;
}

.location-dropdown::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 2px;
}

.location-dropdown::-webkit-scrollbar-thumb:hover {
  background: #bbb;
}

/* Responsive */
@media (max-width: 768px) {
  .location-value {
    max-width: 80px;
  }
  
  .location-dropdown {
    min-width: 180px;
    left: auto;
    right: 0;
    transform: none;
  }
  
  .dropdown-enter-from,
  .dropdown-leave-to {
    transform: translateY(-10px);
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .location-content {
    color: #f0f0f0;
  }
  
  .location-button:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
  
  .location-dropdown {
    background: #2a2a2a;
    border-color: #444;
  }
  
  .location-item {
    color: #f0f0f0;
    border-bottom-color: #444;
  }
  
  .location-item:hover {
    background-color: #333;
  }
  
  .location-item.selected {
    background-color: rgba(239, 51, 67, 0.1);
  }
}
</style>