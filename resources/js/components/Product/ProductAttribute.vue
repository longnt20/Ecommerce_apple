<template>
  <div class="wrapper">

    <!-- STORAGE -->
    <div class="section">
      <div class="title">Phiên bản</div>
      <div class="storage-list">
        <div 
          v-for="item in storage" 
          :key="item" 
          class="storage-item" 
          :class="{ active: selectedStorage === item }"
          @click="selectStorage(item)"
        >
          {{ item }}
          <span v-if="selectedStorage === item" class="check"></span>
        </div>
      </div>
    </div>

    <!-- COLORS -->
    <div class="section">
      <div class="title">Màu sắc</div>
      <div class="color-list">
        <div 
          v-for="variant in colors" 
          :key="variant.color" 
          class="color-item"
          :class="{ active: selectedColor === variant.color,disabled: variant.available_quantity <= 0}"
          @click="selectColor(variant)"
        >
          <img :src="variant.thumbnail" class="color-img" />
          <div>
            <div class="color-name">{{ variant.color }}</div>
            <div class="color-price">{{ variant.final_price }}đ</div>
          </div>
          <span v-if="selectedColor === variant.color" class="check"></span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  props: {
    product: Object,
    selectedVariant: Object
  },

  data() {
    return {
      selectedStorage: null,
      selectedColor: null,
    };
  },

  computed: {
    storage() {
      return [...new Set(this.product?.variants.map(v => v.storage))];
    },
    colors() {
      if (!this.selectedStorage) return [];
      return this.product.variants.filter(v => v.storage === this.selectedStorage);
    },
    currentVariant() {
      return this.product.variants.find(
        v => v.storage === this.selectedStorage && v.color === this.selectedColor
      );
    }
  },

  watch: {
    product: {
      immediate: true,
      handler(newProduct) {
        if (!newProduct?.variants?.length) return;

        // Nếu có selectedVariant thì active theo nó
        const sv = this.selectedVariant;
        if (sv) {
          this.selectedStorage = sv.storage;
          this.selectedColor = sv.color;

          const v = this.product.variants.find(
            x => x.storage === sv.storage && x.color === sv.color
          );
          if (v) this.$emit('variant-selected', v);
        } else {
          // fallback: chọn storage đầu + màu đầu
          this.selectedStorage = this.storage[0];
          this.selectedColor = this.colors[0]?.color;
          if (this.colors[0]) this.$emit('variant-selected', this.colors[0]);
        }
      }
    }
  },

  methods: {
  selectStorage(storage) {
    this.selectedStorage = storage;

    // Kiểm tra màu hiện tại có còn tồn tại trong storage mới không
     const availableVariants = this.product.variants
    .filter(v => v.storage === storage && v.available_quantity > 0);

  // Nếu màu đang chọn không còn tồn tại hoặc hết hàng → chọn màu khả dụng đầu tiên
  if (
    !availableVariants.find(v => v.color === this.selectedColor)
  ) {
    this.selectedColor = availableVariants[0]?.color || null;
  }

    // emit variant
    const variant = this.product.variants.find(
      v => v.storage === this.selectedStorage && v.color === this.selectedColor
    );

    if (variant) this.$emit('variant-selected', variant);
  },

  selectColor(variant) {
    if (variant.available_quantity <= 0) return;
    this.selectedColor = variant.color;
    this.$emit('variant-selected', variant);
  }
}

};
</script>

<style scoped>
.wrapper {
  font-family: sans-serif;
}

.section {
  margin-bottom: 22px;
}

.title {
  font-size: 17px;
  font-weight: 600;
  margin-bottom: 12px;
}

/* STORAGE BUTTONS */
.storage-list {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.color-item.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}
.storage-item {
  padding: 14px 26px;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  font-size: 14px;
  cursor: pointer;
  position: relative;
  transition: 0.15s;
  max-width: 140px;
  word-break: break-word;
}

.storage-item.active {
  border: 2px solid #c40000;
}

.check {
  width: 16px;
  height: 16px;
  background: #c40000;
  border-radius: 5px;
  position: absolute;
  top: -1px;
  right: -1px;
}

/* Nếu muốn thêm icon ✔ trắng bên trong */
.check::after {
  content: "✔";
  color: white;
  font-size: 12px;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -55%);
}

/* COLOR OPTIONS */
.color-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.color-item {
  display: flex;
  gap: 2px;
  align-items: center;
  width: 160px;
  padding: 5px;
  border: 1.5px solid #ddd;
  border-radius: 12px;
  cursor: pointer;
  position: relative;
  transition: 0.15s;
  min-height: 60px;
}

.color-item.active {
  border: 2px solid #c40000;
}

.color-img {
  width: 40px;
  height: auto;
  display: block;
  margin-top: 1px;
}

.color-name {
  font-size: 14px;
  font-weight: 600;
}

.color-price {
  font-size: 14px;
  margin-top: 2px;
}
</style>
